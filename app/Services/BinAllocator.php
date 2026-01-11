<?php

namespace App\Services;

use App\Models\ASN;
use App\Models\ASNAllocation;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BinAllocator
{
    /**
     * Lógica para ENTRADAS (ASN):
     * Busca bines vacíos compatibles para guardar mercancía.
     */
    public function allocateASN(ASN $asn)
    {
        $asn->load('items.product');

        // Buscar ubicaciones vacías con tipo de bin definido
        // Aquí podrías filtrar por warehouse si ASN tuviera warehouse_id
        $emptyLocations = Location::whereDoesntHave('stock', function($q) {
                $q->where('quantity', '>', 0);
            })
            ->whereNotNull('bin_type_id') 
            ->with('binType')
            ->orderBy('aisle') 
            ->orderBy('rack')
            ->orderBy('shelf')
            ->get();

        foreach ($asn->items as $item) {
            $product = $item->product;
            $qtyToAllocate = $item->expected_quantity;

            // Dimensiones seguras
            $l = (float)$product->length_cm ?: 30; 
            $w = (float)$product->width_cm ?: 20;
            $h = (float)$product->height_cm ?: 12;
            
            $prodVol = $l * $w * $h;
            if ($prodVol <= 0) $prodVol = 1000; 
            
            foreach ($emptyLocations as $key => $loc) {
                if ($qtyToAllocate <= 0) break;

                $binType = $loc->binType;
                if (!$binType || $binType->length <= 0) continue;
                
                $binVol = $binType->length * $binType->width * $binType->height;
                
                // Capacidad al 85%
                $maxUnitsInBin = floor(($binVol / $prodVol) * 0.85);
                if ($maxUnitsInBin < 1) continue;

                $qtyForThisBin = min($qtyToAllocate, $maxUnitsInBin);

                ASNAllocation::create([
                    'asn_item_id' => $item->id,
                    'location_id' => $loc->id,
                    'quantity' => $qtyForThisBin,
                    'status' => 'planned'
                ]);

                $qtyToAllocate -= $qtyForThisBin;
                // $emptyLocations->forget($key); // Opcional: Si queremos llenar un solo bin por producto
            }
        }
    }

    /**
     * Lógica para SALIDAS (Picking / Pedidos):
     * Busca inventario existente (FIFO) para reservar stock.
     */
    public function allocateOrder(Order $order)
    {
        $allAllocated = true;

        // Iterar sobre los items de la orden que faltan por asignar
        foreach ($order->items as $item) {
            // Calcular cuánto falta por reservar. 
            // Usamos 'picked_quantity' como referencia base si no hay columna 'allocated'
            // Pero idealmente deberíamos calcular sum(allocations)
            
            $alreadyAllocated = OrderAllocation::where('order_item_id', $item->id)->sum('quantity');
            $needed = $item->requested_quantity - $alreadyAllocated;

            if ($needed <= 0) continue;

            // 1. Buscar Inventario Disponible (FIFO) en la SUCURSAL de la orden
            $inventories = Inventory::where('product_id', $item->product_id)
                ->where('quantity', '>', 0)
                ->whereHas('location.warehouse', function($q) use ($order) {
                    // FILTRO CRÍTICO: Solo inventario de la sucursal de la orden
                    $q->where('branch_id', $order->branch_id)
                      ->where('is_blocked', false);
                })
                ->whereDoesntHave('location', function($q) {
                    // Excluir ubicaciones de sistema (Cuarentena, Recepción, etc)
                    // Asumiendo que 'storage' es el tipo correcto para picking
                    $q->whereIn('type', ['quarantine', 'staging']);
                })
                ->orderBy('created_at', 'asc') // FIFO (First In, First Out)
                ->get();

            // 2. Iterar y Reservar (Hard Allocation)
            foreach ($inventories as $inv) {
                if ($needed <= 0) break;

                // Calcular stock real disponible en el bin (Físico - Reservado por otros)
                $committed = OrderAllocation::where('inventory_id', $inv->id)->sum('quantity');
                $availableInBin = $inv->quantity - $committed;

                if ($availableInBin <= 0) continue;

                $take = min($needed, $availableInBin);

                // Crear Reserva
                OrderAllocation::create([
                    'order_id' => $order->id,
                    'order_item_id' => $item->id,
                    'inventory_id' => $inv->id,
                    'quantity' => $take
                ]);

                $needed -= $take;
            }

            if ($needed > 0) {
                $allAllocated = false;
            }
        }

        // Actualizar Estado de la Orden
        if ($allAllocated) {
            $order->update(['status' => 'allocated']);
        } else {
            // Si se reservó algo pero no todo, es Backorder
            $hasAllocations = OrderAllocation::where('order_id', $order->id)->exists();
            if ($hasAllocations) {
                $order->update(['status' => 'backorder']);
            }
            // Si no se reservó nada, se queda en 'pending' o pasa a 'backorder' total
        }

        return $allAllocated;
    }

    /**
     * Lógica para ANULAR (Release Stock):
     * Libera las reservas (Allocations) sin afectar el stock físico (porque nunca salió).
     */
    public function deallocateOrder(Order $order)
    {
        // Solo eliminamos las reservas. El stock físico sigue en el Inventory.
        OrderAllocation::where('order_id', $order->id)->delete();

        // Marcar orden como cancelada
        $order->update(['status' => 'cancelled']);
    }

    /**
     * Sugiere la mejor ubicación para GUARDAR (Put-away) un producto.
     * Usado en Recepción y Traslados.
     */
    public function getBestLocationForProduct($product, $warehouse)
    {
        // 1. Estrategia: Agrupar (Buscar donde ya exista el mismo producto)
        $existingLoc = Inventory::where('product_id', $product->id)
            ->whereHas('location', function($q) use ($warehouse) {
                $q->where('warehouse_id', $warehouse->id)
                  ->where('type', 'storage')
                  ->where('is_blocked', false);
            })
            ->orderBy('quantity', 'desc') // Llenar el que tenga más primero
            ->first();

        if ($existingLoc) {
            return $existingLoc->location_id;
        }

        // 2. Estrategia: Primer Hueco Vacío (Empty Bin)
        // Buscamos ubicaciones de almacenamiento que NO tengan registros en inventario
        $emptyLoc = Location::where('warehouse_id', $warehouse->id)
            ->where('type', 'storage')
            ->where('is_blocked', false)
            ->whereDoesntHave('inventory', function($q) {
                $q->where('quantity', '>', 0);
            })
            ->orderBy('aisle')
            ->orderBy('level')
            ->orderBy('position')
            ->first();

        if ($emptyLoc) {
            return $emptyLoc->id;
        }

        return null; // Bodega llena o sin configuración
    }
}