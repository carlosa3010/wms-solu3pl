<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\BinType;
use App\Models\Location;
use App\Models\Country;
use App\Models\State;
use App\Models\Inventory; // Importante para validar stock antes de borrar bines
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseManagementController extends Controller
{
    /**
     * Muestra la gestión de infraestructura y el mapa físico.
     */
    public function index(Request $request)
    {
        $branches = Branch::with('warehouses')->orderBy('name')->get();
        $binTypes = BinType::all();
        $countries = Country::orderBy('name')->get();
        
        return view('admin.inventory.map', compact('branches', 'binTypes', 'countries'));
    }

    /**
     * Módulo de Cobertura Geográfica.
     */
    public function coverage()
    {
        $branches = Branch::all();
        $countries = Country::orderBy('name')->get();
        $states = State::with('country')->orderBy('name')->get();

        return view('admin.inventory.coverage', compact('branches', 'countries', 'states'));
    }

    public function updateCoverage(Request $request, $id)
    {
        $branch = Branch::findOrFail($id);
        
        $request->validate([
            'covered_countries' => 'nullable|array',
            'covered_states' => 'nullable|array',
            'can_export' => 'nullable'
        ]);

        $branch->covered_countries = $request->input('covered_countries', []);
        $branch->covered_states = $request->input('covered_states', []);
        $branch->can_export = $request->has('can_export');
        
        $branch->save();

        return back()->with('success', "Configuración de cobertura actualizada para {$branch->name}");
    }

    // =========================================================================
    // GESTIÓN DE SUCURSALES
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
    // GESTIÓN DE BODEGAS
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
    // LÓGICA TÉCNICA: MAPA FÍSICO Y UBICACIONES (BINES)
    // =========================================================================

    /**
     * Obtiene la configuración actual de un rack.
     * Lee la tabla locations y filtra por el código para distinguir Lado A/B.
     */
    public function getRackDetails(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'aisle'        => 'required',
            'side'         => 'required',
            'rack_col'     => 'required'
        ]);

        try {
            $warehouse = Warehouse::findOrFail($request->warehouse_id);

            // Construir patrón de búsqueda para el CÓDIGO porque no existe columna 'side'.
            // Patrón: %-P{pasillo}-{lado}-R{rack}-%
            // Ejemplo: %-P01-A-R05-%
            $aislePad = str_pad($request->aisle, 2, '0', STR_PAD_LEFT);
            $rackPad  = str_pad($request->rack_col, 2, '0', STR_PAD_LEFT);
            $side     = $request->side;
            
            // Usamos un patrón robusto para evitar coincidencias parciales erróneas
            $searchPattern = "%-P{$aislePad}-{$side}-R{$rackPad}-%";

            $locations = Location::where('warehouse_id', $warehouse->id)
                ->where('aisle', $request->aisle)
                ->where('rack', $request->rack_col)
                ->where('code', 'LIKE', $searchPattern) // Filtro crucial por lado
                ->orderBy('shelf', 'asc') // Ordenar por nivel (shelf)
                ->orderBy('bin', 'asc')   // Ordenar por posición (bin)
                ->get();

            if ($locations->isEmpty()) {
                return response()->json([
                    'status' => 'empty',
                    'levels' => []
                ]);
            }

            // Agrupar por nivel (shelf)
            $levelsRaw = $locations->groupBy('shelf');
            $levels = [];

            foreach ($levelsRaw as $shelfNum => $locs) {
                // Tomar configuración del primer bin del nivel
                $firstLoc = $locs->first();
                // Calcular cantidad de bines (máximo número de bin encontrado)
                $maxBin = $locs->max('bin');

                $levels[] = [
                    'level'       => (int)$shelfNum,
                    'bins_count'  => (int)$maxBin,
                    'bin_type_id' => $firstLoc->bin_type_id
                ];
            }

            // Ordenar niveles ascendentemente
            usort($levels, fn($a, $b) => $a['level'] <=> $b['level']);

            return response()->json([
                'status' => 'success',
                'levels' => $levels
            ]);

        } catch (\Exception $e) {
            Log::error('Error getRackDetails: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error cargando datos'], 500);
        }
    }

    /**
     * Guarda la estructura del rack generando nomenclaturas.
     */
    public function saveRack(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id'        => 'required|exists:warehouses,id',
            'aisle'               => 'required',
            'side'                => 'required|in:A,B',
            'rack_col'            => 'required',
            'levels'              => 'required|array',
            'levels.*.level'      => 'required|integer',
            'levels.*.bins_count' => 'required|integer|min:1',
            'levels.*.bin_type_id'=> 'nullable|exists:bin_types,id',
        ]);

        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);

        // Validar límite de niveles físicos de la bodega
        if (count($validated['levels']) > $warehouse->levels) {
            return response()->json([
                'error' => "La bodega permite máximo {$warehouse->levels} niveles."
            ], 422);
        }

        DB::beginTransaction();
        try {
            $processedLocationIds = [];

            foreach ($validated['levels'] as $levelConfig) {
                $shelfNum   = $levelConfig['level'];
                $binsCount  = $levelConfig['bins_count'];
                $binTypeId  = $levelConfig['bin_type_id'] ?? null;

                for ($b = 1; $b <= $binsCount; $b++) {
                    // Generar Código: CODIGO-P01-A-R01-N01-B01
                    // Usamos el 'side' aquí para generar el string único
                    $code = sprintf(
                        "%s-P%02d-%s-R%02d-N%02d-B%02d",
                        $warehouse->code,
                        $validated['aisle'],
                        $validated['side'], // Aquí sí usamos el lado para el string
                        $validated['rack_col'],
                        $shelfNum,
                        $b
                    );

                    // Guardamos en la DB. NO incluimos la columna 'side'.
                    $location = Location::updateOrCreate(
                        [
                            'warehouse_id' => $warehouse->id,
                            'code'         => $code // Llave única lógica
                        ],
                        [
                            'branch_id'   => $warehouse->branch_id,
                            'aisle'       => $validated['aisle'],
                            // NO guardamos 'side' porque la columna no existe en locations
                            'rack'        => $validated['rack_col'],
                            'shelf'       => $shelfNum, 
                            'bin'         => $b,
                            'bin_type_id' => $binTypeId,
                            'type'        => 'picking', // Valor por defecto
                            'status'      => 'active'   // Valor por defecto
                        ]
                    );
                    
                    $processedLocationIds[] = $location->id;
                }
            }

            // Limpieza: Borrar solo bines de ESTE lado y ESTE rack que ya no se usen
            $aislePad = str_pad($validated['aisle'], 2, '0', STR_PAD_LEFT);
            $rackPad  = str_pad($validated['rack_col'], 2, '0', STR_PAD_LEFT);
            $side     = $validated['side'];
            $searchPattern = "%-P{$aislePad}-{$side}-R{$rackPad}-%";

            $locationsToDelete = Location::where('warehouse_id', $warehouse->id)
                ->where('aisle', $validated['aisle'])
                ->where('rack', $validated['rack_col'])
                ->where('code', 'LIKE', $searchPattern) // Filtramos por código para no borrar el otro lado
                ->whereNotIn('id', $processedLocationIds)
                ->get();

            foreach ($locationsToDelete as $loc) {
                // Verificar Stock
                if (Inventory::where('location_id', $loc->id)->where('quantity', '>', 0)->exists()) {
                    DB::rollBack();
                    return response()->json(['error' => "Ubicación {$loc->code} tiene stock."], 422);
                }
                $loc->delete();
            }

            DB::commit();
            return response()->json(['message' => 'Guardado correctamente']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error saving rack: " . $e->getMessage());
            return response()->json(['message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Genera etiquetas para impresión.
     */
    public function printLabels($id)
    {
        $warehouse = Warehouse::with(['branch', 'locations.binType'])->findOrFail($id);
        $labels = [];

        $labels[] = [
            'type' => 'WAREHOUSE',
            'title' => $warehouse->name,
            'subtitle' => $warehouse->branch->name,
            'code' => $warehouse->code,
            'qr_data' => $warehouse->code
        ];

        // Ordenar ubicaciones para impresión
        $locations = $warehouse->locations()
            ->orderBy('aisle')
            ->orderBy('code') // Ordenar por código agrupa implícitamente por lado (A antes que B)
            ->get();

        $aisles = $locations->groupBy('aisle');
        
        foreach ($aisles as $aisleNum => $locsInAisle) {
            // Etiqueta Pasillo
            $labels[] = [
                'type' => 'AISLE',
                'title' => "PASILLO {$aisleNum}",
                'subtitle' => $warehouse->name,
                'code' => "P" . str_pad($aisleNum, 2, '0', STR_PAD_LEFT),
                'qr_data' => $warehouse->code . "-P" . str_pad($aisleNum, 2, '0', STR_PAD_LEFT)
            ];

            // Agrupar por Rack dentro del Pasillo
            $racks = $locsInAisle->groupBy('rack');
            
            foreach ($racks as $rackNum => $locsInRack) {
                // Como un rack puede tener lado A y B, y queremos etiquetas separadas si es necesario,
                // verificamos los lados presentes en este rack analizando los códigos.
                // Sin embargo, físicamente el Rack es la estructura. Generamos etiqueta por Rack físico.
                
                $labels[] = [
                    'type' => 'RACK',
                    'title' => "RACK {$rackNum}",
                    'subtitle' => "PASILLO {$aisleNum}",
                    'code' => "R" . str_pad($rackNum, 2, '0', STR_PAD_LEFT),
                    'qr_data' => $warehouse->code . "-P" . str_pad($aisleNum, 2, '0', STR_PAD_LEFT) . "-R" . str_pad($rackNum, 2, '0', STR_PAD_LEFT)
                ];

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