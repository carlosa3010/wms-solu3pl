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
use App\Models\User;
use App\Models\Order;
use App\Services\WarehouseLayoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseManagementController extends Controller
{
    protected $layoutService;

    public function __construct(WarehouseLayoutService $layoutService)
    {
        $this->layoutService = $layoutService;
    }

    // =========================================================================
    // 1. UTILIDADES Y AJAX
    // =========================================================================

    public function getStates($countryId)
    {
        $states = State::where('country_id', $countryId)->orderBy('name')->get();
        return response()->json($states);
    }

    // =========================================================================
    // 2. VISTAS PRINCIPALES
    // =========================================================================

    public function index(Request $request)
    {
        $branches = Branch::with('warehouses')->where('is_active', true)->orderBy('name')->get();
        $binTypes = BinType::all();
        $countries = Country::orderBy('name')->get();
        
        $selectedWarehouseId = $request->query('warehouse_id');
        $selectedWarehouse = null;
        
        if ($selectedWarehouseId) {
            $selectedWarehouse = Warehouse::find($selectedWarehouseId);
        }
        
        return view('admin.inventory.map', compact('branches', 'binTypes', 'countries', 'selectedWarehouse'));
    }

    public function coverage()
    {
        $branches = Branch::where('is_active', true)->get();
        $countries = Country::orderBy('name')->get();
        $states = State::with('country')->orderBy('country_id')->orderBy('name')->get();

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

        return back()->with('success', "Cobertura actualizada para {$branch->name}");
    }

    // =========================================================================
    // 3. DISPATCHER
    // =========================================================================
    
    public function store(Request $request)
    {
        if ($request->has('country') || $request->has('city')) {
            return $this->storeBranch($request);
        }
        return $this->storeWarehouse($request);
    }

    public function update(Request $request, $id)
    {
        if ($request->is('*/branches/*') || $request->has('country')) {
            return $this->updateBranch($request, $id);
        }
        return $this->updateWarehouse($request, $id);
    }

    public function destroy(Request $request, $id)
    {
        if ($request->is('*/branches/*')) {
            return $this->destroyBranch($id);
        }
        return $this->destroyWarehouse($id);
    }

    // =========================================================================
    // 4. GESTIÓN DE SUCURSALES
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
            'code'    => 'required|string|unique:branches,code|max:15',
            'phone'   => 'nullable|string',
            'email'   => 'nullable|email'
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
            'code'    => 'required|string|max:15|unique:branches,code,' . $branch->id,
            'phone'   => 'nullable|string',
            'email'   => 'nullable|email'
        ]);
        
        $branch->update($validated);
        return back()->with('success', 'Datos de la sucursal actualizados.');
    }

    public function destroyBranch($id)
    {
        $branch = Branch::findOrFail($id);
        
        if (User::where('branch_id', $branch->id)->exists()) {
            return back()->with('error', 'No se puede eliminar: Hay usuarios asignados.');
        }
        if (Order::where('branch_id', $branch->id)->whereNotIn('status', ['cancelled', 'completed'])->exists()) {
            return back()->with('error', 'No se puede eliminar: Hay órdenes activas.');
        }
        
        $branch->delete(); 
        return back()->with('success', 'Sucursal eliminada del sistema.');
    }

    // =========================================================================
    // 5. GESTIÓN DE BODEGAS
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
        
        try {
            DB::beginTransaction();

            // 1. Crear Bodega
            $warehouse = Warehouse::create($validated);

            // 2. Crear Zona de Recepción (Inbound)
            $recepcion = new Location();
            $recepcion->warehouse_id = $warehouse->id;
            $recepcion->code = 'RECEPCION';
            $recepcion->type = 'staging';
            $recepcion->is_blocked = false;
            $recepcion->aisle = null;
            $recepcion->rack = null;
            $recepcion->level = null;
            $recepcion->position = null;
            $recepcion->save();
            
            // 3. Crear Zona de Despacho (Outbound)
            $despacho = new Location();
            $despacho->warehouse_id = $warehouse->id;
            $despacho->code = 'DESPACHO';
            $despacho->type = 'staging';
            $despacho->is_blocked = false;
            $despacho->aisle = null;
            $despacho->rack = null;
            $despacho->level = null;
            $despacho->position = null;
            $despacho->save();

            DB::commit();
            return back()->with('success', 'Bodega creada exitosamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating warehouse: " . $e->getMessage());
            return back()->withErrors(['msg' => 'Error al guardar: ' . $e->getMessage()]);
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
        return back()->with('success', 'Configuración de bodega actualizada.');
    }

    public function destroyWarehouse($id)
    {
        $warehouse = Warehouse::findOrFail($id);
        
        $hasStock = Location::where('warehouse_id', $warehouse->id)
            ->whereHas('inventory', function($q) {
                $q->where('quantity', '>', 0);
            })->exists();

        if ($hasStock) {
             return back()->with('error', 'No se puede eliminar: La bodega tiene stock activo.');
        }
        
        $warehouse->delete();
        return back()->with('success', 'Bodega eliminada correctamente.');
    }

    // =========================================================================
    // 6. LÓGICA TÉCNICA: MAPA Y LAYOUT
    // =========================================================================

    public function getLayoutData($id) 
    {
        try {
            $data = $this->layoutService->getWarehouseMapData($id);
            return response()->json(['success' => true, 'structure' => $data]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function generateLayout(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'level_configs' => 'required|array', 
        ]);

        try {
            DB::beginTransaction();
            $count = $this->layoutService->generateFullWarehouse(
                $request->warehouse_id,
                $request->level_configs
            );
            DB::commit();

            return response()->json(['success' => true, 'message' => "Estructura generada ({$count} bines)."]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function saveRack(Request $request)
    {
        $request->validate(['warehouse_id' => 'required', 'rack_code' => 'required']);

        try {
            $this->layoutService->createRackStructure(
                $request->warehouse_id,
                $request->aisle,
                $request->side,
                $request->rack_code,
                $request->level_configs
            );
            return response()->json(['success' => true, 'message' => 'Rack actualizado.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getRackDetails(Request $request)
    {
        try {
            $warehouse = Warehouse::findOrFail($request->warehouse_id);
            $rackPad = str_pad($request->rack_col, 2, '0', STR_PAD_LEFT); 

            $locations = Location::where('warehouse_id', $warehouse->id)
                ->where('aisle', $request->aisle)
                ->where('rack', $rackPad)
                ->where('side', $request->side)
                ->orderBy('level', 'asc')
                ->get();

            if ($locations->isEmpty()) {
                return response()->json(['status' => 'success', 'levels' => []]);
            }

            $levels = $locations->groupBy('level')->map(function ($locs, $key) {
                return [
                    'level' => (int)$key,
                    'bins_count' => $locs->count(),
                    'bin_type_id' => $locs->first()->bin_type_id
                ];
            })->values();

            return response()->json(['status' => 'success', 'levels' => $levels]);

        } catch (\Exception $e) {
            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // 7. ETIQUETAS (REFACTORIZADO PARA GENERAR TODAS LAS ETIQUETAS)
    // =========================================================================

    public function printLabels($id)
    {
        $warehouse = Warehouse::with(['branch', 'locations.binType'])->findOrFail($id);
        $labels = [];
        
        // 1. Etiqueta Maestra de BODEGA
        $labels[] = [
            'type' => 'WAREHOUSE', 
            'title' => $warehouse->name, 
            'subtitle' => $warehouse->branch->name, 
            'code' => $warehouse->code, 
            'qr_data' => $warehouse->code
        ];

        // 2. Etiquetas de Zonas Especiales (RECEPCION / DESPACHO)
        $stagingZones = Location::where('warehouse_id', $id)
            ->whereIn('code', ['RECEPCION', 'DESPACHO'])
            ->get();

        foreach($stagingZones as $zone) {
            $labels[] = [
                'type' => 'ZONE',
                'title' => $zone->code,
                'subtitle' => $warehouse->name,
                'code' => $zone->code,
                'qr_data' => $zone->code
            ];
        }

        // 3. Etiquetas Estructurales (Pasillos, Racks, Bines)
        // Filtramos 'aisle' != null para evitar el "Pasillo 0" o zonas sin ubicación física
        $locations = $warehouse->locations()
            ->whereNotNull('aisle') 
            ->where('aisle', '!=', '')
            ->orderBy('aisle')
            ->orderBy('side') 
            ->orderBy('rack')
            ->orderBy('level')
            ->orderBy('position')
            ->get();
            
        // Agrupamos por Pasillo
        $aisles = $locations->groupBy('aisle');

        foreach ($aisles as $aisleNum => $locsInAisle) {
            // A. Etiqueta de PASILLO (Una por pasillo)
            $labels[] = [
                'type' => 'AISLE', 
                'title' => "PASILLO $aisleNum", 
                'subtitle' => $warehouse->name, 
                'code' => "P$aisleNum", 
                'qr_data' => "{$warehouse->code}-P$aisleNum"
            ];
            
            // Agrupamos por Rack dentro del pasillo
            // Aquí agrupamos por Lado y luego Rack para orden lógico
            $sides = $locsInAisle->groupBy('side');
            
            foreach($sides as $side => $locsInSide) {
                $racks = $locsInSide->groupBy('rack');

                foreach ($racks as $rackNum => $locsInRack) {
                    // B. Etiqueta de RACK (Ej: Pasillo 01 - Lado A - Rack 05)
                    $labels[] = [
                        'type' => 'RACK', 
                        'title' => "RACK $rackNum ($side)", 
                        'subtitle' => "P$aisleNum - Lado $side", 
                        'code' => "R$rackNum", 
                        'qr_data' => "{$warehouse->code}-P$aisleNum-{$side}-R$rackNum"
                    ];
                    
                    // C. Etiquetas de BIN INDIVIDUALES
                    foreach ($locsInRack as $bin) {
                        $labels[] = [
                            'type' => 'BIN', 
                            'title' => $bin->code, 
                            'subtitle' => $bin->binType->name ?? 'Std', 
                            'code' => $bin->code, 
                            'qr_data' => $bin->code 
                        ];
                    }
                }
            }
        }
        
        return view('admin.inventory.print_warehouse_labels', compact('warehouse', 'labels'));
    }
}