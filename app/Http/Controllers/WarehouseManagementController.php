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
use App\Models\Transfer;
use App\Services\WarehouseLayoutService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WarehouseManagementController extends Controller
{
    protected $layoutService;

    public function __construct(WarehouseLayoutService $layoutService)
    {
        $this->layoutService = $layoutService;
        // Opcional: Middleware para asegurar que solo Admin accede a configuración
        // $this->middleware('can:manage_settings'); 
    }

    // =========================================================================
    // VISTAS PRINCIPALES (ADMIN)
    // =========================================================================

    /**
     * Mapa Interactivo de Bodegas (Layout Designer)
     */
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

    /**
     * Configuración de Cobertura Geográfica (Para asignación de órdenes)
     */
    public function coverage()
    {
        $branches = Branch::where('is_active', true)->get();
        $countries = Country::orderBy('name')->get();
        // Carga optimizada de estados
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

        // Asignación directa (El modelo Branch debe tener 'casts' => 'array')
        $branch->covered_countries = $request->input('covered_countries', []);
        $branch->covered_states = $request->input('covered_states', []);
        $branch->can_export = $request->has('can_export');
        
        $branch->save();

        return back()->with('success', "Cobertura actualizada para {$branch->name}");
    }

    // =========================================================================
    // MÉTODOS DISPATCHER (Resource Router)
    // =========================================================================
    
    public function store(Request $request)
    {
        return $this->storeBranch($request);
    }

    public function update(Request $request, $id)
    {
        // Detectar si la petición viene del formulario de sucursal o bodega
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
    // GESTIÓN DE SUCURSALES (Branches)
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
        return back()->with('success', 'Datos de la sucursal actualizados.');
    }

    public function destroyBranch($id)
    {
        $branch = Branch::findOrFail($id);
        
        // 1. Verificar Usuarios Activos
        if (User::where('branch_id', $branch->id)->exists()) {
            return back()->with('error', 'No se puede eliminar: Hay usuarios asignados a esta sucursal.');
        }

        // 2. Verificar Órdenes Activas
        if (Order::where('branch_id', $branch->id)->whereNotIn('status', ['cancelled', 'completed'])->exists()) {
            return back()->with('error', 'No se puede eliminar: Hay órdenes activas en esta sucursal.');
        }

        // 3. Verificar Traslados Pendientes (Origen o Destino)
        $pendingTransfers = Transfer::where(function($q) use ($id) {
            $q->where('origin_branch_id', $id)->orWhere('destination_branch_id', $id);
        })->whereIn('status', ['pending', 'in_transit'])->exists();

        if ($pendingTransfers) {
            return back()->with('error', 'No se puede eliminar: Hay traslados pendientes vinculados a esta sucursal.');
        }

        // 4. Verificar Stock en sus bodegas
        $hasStock = Inventory::whereHas('location.warehouse', function($q) use ($id){
            $q->where('branch_id', $id);
        })->where('quantity', '>', 0)->exists();

        if ($hasStock) {
            return back()->with('error', 'No se puede eliminar: Aún hay stock físico en las bodegas de esta sucursal.');
        }

        // Soft delete o delete real según modelo
        $branch->delete(); 
        return back()->with('success', 'Sucursal eliminada del sistema.');
    }

    // =========================================================================
    // GESTIÓN DE BODEGAS (Warehouses)
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

            // 2. Crear Zona de Recepción Automática
            // NOTA: Se crea con coordenadas NULL para evitar pasillos fantasmas
            Location::create([
                'warehouse_id' => $warehouse->id,
                'code' => 'RECEPCION', // Código reservado
                'type' => 'staging',   // Tipo zona de paso
                'aisle' => null,       // Sin coordenadas físicas
                'rack' => null,
                'shelf' => null,
                'position' => null,
                'is_blocked' => false, // Disponible para recibir
                'description' => 'Zona general de descarga y recepción'
            ]);
            
            // Opcional: Crear Zona de Despacho
            Location::create([
                'warehouse_id' => $warehouse->id,
                'code' => 'DESPACHO', 
                'type' => 'staging',
                'aisle' => null,
                'rack' => null,
                'shelf' => null,
                'position' => null,
                'is_blocked' => false,
                'description' => 'Zona de preparación de salida'
            ]);

            DB::commit();
            return back()->with('success', 'Bodega creada exitosamente con zona de RECEPCION y DESPACHO.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating warehouse: " . $e->getMessage());
            return back()->with('error', 'Error al crear bodega: ' . $e->getMessage());
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
        
        // Verificar Stock
        $hasStock = Location::where('warehouse_id', $warehouse->id)
            ->whereHas('inventory', function($q) {
                $q->where('quantity', '>', 0);
            })->exists();

        if ($hasStock) {
             return back()->withErrors(['error' => 'No se puede eliminar la bodega porque tiene stock activo. Vacíe los bines primero.']);
        }
        
        $warehouse->delete();
        return back()->with('success', 'Bodega eliminada correctamente.');
    }

    // =========================================================================
    // LÓGICA TÉCNICA: MAPA Y ETIQUETAS (Visualización)
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
     * API: Generación Masiva de Estructura.
     */
    public function generateLayout(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'level_configs' => 'required|array', 
            'level_configs.*.level' => 'required|integer',
            'level_configs.*.bins_count' => 'required|integer|min:1',
            'level_configs.*.bin_type_id' => 'required|exists:bin_types,id',
        ]);

        try {
            DB::beginTransaction();
            $count = $this->layoutService->generateFullWarehouse(
                $request->warehouse_id,
                $request->level_configs
            );
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Estructura generada. Se crearon/actualizaron {$count} ubicaciones."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error generating layout: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API: Edición Individual de Rack.
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
     * API: Obtener detalles de un rack específico.
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

            $levelsRaw = $locations->groupBy('level');
            $levels = [];

            foreach ($levelsRaw as $shelfNum => $locs) {
                $firstLoc = $locs->first();
                $levels[] = [
                    'level'       => (int)$shelfNum,
                    'bins_count'  => (int)$locs->count(),
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
     * Genera las etiquetas QR (Impresión).
     */
    public function printLabels($id)
    {
        $warehouse = Warehouse::with(['branch', 'locations.binType'])->findOrFail($id);
        $labels = [];
        
        // Etiqueta Principal Bodega
        $labels[] = [
            'type'=>'WAREHOUSE', 
            'title'=>$warehouse->name, 
            'subtitle'=>$warehouse->branch->name, 
            'code'=>$warehouse->code, 
            'qr_data'=>$warehouse->code
        ];

        // Etiquetas de Pasillos, Racks y Bines
        $locations = $warehouse->locations()
            // Filtramos locations nulas (Recepción) para que no salgan en impresión de racks
            ->whereNotNull('aisle')
            ->orderBy('aisle')->orderBy('rack')->orderBy('level')->orderBy('position')
            ->get();
            
        $aisles = $locations->groupBy('aisle');

        foreach ($aisles as $aisleNum => $locsInAisle) {
            $labels[] = [
                'type'=>'AISLE', 
                'title'=>"PASILLO $aisleNum", 
                'subtitle'=>$warehouse->name, 
                'code'=>"P$aisleNum", 
                'qr_data'=>"{$warehouse->code}-P$aisleNum"
            ];
            
            foreach ($locsInAisle->groupBy('rack') as $rackNum => $locsInRack) {
                $labels[] = [
                    'type'=>'RACK', 
                    'title'=>"RACK $rackNum", 
                    'subtitle'=>"PASILLO $aisleNum", 
                    'code'=>"R$rackNum", 
                    'qr_data'=>"{$warehouse->code}-P$aisleNum-R$rackNum"
                ];
                
                foreach ($locsInRack as $bin) {
                    $labels[] = [
                        'type'=>'BIN', 
                        'title'=>$bin->code, 
                        'subtitle'=>$bin->binType->name ?? 'Std', 
                        'code'=>$bin->code, 
                        'qr_data'=>$bin->code // Formato: BOD-P01-R01-L1-B01
                    ];
                }
            }
        }
        
        return view('admin.inventory.print_warehouse_labels', compact('warehouse', 'labels'));
    }
}