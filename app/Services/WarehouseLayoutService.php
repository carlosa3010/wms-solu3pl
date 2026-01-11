<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Exception;

class WarehouseLayoutService
{
    /**
     * GENERACIÓN MASIVA: Crea o Sincroniza toda la estructura.
     */
    public function generateFullWarehouse(int $warehouseId, array $levelConfigs)
    {
        $warehouse = Warehouse::with('branch')->findOrFail($warehouseId);
        
        if ($warehouse->rows <= 0 || $warehouse->cols <= 0 || $warehouse->levels <= 0) {
            throw new Exception("La bodega no tiene dimensiones definidas.");
        }

        DB::beginTransaction();
        try {
            $createdCount = 0;
            $branchCode = $warehouse->branch->code ?? 'GEN';
            $whCode = $warehouse->code;

            $validAisles = []; 

            // Iterar Pasillos (Rows)
            for ($p = 1; $p <= $warehouse->rows; $p++) {
                $aisleCode = str_pad($p, 2, '0', STR_PAD_LEFT);
                $validAisles[] = $aisleCode;
                
                foreach (['A', 'B'] as $side) {
                    for ($r = 1; $r <= $warehouse->cols; $r++) {
                        $rackCode = str_pad($r, 2, '0', STR_PAD_LEFT);
                        
                        $locations = $this->createRackLogic($warehouse, $aisleCode, $side, $rackCode, $levelConfigs);
                        $createdCount += count($locations);
                    }
                    
                    // LIMPIEZA DE RACKS SOBRANTES EN ESTE PASILLO/LADO
                    $maxRackCode = str_pad($warehouse->cols, 2, '0', STR_PAD_LEFT);
                    Location::where('warehouse_id', $warehouse->id)
                        ->where('aisle', $aisleCode)
                        ->where('side', $side)
                        ->where('rack', '>', $maxRackCode) 
                        ->delete();
                }
            }

            // LIMPIEZA DE PASILLOS SOBRANTES
            Location::where('warehouse_id', $warehouse->id)
                ->whereNotNull('aisle')
                ->whereNotIn('aisle', $validAisles)
                ->delete();

            DB::commit();
            return $createdCount;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * EDICIÓN INDIVIDUAL: Configura un rack específico y LIMPIA lo que sobre.
     */
    public function createRackStructure($warehouseId, $aisle, $side, $rackCode, $levelConfigs)
    {
        $warehouse = Warehouse::with('branch')->findOrFail($warehouseId);
        
        $aislePad = str_pad($aisle, 2, '0', STR_PAD_LEFT);
        $rackPad = str_pad($rackCode, 2, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            // 1. Generar/Actualizar bines y obtener sus IDs
            $createdLocations = $this->createRackLogic($warehouse, $aislePad, $side, $rackPad, $levelConfigs);
            
            // 2. Extraer solo los IDs que acabamos de confirmar que existen
            $validIds = collect($createdLocations)->pluck('id')->toArray();

            // 3. LOGICA DE BORRADO INTELIGENTE (SYNC):
            // Borra (SoftDelete) lo que sobre en este rack
            Location::where('warehouse_id', $warehouse->id)
                ->where('aisle', $aislePad)
                ->where('side', $side)
                ->where('rack', $rackPad)
                ->whereNotIn('id', $validIds) 
                ->delete();

            DB::commit();
            return $createdLocations;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Lógica centralizada CORREGIDA para manejar SoftDeletes.
     */
    private function createRackLogic($warehouse, $aisle, $side, $rackCode, $levelConfigs)
    {
        $branchCode = $warehouse->branch->code ?? 'GEN';
        $whCode = $warehouse->code;
        $createdLocations = [];

        foreach ($levelConfigs as $config) {
            $levelNum = $config['level'];
            
            if ($levelNum > $warehouse->levels) continue;

            $levelCode = str_pad($levelNum, 2, '0', STR_PAD_LEFT);
            $binsCount = $config['bins_count'];
            $binTypeId = $config['bin_type_id'];

            for ($b = 1; $b <= $binsCount; $b++) {
                $binCode = str_pad($b, 2, '0', STR_PAD_LEFT);
                $fullCode = "{$branchCode}-{$whCode}-{$aisle}-{$side}-{$rackCode}-{$levelCode}-{$binCode}";

                // 1. BUSCAR INCLUSO SI ESTÁ BORRADO (withTrashed)
                $loc = Location::withTrashed()
                    ->where('warehouse_id', $warehouse->id)
                    ->where('code', $fullCode)
                    ->first();

                if ($loc) {
                    // 2. SI EXISTE: Actualizar datos y RESTAURAR
                    $loc->update([
                        'aisle'        => $aisle,
                        'side'         => $side,
                        'rack'         => $rackCode,
                        'level'        => $levelCode,
                        'position'     => $binCode,
                        'bin_type_id'  => $binTypeId,
                        'type'         => 'storage',
                        'status'       => 'active',
                        'is_blocked'   => false
                    ]);

                    if ($loc->trashed()) {
                        $loc->restore(); // <--- Aquí ocurre la magia: "Revive" el bin
                    }
                } else {
                    // 3. SI NO EXISTE: Crear nuevo
                    $loc = Location::create([
                        'warehouse_id' => $warehouse->id,
                        'code'         => $fullCode,
                        'aisle'        => $aisle,
                        'side'         => $side,
                        'rack'         => $rackCode,
                        'level'        => $levelCode,
                        'position'     => $binCode,
                        'bin_type_id'  => $binTypeId,
                        'type'         => 'storage',
                        'status'       => 'active',
                        'is_blocked'   => false
                    ]);
                }

                $createdLocations[] = $loc;
            }
        }
        return $createdLocations;
    }

    /**
     * Obtiene el mapa visual.
     */
    public function getWarehouseMapData($warehouseId)
    {
        $locations = Location::where('warehouse_id', $warehouseId)
            ->whereNotNull('aisle') 
            ->where('aisle', '!=', '')
            ->with(['binType', 'inventory']) 
            ->orderBy('aisle')
            ->orderBy('rack')
            ->orderBy('level', 'desc')
            ->orderBy('position')
            ->get();

        $map = [];

        foreach ($locations as $loc) {
            $aisle = $loc->aisle; 
            $side = $loc->side ?? 'A'; 
            $rack = $loc->rack ?? '01';
            
            if (!isset($map[$aisle])) $map[$aisle] = [];
            if (!isset($map[$aisle][$side])) $map[$aisle][$side] = [];
            if (!isset($map[$aisle][$side][$rack])) {
                $map[$aisle][$side][$rack] = [
                    'id' => $rack,
                    'levels' => []
                ];
            }

            $level = $loc->level;
            if (!isset($map[$aisle][$side][$rack]['levels'][$level])) {
                $map[$aisle][$side][$rack]['levels'][$level] = [];
            }

            $map[$aisle][$side][$rack]['levels'][$level][] = [
                'id' => $loc->id,
                'code' => $loc->code,
                'position' => $loc->position,
                'type' => $loc->binType->name ?? 'Std',
                'bin_type_id' => $loc->bin_type_id,
                'has_stock' => $loc->inventory->sum('quantity') > 0
            ];
        }

        return $map;
    }
}