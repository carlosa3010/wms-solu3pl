<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\BinType;
use App\Models\Location;
use App\Models\Country;
use App\Models\State;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseManagementController extends Controller
{
    // =========================================================================
    // VISTAS PRINCIPALES
    // =========================================================================

    public function index(Request $request)
    {
        $branches = Branch::with('warehouses')->orderBy('name')->get();
        $binTypes = BinType::all();
        $countries = Country::orderBy('name')->get();
        
        return view('admin.inventory.map', compact('branches', 'binTypes', 'countries'));
    }

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
    // MÉTODOS DISPATCHER (Resuelven el error BadMethodCallException)
    // =========================================================================
    
    /**
     * Redirige la petición de actualización al método correcto según la URL o datos.
     */
    public function update(Request $request, $id)
    {
        // Si la URL contiene 'branches' o el request tiene datos de sucursal
        if ($request->is('*/branches/*') || $request->has('country')) {
            return $this->updateBranch($request, $id);
        }
        // Por defecto asumimos bodega si no es sucursal
        return $this->updateWarehouse($request, $id);
    }

    /**
     * Redirige la petición de eliminación al método correcto.
     */
    public function destroy(Request $request, $id)
    {
        if ($request->is('*/branches/*')) {
            return $this->destroyBranch($id);
        }
        return $this->destroyWarehouse($id);
    }

    // =========================================================================
    // GESTIÓN DE SUCURSALES (Lógica Específica)
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
        // Validar dependencias si es necesario
        $branch->delete(); 
        return back()->with('success', 'Sucursal eliminada del sistema.');
    }

    // =========================================================================
    // GESTIÓN DE BODEGAS (Lógica Específica)
    // =========================================================================

    public function storeWarehouse(Request $request)
    {
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
        
        // Validación de stock antes de eliminar
        $hasStock = Location::where('warehouse_id', $warehouse->id)
            ->whereHas('inventory', function($q) {
                $q->where('quantity', '>', 0);
            })->exists();

        if ($hasStock) {
             return back()->withErrors(['error' => 'No se puede eliminar la bodega porque tiene stock activo.']);
        }
        
        $warehouse->delete();
        return back()->with('success', 'Bodega eliminada correctamente.');
    }

    // =========================================================================
    // LÓGICA TÉCNICA: MAPA FÍSICO Y UBICACIONES (BINES)
    // =========================================================================

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

            // Filtro por Código: BOD-P01-A-R10...
            $aislePad = str_pad($request->aisle, 2, '0', STR_PAD_LEFT);
            $rackPad  = str_pad($request->rack_col, 2, '0', STR_PAD_LEFT);
            $side     = $request->side;

            // Patrón exacto para filtrar el lado
            $searchCode = "{$warehouse->code}-P{$aislePad}-{$side}-R{$rackPad}-";

            // Usamos LIKE en 'code' porque la columna 'side' no existe
            $locations = Location::where('warehouse_id', $warehouse->id)
                ->where('aisle', $request->aisle)
                ->where('rack', $request->rack_col)
                ->where('code', 'LIKE', $searchCode . '%') 
                ->orderBy('shelf', 'asc') 
                ->orderBy('bin', 'asc')
                ->get();

            if ($locations->isEmpty()) {
                return response()->json(['status' => 'empty', 'levels' => []]);
            }

            // Reconstruir estructura
            $levelsRaw = $locations->groupBy('shelf');
            $levels = [];

            foreach ($levelsRaw as $shelfNum => $locs) {
                $firstLoc = $locs->first();
                $maxBin = $locs->max('bin');

                $levels[] = [
                    'level'       => (int)$shelfNum,
                    'bins_count'  => (int)$maxBin,
                    'bin_type_id' => $firstLoc->bin_type_id
                ];
            }

            usort($levels, fn($a, $b) => $a['level'] <=> $b['level']);

            return response()->json(['status' => 'success', 'levels' => $levels]);

        } catch (\Exception $e) {
            Log::error('Error getRackDetails: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error cargando datos'], 500);
        }
    }

    public function saveRack(Request $request)
    {
        $validated = $request->validate([
            'warehouse_id'        => 'required|exists:warehouses,id',
            'aisle'               => 'required',
            'side'                => 'required|in:A,B',
            'rack_col'            => 'required',
            'levels'              => 'required|array'
        ]);

        $warehouse = Warehouse::findOrFail($validated['warehouse_id']);

        // Validar límite físico
        if (count($validated['levels']) > $warehouse->levels) {
            return response()->json(['error' => "La bodega permite máximo {$warehouse->levels} niveles."], 422);
        }

        DB::beginTransaction();
        try {
            $processedLocationIds = [];

            foreach ($validated['levels'] as $levelConfig) {
                $shelfNum   = $levelConfig['level'];
                $binsCount  = $levelConfig['bins_count'];
                $binTypeId  = $levelConfig['bin_type_id'] ?? null;

                for ($b = 1; $b <= $binsCount; $b++) {
                    // NOMENCLATURA: BOD-P01-A-R01-N01-B01
                    $aislePad = str_pad($validated['aisle'], 2, '0', STR_PAD_LEFT);
                    $rackPad  = str_pad($validated['rack_col'], 2, '0', STR_PAD_LEFT);
                    $shelfPad = str_pad($shelfNum, 2, '0', STR_PAD_LEFT);
                    $binPad   = str_pad($b, 2, '0', STR_PAD_LEFT);
                    
                    $code = "{$warehouse->code}-P{$aislePad}-{$validated['side']}-R{$rackPad}-N{$shelfPad}-B{$binPad}";

                    $location = Location::updateOrCreate(
                        [
                            'warehouse_id' => $warehouse->id,
                            'code'         => $code
                        ],
                        [
                            'branch_id'   => $warehouse->branch_id,
                            'aisle'       => $validated['aisle'],
                            'rack'        => $validated['rack_col'],
                            'shelf'       => $shelfNum, 
                            'bin'         => $b,
                            'bin_type_id' => $binTypeId,
                            'type'        => 'picking',
                            'status'      => 'active'
                        ]
                    );
                    
                    $processedLocationIds[] = $location->id;
                }
            }

            // Limpieza usando el mismo patrón de código
            $aislePad = str_pad($validated['aisle'], 2, '0', STR_PAD_LEFT);
            $rackPad  = str_pad($validated['rack_col'], 2, '0', STR_PAD_LEFT);
            $searchCode = "{$warehouse->code}-P{$aislePad}-{$validated['side']}-R{$rackPad}-";

            $locationsToDelete = Location::where('warehouse_id', $warehouse->id)
                ->where('aisle', $validated['aisle'])
                ->where('rack', $validated['rack_col'])
                ->where('code', 'LIKE', $searchCode . '%') 
                ->whereNotIn('id', $processedLocationIds)
                ->get();

            foreach ($locationsToDelete as $loc) {
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

    public function printLabels($id)
    {
        $warehouse = Warehouse::with(['branch', 'locations.binType'])->findOrFail($id);
        $labels = [];

        // 1. Bodega
        $labels[] = [
            'type' => 'WAREHOUSE',
            'title' => $warehouse->name,
            'subtitle' => $warehouse->branch->name,
            'code' => $warehouse->code,
            'qr_data' => $warehouse->code
        ];

        // 2. Ubicaciones (Corregido ordenamiento para evitar error SQL de columna 'bin')
        $locations = $warehouse->locations()
            ->orderBy('aisle')
            ->orderBy('rack')
            ->orderBy('shelf')
            ->orderBy('code') // Ordenamos por código como fallback seguro
            ->get();

        $aisles = $locations->groupBy('aisle');
        
        foreach ($aisles as $aisleNum => $locsInAisle) {
            // Pasillo
            $labels[] = [
                'type' => 'AISLE',
                'title' => "PASILLO {$aisleNum}",
                'subtitle' => $warehouse->name,
                'code' => "P" . str_pad($aisleNum, 2, '0', STR_PAD_LEFT),
                'qr_data' => $warehouse->code . "-P" . str_pad($aisleNum, 2, '0', STR_PAD_LEFT)
            ];

            $racks = $locsInAisle->groupBy('rack');
            foreach ($racks as $rackNum => $locsInRack) {
                // Rack
                $labels[] = [
                    'type' => 'RACK',
                    'title' => "RACK {$rackNum}",
                    'subtitle' => "PASILLO {$aisleNum}",
                    'code' => "R" . str_pad($rackNum, 2, '0', STR_PAD_LEFT),
                    'qr_data' => $warehouse->code . "-P" . str_pad($aisleNum, 2, '0', STR_PAD_LEFT) . "-R" . str_pad($rackNum, 2, '0', STR_PAD_LEFT)
                ];

                // Bines
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