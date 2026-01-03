<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\BinType;
use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseManagementController extends Controller
{
    /**
     * Lista maestra de estados de Venezuela para la inteligencia geográfica.
     */
    const VENEZUELA_STATES = [
        'Amazonas', 'Anzoátegui', 'Apure', 'Aragua', 'Barinas', 'Bolívar', 
        'Carabobo', 'Cojedes', 'Delta Amacuro', 'Distrito Capital', 'Falcón', 
        'Guárico', 'Lara', 'Mérida', 'Miranda', 'Monagas', 'Nueva Esparta', 
        'Portuguesa', 'Sucre', 'Táchira', 'Trujillo', 'Vargas', 'Yaracuy', 'Zulia'
    ];

    /**
     * Muestra el mapa de infraestructura y la gestión principal de sucursales.
     */
    public function index()
    {
        // Cargamos todas las sucursales con sus bodegas para la navegación lateral
        $branches = Branch::with('warehouses')->orderBy('name')->get();
        $binTypes = BinType::all();
        
        return view('admin.inventory.map', compact('branches', 'binTypes'));
    }

    /**
     * Muestra el módulo de configuración de Cobertura Geográfica.
     */
    public function coverage()
    {
        $branches = Branch::all();
        $states = self::VENEZUELA_STATES;

        return view('admin.inventory.coverage', compact('branches', 'states'));
    }

    /**
     * Actualiza la cobertura de estados y permisos de exportación de una sede.
     */
    public function updateCoverage(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        
        $request->validate([
            'covered_states' => 'nullable|array',
            'can_export' => 'nullable|boolean'
        ]);

        $branch->update([
            'covered_states' => $request->covered_states ?? [],
            'can_export' => $request->has('can_export') // Determinado por la presencia del check
        ]);

        return back()->with('success', 'Configuración de cobertura geográfica actualizada para ' . $branch->name);
    }

    // ==========================================
    // GESTIÓN DE SUCURSALES (BRANCHES)
    // ==========================================

    public function storeBranch(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string',
            'code' => 'required|string|unique:branches,code|max:10'
        ]);
        
        $validated['is_active'] = true;
        Branch::create($validated);

        return back()->with('success', 'Sucursal registrada correctamente.');
    }

    public function updateBranch(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address' => 'nullable|string',
            'code' => 'required|string|max:10|unique:branches,code,' . $branch->id 
        ]);
        
        $branch->update($validated);
        return back()->with('success', 'Datos de la sucursal actualizados.');
    }

    public function destroyBranch($id)
    {
        $branch = Branch::findOrFail($id);
        // Por cascada de base de datos se eliminan bodegas y bines
        $branch->delete(); 
        return back()->with('success', 'Sucursal eliminada del sistema.');
    }

    // ==========================================
    // GESTIÓN DE BODEGAS (WAREHOUSES)
    // ==========================================

    public function storeWarehouse(Request $request)
    {
        try {
            $validated = $request->validate([
                'branch_id' => 'required|exists:branches,id',
                'name'      => 'required|string|max:100',
                'code'      => 'required|string|unique:warehouses,code|max:15',
                'rows'      => 'required|integer|min:1|max:100',
                'cols'      => 'required|integer|min:1|max:100',
                'levels'    => 'required|integer|min:1|max:20',
                'bin_size'  => 'nullable|string|max:50'
            ]);
            
            Warehouse::create($validated);
            
            return back()->with('success', 'Bodega creada exitosamente.');

        } catch (\Exception $e) {
            Log::error("Error al crear bodega: " . $e->getMessage());
            return back()->withErrors(['error' => 'No se pudo crear la bodega: ' . $e->getMessage()])->withInput();
        }
    }

    public function updateWarehouse(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        
        $validated = $request->validate([
            'name'     => 'required|string|max:100',
            'rows'     => 'required|integer|min:1|max:100',
            'cols'     => 'required|integer|min:1|max:100',
            'levels'   => 'required|integer|min:1|max:20',
            'bin_size' => 'nullable|string|max:50'
        ]);
        
        $warehouse->update($validated);
        return back()->with('success', 'Configuración de la bodega actualizada.');
    }

    public function destroyWarehouse($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        $warehouse->delete();
        return back()->with('success', 'Bodega eliminada correctamente.');
    }

    // ==========================================
    // LÓGICA DE RACKS Y BINES (INTERACCIÓN MAPA)
    // ==========================================

    /**
     * AJAX: Obtiene la configuración de bines de un rack específico.
     */
    public function getRackDetails(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'aisle' => 'required|integer',
            'side' => 'required|string',
            'rack_col' => 'required|integer'
        ]);

        // El patrón busca coincidencias en el código para determinar bines del mismo rack
        $searchPattern = sprintf("-P%02d-%s-R%02d-", $request->aisle, $request->side, $request->rack_col);
        
        $locations = Location::where('warehouse_id', $request->warehouse_id)
            ->where('code', 'like', "%{$searchPattern}%")
            ->get();

        $levelsConfig = [];
        foreach ($locations as $loc) {
            $level = $loc->shelf;
            if (!isset($levelsConfig[$level])) {
                $levelsConfig[$level] = [
                    'level' => $level,
                    'bins_count' => 0,
                    'bin_type_id' => $loc->bin_type_id
                ];
            }
            $levelsConfig[$level]['bins_count']++;
        }

        return response()->json(['levels' => (object)$levelsConfig]);
    }

    /**
     * AJAX: Guarda o actualiza los bines de un rack generado desde el mapa.
     */
    public function saveRackDetails(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'aisle' => 'required|integer',
            'side' => 'required|string',
            'rack_col' => 'required|integer',
            'levels_config' => 'required|array'
        ]);

        $wh = Warehouse::find($request->warehouse_id);

        try {
            DB::transaction(function () use ($request, $wh) {
                $rackIdentifier = sprintf("-P%02d-%s-R%02d-", $request->aisle, $request->side, $request->rack_col);
                
                // Borrar bines antiguos de esta columna específica para regenerar según nueva config
                Location::where('warehouse_id', $wh->id)
                    ->where('code', 'like', "%{$rackIdentifier}%")
                    ->delete();

                foreach ($request->levels_config as $config) {
                    $level = $config['level'];
                    $qty = (int)$config['bins_count'];
                    $typeId = $config['bin_type_id'] ?? null;

                    for ($b = 1; $b <= $qty; $b++) {
                        // Generar código estandarizado: WH-P01-A-R01-N01-B01
                        $locCode = sprintf(
                            "%s-P%02d-%s-R%02d-N%02d-B%02d",
                            $wh->code, $request->aisle, $request->side, $request->rack_col, $level, $b
                        );

                        Location::create([
                            'warehouse_id' => $wh->id,
                            'code' => $locCode,
                            'aisle' => $request->aisle,
                            'rack' => $request->rack_col,
                            'shelf' => $level,
                            'bin_type_id' => $typeId,
                            'type' => 'rack'
                        ]);
                    }
                }
            });
            return response()->json(['success' => true, 'message' => 'Configuración de rack guardada con éxito.']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Genera la lista de etiquetas para impresión masiva de una bodega.
     */
    public function printLabels($id)
    {
        $warehouse = Warehouse::with(['branch', 'locations.binType'])->findOrFail($id);
        
        $labels = [];

        // 1. Etiqueta de la Bodega (Raíz)
        $labels[] = [
            'type' => 'WAREHOUSE',
            'title' => $warehouse->name,
            'subtitle' => $warehouse->branch->name,
            'code' => $warehouse->code,
            'qr_data' => $warehouse->code
        ];

        // 2. Agrupación por jerarquía para etiquetas de Pasillo y Rack
        $aisles = $warehouse->locations->groupBy('aisle');

        foreach ($aisles as $aisleNum => $locsInAisle) {
            $labels[] = [
                'type' => 'AISLE',
                'title' => "PASILLO {$aisleNum}",
                'subtitle' => $warehouse->name,
                'code' => "P" . str_pad($aisleNum, 2, '0', STR_PAD_LEFT),
                'qr_data' => $warehouse->code . "-P" . str_pad($aisleNum, 2, '0', STR_PAD_LEFT)
            ];

            $racks = $locsInAisle->groupBy('rack');
            foreach ($racks as $rackNum => $locsInRack) {
                $labels[] = [
                    'type' => 'RACK',
                    'title' => "RACK {$rackNum}",
                    'subtitle' => "PASILLO {$aisleNum}",
                    'code' => "R" . str_pad($rackNum, 2, '0', STR_PAD_LEFT),
                    'qr_data' => $warehouse->code . "-P" . str_pad($aisleNum, 2, '0', STR_PAD_LEFT) . "-R" . str_pad($rackNum, 2, '0', STR_PAD_LEFT)
                ];

                // 3. Etiquetas individuales por cada BIN final
                foreach ($locsInRack as $bin) {
                    $labels[] = [
                        'type' => 'BIN',
                        'title' => $bin->code,
                        'subtitle' => $bin->binType ? $bin->binType->name : 'Estándar',
                        'code' => $bin->code,
                        'qr_data' => $bin->code
                    ];
                }
            }
        }

        return view('admin.inventory.print_warehouse_labels', compact('warehouse', 'labels'));
    }
}