<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\BinType;
use App\Models\Location;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseManagementController extends Controller
{
    /**
     * Muestra la gestión de infraestructura y el mapa físico.
     * Esta ruta responde tanto al botón "Sucursales y Bodegas" como a "Mapa de Bodegas"
     * en el sidebar, diferenciándose por el parámetro ?view=map en la URL.
     */
    public function index(Request $request)
    {
        // 1. Cargar infraestructura completa con sus relaciones
        $branches = Branch::with('warehouses')->orderBy('name')->get();
        
        // 2. Cargar tipos de bines para la configuración dinámica en el mapa
        $binTypes = BinType::all();
        
        // 3. Cargar países de la base de datos para los selectores de creación/edición
        $countries = Country::orderBy('name')->get();
        
        // Retornamos la vista unificada
        return view('admin.inventory.map', compact('branches', 'binTypes', 'countries'));
    }

    /**
     * Módulo de Cobertura Geográfica: Configura las zonas que atiende cada sucursal.
     */
    public function coverage()
    {
        $branches = Branch::all();
        
        // IMPORTANTE: Cargamos países y estados para alimentar los selectores del modal de cobertura
        $countries = Country::orderBy('name')->get();
        $states = State::with('country')->orderBy('name')->get();

        return view('admin.inventory.coverage', compact('branches', 'countries', 'states'));
    }

    /**
     * Actualiza la configuración de cobertura geográfica de una sucursal.
     * MEJORADO: Persistencia robusta para asegurar el guardado de arrays JSON.
     */
    public function updateCoverage(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        
        $request->validate([
            'covered_countries' => 'nullable|array',
            'covered_states' => 'nullable|array',
            'can_export' => 'nullable'
        ]);

        // Sincronización explícita para evitar problemas de "hace que guarda pero no guarda"
        // Aseguramos que si no vienen datos, se guarde un array vacío en la DB
        $branch->covered_countries = $request->input('covered_countries', []);
        $branch->covered_states = $request->input('covered_states', []);
        
        // El checkbox de exportación se maneja por su presencia en el request
        $branch->can_export = $request->has('can_export');
        
        // Guardamos los cambios
        $branch->save();

        return back()->with('success', "Configuración de cobertura geográfica actualizada para {$branch->name}");
    }

    // =========================================================================
    // GESTIÓN DE SUCURSALES (BRANCHES)
    // =========================================================================

    public function storeBranch(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:100',
            'country' => 'required|string',
            'state'   => 'required|string',
            'city'    => 'required|string|max:100',
            'address' => 'nullable|string',
            'zip'     => 'nullable|string|max:20',
            'code'    => 'required|string|unique:branches,code|max:15'
        ]);
        
        $validated['is_active'] = true;
        $validated['covered_states'] = []; 
        $validated['covered_countries'] = [];

        Branch::create($validated);

        return back()->with('success', 'Sucursal registrada correctamente.');
    }

    public function updateBranch(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        
        $validated = $request->validate([
            'name'    => 'required|string|max:100',
            'country' => 'required|string',
            'state'   => 'required|string',
            'city'    => 'required|string|max:100',
            'address' => 'nullable|string',
            'zip'     => 'nullable|string|max:20',
            'code'    => 'required|string|max:15|unique:branches,code,' . $branch->id 
        ]);
        
        $branch->update($validated);
        return back()->with('success', 'Datos de la sucursal actualizados correctamente.');
    }

    public function destroyBranch($id)
    {
        $branch = Branch::findOrFail($id);
        $branch->delete(); 
        return back()->with('success', 'Sucursal eliminada del sistema.');
    }

    // =========================================================================
    // GESTIÓN DE BODEGAS (WAREHOUSES)
    // =========================================================================

    public function storeWarehouse(Request $request)
    {
        try {
            $validated = $request->validate([
                'branch_id' => 'required|exists:branches,id',
                'name'      => 'required|string|max:100',
                'code'      => 'required|string|unique:warehouses,code|max:15',
                'rows'      => 'required|integer|min:1|max:100',
                'cols'      => 'required|integer|min:1|max:100',
                'levels'    => 'required|integer|min:1|max:20'
            ]);
            
            Warehouse::create($validated);
            return back()->with('success', 'Bodega creada exitosamente.');

        } catch (\Exception $e) {
            Log::error("Error al crear bodega: " . $e->getMessage());
            return back()->withErrors(['error' => 'Error técnico al crear bodega.'])->withInput();
        }
    }

    public function updateWarehouse(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        
        $validated = $request->validate([
            'name'   => 'required|string|max:100',
            'rows'   => 'required|integer|min:1|max:100',
            'cols'   => 'required|integer|min:1|max:100',
            'levels' => 'required|integer|min:1|max:20'
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

    // =========================================================================
    // LÓGICA TÉCNICA: MAPA FÍSICO Y BINES (AJAX)
    // =========================================================================

    public function getRackDetails(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'aisle' => 'required|integer',
            'side' => 'required|string',
            'rack_col' => 'required|integer'
        ]);

        $locations = Location::where('warehouse_id', $request->warehouse_id)
            ->where('aisle', $request->aisle)
            ->where('side', $request->side)
            ->where('rack', $request->rack_col)
            ->orderBy('shelf') // Nivel
            ->orderBy('bin')   // Posición
            ->get();

        // Agrupar por nivel para determinar configuración
        $levels = [];
        foreach ($locations as $loc) {
            $lvl = $loc->shelf;
            if (!isset($levels[$lvl])) {
                $levels[$lvl] = [
                    'bins_count' => 0,
                    'bin_type_id' => $loc->bin_type_id
                ];
            }
            // Determinamos el número máximo de bin en este nivel
            $levels[$lvl]['bins_count'] = max($levels[$lvl]['bins_count'], $loc->bin);
        }

        return response()->json(['levels' => $levels]);
    }

    public function saveRack(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'aisle' => 'required|integer',
            'side' => 'required|in:A,B',
            'rack_col' => 'required|integer',
            'levels' => 'required|array'
        ]);

        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);

        DB::beginTransaction();
        try {
            // ID's de ubicaciones que mantendremos/actualizaremos
            $processedLocationIds = [];

            // Procesar cada nivel enviado desde el frontend
            foreach ($validated['levels'] as $levelConfig) {
                $levelNum = $levelConfig['level'];
                $binsCount = $levelConfig['bins_count'];
                $binTypeId = $levelConfig['bin_type_id'] ?? null;

                for ($b = 1; $b <= $binsCount; $b++) {
                    // Generar código de ubicación estandarizado: WH-P01-A-R01-N01-B01
                    // Usamos str_pad para asegurar dos dígitos
                    $code = sprintf(
                        "%s-P%02d-%s-R%02d-N%02d-B%02d",
                        $warehouse->code,
                        $validated['aisle'],
                        $validated['side'],
                        $validated['rack_col'],
                        $levelNum,
                        $b
                    );

                    // Buscar si existe (para mantener historial/ID) o crear
                    $location = Location::updateOrCreate(
                        [
                            'warehouse_id' => $warehouse->id,
                            'code' => $code
                        ],
                        [
                            'branch_id' => $warehouse->branch_id,
                            'aisle' => $validated['aisle'],
                            'side' => $validated['side'],
                            'rack' => $validated['rack_col'],
                            'shelf' => $levelNum,
                            'bin' => $b,
                            'bin_type_id' => $binTypeId,
                            'type' => 'picking', // Default: picking
                            'status' => 'active' // Default: active
                        ]
                    );
                    
                    $processedLocationIds[] = $location->id;
                }
            }

            // Eliminar ubicaciones que ya no existen en la nueva configuración para este rack específico
            // (Ej: Si antes el nivel 1 tenía 5 bines y ahora se configuran 3, borramos los 2 sobrantes)
            Location::where('warehouse_id', $warehouse->id)
                ->where('aisle', $validated['aisle'])
                ->where('side', $validated['side'])
                ->where('rack', $validated['rack_col'])
                ->whereNotIn('id', $processedLocationIds)
                ->delete();

            DB::commit();
            return response()->json(['message' => 'Configuración guardada exitosamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error guardando rack: " . $e->getMessage());
            return response()->json(['message' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Genera la lista de etiquetas para impresión masiva de una bodega.
     */
    public function printLabels($id)
    {
        $warehouse = Warehouse::with(['branch', 'locations.binType'])->findOrFail($id);
        $labels = [];

        // Etiqueta Principal de Bodega
        $labels[] = [
            'type' => 'WAREHOUSE',
            'title' => $warehouse->name,
            'subtitle' => $warehouse->branch->name,
            'code' => $warehouse->code,
            'qr_data' => $warehouse->code
        ];

        // Agrupar ubicaciones para generar etiquetas jerárquicas
        $aisles = $warehouse->locations->groupBy('aisle');
        
        foreach ($aisles as $aisleNum => $locsInAisle) {
            // Etiqueta de Pasillo
            $labels[] = [
                'type' => 'AISLE',
                'title' => "PASILLO {$aisleNum}",
                'subtitle' => $warehouse->name,
                'code' => "P" . str_pad($aisleNum, 2, '0', STR_PAD_LEFT),
                'qr_data' => $warehouse->code . "-P" . str_pad($aisleNum, 2, '0', STR_PAD_LEFT)
            ];

            $racks = $locsInAisle->groupBy('rack');
            foreach ($racks as $rackNum => $locsInRack) {
                // Etiqueta de Rack
                $labels[] = [
                    'type' => 'RACK',
                    'title' => "RACK {$rackNum}",
                    'subtitle' => "PASILLO {$aisleNum}",
                    'code' => "R" . str_pad($rackNum, 2, '0', STR_PAD_LEFT),
                    'qr_data' => $warehouse->code . "-P" . str_pad($aisleNum, 2, '0', STR_PAD_LEFT) . "-R" . str_pad($rackNum, 2, '0', STR_PAD_LEFT)
                ];

                // Etiquetas de Bin individuales
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