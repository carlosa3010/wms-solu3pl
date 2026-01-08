<?php

namespace App\Services;

use App\Models\ASN;
use App\Models\ASNAllocation;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderAllocation;
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
                $emptyLocations->forget($key);
            }
        }
    }

    /**
     * Lógica para SALIDAS (Picking / Pedidos):
     * Busca inventario existente (FIFO) para reservar stock.
     */
    public function allocateOrder($order)
    {
        $allocatedAny = false;

        foreach ($order->items as $item) {
            // Si ya está completo, saltar
            if ($item->allocated_quantity >= $item->requested_quantity) {
                continue;
            }

            $qtyNeeded = $item->requested_quantity - $item->allocated_quantity;
            
            // FIFO: Buscar inventario más antiguo primero
            // Asumimos que Inventory tiene relación con Product y Location
            $inventoryBatches = Inventory::where('product_id', $item->product_id)
                ->where('quantity', '>', 0)
                ->orderBy('created_at', 'asc') // FIFO
                ->get();

            if ($inventoryBatches->isEmpty()) {
                continue;
            }

            foreach ($inventoryBatches as $batch) {
                if ($qtyNeeded <= 0) break;

                $qtyToTake = min($qtyNeeded, $batch->quantity);

                // Crear reserva (Allocation)
                OrderAllocation::create([
                    'order_item_id' => $item->id,
                    'location_id'   => $batch->location_id,
                    'quantity'      => $qtyToTake
                ]);

                // Descontar del inventario físico disponible
                $batch->quantity -= $qtyToTake;
                $batch->save();

                // Actualizar item
                $item->allocated_quantity += $qtyToTake;
                $qtyNeeded -= $qtyToTake;
                $allocatedAny = true;
            }

            $item->save();
        }

        $this->updateOrderStatus($order);

        return $allocatedAny;
    }

    private function updateOrderStatus($order)
    {
        $order->refresh();
        $totalRequested = $order->items->sum('requested_quantity');
        $totalAllocated = $order->items->sum('allocated_quantity');

        if ($totalAllocated >= $totalRequested) {
            $order->status = 'allocated'; // Listo para picking
        } elseif ($totalAllocated > 0) {
            $order->status = 'allocated'; // Parcialmente asignado (o usar 'partial')
        }
        
        $order->save();
    }
    /**
     * Lógica para ANULAR (Release Stock):
     * Devuelve el stock reservado al inventario disponible y pone en 0 la orden.
     */
    public function deallocateOrder(Order $order)
    {
        // 1. Cargar relaciones necesarias
        $order->load('items.allocations');

        foreach ($order->items as $item) {
            // Si no tiene nada asignado, saltamos
            if ($item->allocated_quantity <= 0) continue;

            foreach ($item->allocations as $allocation) {
                // 2. Buscar si existe inventario en esa ubicación para ese producto
                $inventory = Inventory::where('product_id', $item->product_id)
                    ->where('location_id', $allocation->location_id)
                    ->first();

                if ($inventory) {
                    // Si existe, sumamos la cantidad
                    $inventory->quantity += $allocation->quantity;
                    $inventory->save();
                } else {
                    // Si el registro de inventario se borró (raro, pero posible), lo recreamos
                    Inventory::create([
                        'product_id' => $item->product_id,
                        'location_id' => $allocation->location_id,
                        'quantity' => $allocation->quantity,
                        'batch_number' => 'RETURN-' . date('Ymd'), // Opcional
                    ]);
                }

                // 3. Eliminar el registro de asignación
                $allocation->delete();
            }

            // 4. Resetear contador del item
            $item->allocated_quantity = 0;
            $item->save();
        }

        // 5. Marcar orden como cancelada
        $order->status = 'cancelled';
        $order->save();
    }
}