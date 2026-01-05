<?php

namespace App\Services;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Exception;

class WarehouseLayoutService
{
    /**
     * GENERACIÓN MASIVA: Crea toda la estructura de la bodega.
     * Recorre las dimensiones definidas en la bodega (Pasillos, Lados, Racks) y aplica la configuración.
     * * @param int $warehouseId
     * @param array $levelConfigs Array con la configuración por nivel
     */
    public function generateFullWarehouse(int $warehouseId, array $levelConfigs)
    {
        $warehouse = Warehouse::with('branch')->findOrFail($warehouseId);
        
        if ($warehouse->rows <= 0 || $warehouse->cols <= 0 || $warehouse->levels <= 0) {
            throw new Exception("La bodega '{$warehouse->name}' no tiene dimensiones definidas. Edita la bodega primero.");
        }

        DB::beginTransaction();
        try {
            $createdCount = 0;
            $branchCode = $warehouse->branch->code ?? 'GEN';
            $whCode = $warehouse->code;

            // Iterar Pasillos (Rows)
            for ($p = 1; $p <= $warehouse->rows; $p++) {
                // Forzamos 2 dígitos siempre: 1 -> "01"
                $aisleCode = str_pad($p, 2, '0', STR_PAD_LEFT);
                
                // Iterar Lados
                foreach (['A', 'B'] as $side) {
                    
                    // Iterar Racks por Lado (Cols)
                    for ($r = 1; $r <= $warehouse->cols; $r++) {
                        $rackCode = str_pad($r, 2, '0', STR_PAD_LEFT);
                        
                        // Generar ubicaciones para este rack
                        // Nota: En generación masiva NO borramos sobrantes automáticamente por rendimiento,
                        // asumimos que es una generación inicial o regeneración completa.
                        $locations = $this->createRackLogic($warehouse, $aisleCode, $side, $rackCode, $levelConfigs);
                        $createdCount += count($locations);
                    }
                }
            }

            DB::commit();
            return $createdCount;

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * EDICIÓN INDIVIDUAL: Configura un rack específico y LIMPIA lo que sobre.
     * Permite personalizar niveles y bines para un rack en particular.
     * * @param int $warehouseId
     * @param string $aisle Pasillo (Ej: '01' o '1')
     * @param string $side Lado ('A' o 'B')
     * @param string $rackCode Rack (Ej: '05' o '5')
     * @param array $levelConfigs Array de configuración de niveles
     */
    public function createRackStructure($warehouseId, $aisle, $side, $rackCode, $levelConfigs)
    {
        $warehouse = Warehouse::with('branch')->findOrFail($warehouseId);
        
        // 1. Estandarización de coordenadas para asegurar coincidencia con la DB
        $aislePad = str_pad($aisle, 2, '0', STR_PAD_LEFT);
        $rackPad = str_pad($rackCode, 2, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            // 2. Generar/Actualizar la estructura nueva
            $createdLocations = $this->createRackLogic($warehouse, $aislePad, $side, $rackPad, $levelConfigs);
            
            // Obtener los IDs que acabamos de confirmar como válidos
            $validIds = collect($createdLocations)->pluck('id')->toArray();

            // 3. LIMPIEZA: Eliminar (SoftDelete) ubicaciones que ya no existen en la nueva configuración
            // Buscamos todas las ubicaciones de ESTE rack específico
            Location::where('warehouse_id', $warehouse->id)
                ->where('aisle', $aislePad)
                ->where('side', $side)
                ->where('rack', $rackPad)
                ->whereNotIn('id', $validIds) // Excluimos los que acabamos de guardar
                ->delete();

            DB::commit();
            return $createdLocations;
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Lógica centralizada para crear las ubicaciones (Bines) de un rack dado.
     * Utiliza updateOrCreate para evitar duplicados si el código ya existe.
     */
    private function createRackLogic($warehouse, $aisle, $side, $rackCode, $levelConfigs)
    {
        $branchCode = $warehouse->branch->code ?? 'GEN';
        $whCode = $warehouse->code;
        $createdLocations = [];

        foreach ($levelConfigs as $config) {
            $levelNum = $config['level'];
            
            // Validar altura máxima permitida por la configuración de la bodega
            if ($levelNum > $warehouse->levels) continue;

            $levelCode = str_pad($levelNum, 2, '0', STR_PAD_LEFT);
            $binsCount = $config['bins_count'];
            $binTypeId = $config['bin_type_id'];

            for ($b = 1; $b <= $binsCount; $b++) {
                $binCode = str_pad($b, 2, '0', STR_PAD_LEFT);
                
                // Nomenclatura Única: SUC-BOD-PAS-LAD-RACK-NIV-BIN
                // Al incluir todos los componentes estandarizados, garantizamos unicidad lógica
                $fullCode = "{$branchCode}-{$whCode}-{$aisle}-{$side}-{$rackCode}-{$levelCode}-{$binCode}";

                // Buscamos por warehouse_id + code para encontrar el registro exacto
                $loc = Location::updateOrCreate(
                    [
                        'warehouse_id' => $warehouse->id,
                        'code'         => $fullCode
                    ],
                    [
                        'branch_id'    => $warehouse->branch_id ?? null,
                        'aisle'        => $aisle,
                        'side'         => $side,
                        'rack'         => $rackCode,
                        'level'        => $levelCode,
                        'position'     => $binCode, // Se guarda '01'
                        'bin_type_id'  => $binTypeId,
                        'type'         => 'storage',
                        'status'       => 'active'
                    ]
                );

                // Si usamos SoftDeletes y el registro estaba borrado, lo restauramos
                if (method_exists($loc, 'trashed') && $loc->trashed()) {
                    $loc->restore();
                }

                $createdLocations[] = $loc;
            }
        }
        return $createdLocations;
    }

    /**
     * Obtiene la estructura jerárquica para dibujar el mapa visualmente.
     */
    public function getWarehouseMapData($warehouseId)
    {
        $locations = Location::where('warehouse_id', $warehouseId)
            ->with(['binType', 'inventory']) 
            ->orderBy('aisle')
            ->orderBy('rack')
            ->orderBy('level', 'desc')
            ->orderBy('position')
            ->get();

        $map = [];

        foreach ($locations as $loc) {
            $aisle = $loc->aisle ?? '00';
            $side = $loc->side ?? 'U';
            $rack = $loc->rack ?? '00';
            
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