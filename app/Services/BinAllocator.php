<?php

namespace App\Services;

use App\Models\ASN;
use App\Models\Location;
use App\Models\ASNAllocation;
use App\Models\Inventory;

class BinAllocator
{
    /**
     * Ejecuta la lógica de asignación automática para una ASN completa.
     * Algoritmo: Busca bines vacíos compatibles y reserva espacio.
     */
    public function allocateASN(ASN $asn)
    {
        // 1. Cargar items y productos para leer dimensiones
        $asn->load('items.product');

        // 2. Obtener ubicaciones vacías disponibles con tipo de bin
        $emptyLocations = Location::whereDoesntHave('stock', function($q) {
                $q->where('quantity', '>', 0);
            })
            ->whereNotNull('bin_type_id') 
            ->with('binType')
            ->orderBy('aisle') 
            ->orderBy('rack')
            ->orderBy('shelf')
            ->get();

        // 3. Iterar sobre cada producto que esperamos recibir
        foreach ($asn->items as $item) {
            $product = $item->product;
            $qtyToAllocate = $item->expected_quantity;

            // CORRECCIÓN: Usamos '?:' en lugar de '??'. 
            // Si el valor es 0 o null, usa el default (Medidas de caja de zapatos aprox)
            $l = (float)$product->length_cm ?: 30; 
            $w = (float)$product->width_cm ?: 20;
            $h = (float)$product->height_cm ?: 12;
            
            // Calculamos volumen en cm3
            $prodVol = $l * $w * $h;

            // SEGURIDAD: Si por alguna razón sigue siendo 0, forzamos un valor mínimo (10x10x10)
            if ($prodVol <= 0) {
                $prodVol = 1000; 
            }
            
            // 4. Buscar bines para este producto
            foreach ($emptyLocations as $key => $loc) {
                if ($qtyToAllocate <= 0) break; // Ya terminamos con este item

                $binType = $loc->binType;
                
                // Si el tipo de bin no tiene dimensiones válidas, saltamos
                if (!$binType || $binType->length <= 0) continue;
                
                $binVol = $binType->length * $binType->width * $binType->height;
                
                // Capacidad teórica (85% eficiencia)
                // Aquí es donde ocurría el error si $prodVol era 0
                $maxUnitsInBin = floor(($binVol / $prodVol) * 0.85);
                
                if ($maxUnitsInBin < 1) continue;

                // Definir cantidad a guardar
                $qtyForThisBin = min($qtyToAllocate, $maxUnitsInBin);

                // 5. Guardar la asignación
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
}