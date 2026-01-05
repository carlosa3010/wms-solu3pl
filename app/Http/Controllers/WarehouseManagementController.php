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
    // VISTAS PRINCIPALES
    // =========================================================================

    public function index(Request $request)
    {
        $branches = Branch::with('warehouses')->orderBy('name')->get();
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
    // MÉTODOS DISPATCHER (Resuelven rutas resource estándar)
    // =========================================================================
    
    public function store(Request $request)
    {
        return $this->storeBranch($request);
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
            'rows'      => 'required|integer|min:1|max:100', // Pasillos
            'cols'      => 'required|integer|min:1|max:100', // Racks por lado
            'levels'    => 'required|integer|min:1|max:20'   // Niveles
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
    // LÓGICA TÉCNICA: MAPA Y ETIQUETAS (NUEVO & LEGACY)
    // =========================================================================

    /**
     * API: Obtiene la estructura JSON completa para el mapa visual.
     */
    public function getLayoutData($id) 
    {
        try {
            $data = $this->layoutService->getWarehouseMapData($id);
            return response()->json(['success' => true, 'structure' => $data]);
        } catch (\Exception $e) {
            Log::error("Error loading layout: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Generación Masiva (Estructura Base).
     * Crea TODA la estructura de la bodega (Pasillos, Racks, Niveles)
     * basándose en las dimensiones de la bodega y la configuración de niveles recibida.
     */
    public function generateLayout(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            // Validamos que se reciba un array de configuraciones por nivel
            'level_configs' => 'required|array', 
            'level_configs.*.level' => 'required|integer',
            'level_configs.*.bins_count' => 'required|integer|min:1',
            'level_configs.*.bin_type_id' => 'required|exists:bin_types,id',
        ]);

        try {
            $count = $this->layoutService->generateFullWarehouse(
                $request->warehouse_id,
                $request->level_configs
            );

            return response()->json([
                'success' => true,
                'message' => "Estructura generada exitosamente. Se crearon/actualizaron {$count} ubicaciones."
            ]);
        } catch (\Exception $e) {
            Log::error("Error generating layout: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Edición Individual de Rack.
     * Guarda la configuración específica de un solo rack.
     */
    public function saveRack(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'aisle' => 'required',
            'side' => 'required',
            'rack_code' => 'required',
            'level_configs' => 'required|array' 
        ]);

        try {
            $locations = $this->layoutService->createRackStructure(
                $request->warehouse_id,
                $request->aisle,
                $request->side,
                $request->rack_code,
                $request->level_configs
            );

            return response()->json([
                'success' => true,
                'message' => 'Rack actualizado correctamente.',
                'count' => count($locations)
            ]);
        } catch (\Exception $e) {
            Log::error("Error saving single rack: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * API: Obtener detalles de un rack específico (para el modal de edición).
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

            // Búsqueda usando los nuevos campos indexados
            // En la BD se guardan con padding (Ej: '01'), aseguramos formato al buscar
            $rackPad = str_pad($request->rack_col, 2, '0', STR_PAD_LEFT); 

            $locations = Location::where('warehouse_id', $warehouse->id)
                ->where('aisle', $request->aisle)
                ->where('rack', $rackPad)
                ->where('side', $request->side)
                ->orderBy('level', 'asc')
                ->orderBy('position', 'asc')
                ->get();

            if ($locations->isEmpty()) {
                return response()->json(['status' => 'success', 'levels' => []]);
            }

            $levelsRaw = $locations->groupBy('level');
            $levels = [];

            foreach ($levelsRaw as $shelfNum => $locs) {
                $firstLoc = $locs->first();
                $maxBin = $locs->count(); // Cantidad de bines creados para ese nivel

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

    /**
     * Genera las etiquetas QR para toda la bodega.
     */
    public function printLabels($id)
    {
        $warehouse = Warehouse::with(['branch', 'locations.binType'])->findOrFail($id);
        $labels = [];
        
        // 1. Etiqueta Maestra Bodega
        $labels[] = ['type'=>'WAREHOUSE', 'title'=>$warehouse->name, 'subtitle'=>$warehouse->branch->name, 'code'=>$warehouse->code, 'qr_data'=>$warehouse->code];

        $locations = $warehouse->locations()->orderBy('aisle')->orderBy('rack')->orderBy('level')->orderBy('position')->get();
        $aisles = $locations->groupBy('aisle');

        foreach ($aisles as $aisleNum => $locsInAisle) {
            // 2. Etiqueta Pasillo
            $labels[] = ['type'=>'AISLE', 'title'=>"PASILLO $aisleNum", 'subtitle'=>$warehouse->name, 'code'=>"P$aisleNum", 'qr_data'=>"{$warehouse->code}-P$aisleNum"];
            
            foreach ($locsInAisle->groupBy('rack') as $rackNum => $locsInRack) {
                // 3. Etiqueta Rack
                $labels[] = ['type'=>'RACK', 'title'=>"RACK $rackNum", 'subtitle'=>"PASILLO $aisleNum", 'code'=>"R$rackNum", 'qr_data'=>"{$warehouse->code}-P$aisleNum-R$rackNum"];
                
                foreach ($locsInRack as $bin) {
                    // 4. Etiqueta Bin
                    $labels[] = [
                        'type'=>'BIN', 
                        'title'=>$bin->code, 
                        'subtitle'=>$bin->binType->name ?? 'Std', 
                        'code'=>$bin->code, 
                        'qr_data'=>$bin->code
                    ];
                }
            }
        }
        return view('admin.inventory.print_warehouse_labels', compact('warehouse', 'labels'));
    }
}