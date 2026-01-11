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
    }

    // =========================================================================
    // 1. UTILIDADES Y AJAX (SOLUCIÓN ERROR DE ESTADOS)
    // =========================================================================

    /**
     * Obtiene los estados/provincias basados en el ID del país.
     * Ruta: /admin/inventory/get-states/{countryId}
     */
    public function getStates($countryId)
    {
        $states = State::where('country_id', $countryId)->orderBy('name')->get();
        return response()->json($states);
    }

    // =========================================================================
    // 2. VISTAS PRINCIPALES (MAPA Y COBERTURA)
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
     * Configuración de Cobertura Geográfica
     */
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

        // Guardar configuración JSON y booleana
        $branch->covered_countries = $request->input('covered_countries', []);
        $branch->covered_states = $request->input('covered_states', []);
        $branch->can_export = $request->has('can_export');
        
        $branch->save();

        return back()->with('success', "Cobertura actualizada para {$branch->name}");
    }

    // =========================================================================
    // 3. DISPATCHER (CONTROLADOR ÚNICO PARA SUCURSALES Y BODEGAS)
    // =========================================================================
    
    public function store(Request $request)
    {
        // Si el request tiene 'country', asumimos que es una Sucursal
        if ($request->has('country') || $request->has('city')) {
            return $this->storeBranch($request);
        }
        return $this->storeWarehouse($request);
    }

    public function update(Request $request, $id)
    {
        // Detectar si es sucursal (por URL o campos)
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
    // 4. GESTIÓN DE SUCURSALES (BRANCHES)
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
            'code'    => 'required|string|unique:branches,code|max:15', // Código único
            'phone'   => 'nullable|string',
            'email'   => 'nullable|email'
        ]);
        
        // Valores por defecto
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
            // Validar unique ignorando el ID actual
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
        
        // Validaciones de integridad antes de borrar
        if (User::where('branch_id', $branch->id)->exists()) {
            return back()->with('error', 'No se puede eliminar: Hay usuarios asignados a esta sucursal.');
        }

        if (Order::where('branch_id', $branch->id)->whereNotIn('status', ['cancelled', 'completed'])->exists()) {
            return back()->with('error', 'No se puede eliminar: Hay órdenes activas en esta sucursal.');
        }

        // Verificar stock físico en cualquier bodega de la sucursal
        $hasStock = Inventory::whereHas('location.warehouse', function($q) use ($id){
            $q->where('branch_id', $id);
        })->where('quantity', '>', 0)->exists();

        if ($hasStock) {
            return back()->with('error', 'No se puede eliminar: Aún hay stock físico en las bodegas de esta sucursal.');
        }

        $branch->delete(); 
        return back()->with('success', 'Sucursal eliminada del sistema.');
    }

    // =========================================================================
    // 5. GESTIÓN DE BODEGAS (WAREHOUSES)
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

            // 2. Crear Zona de Recepción Automática (Staging)
            // Se usa 'staging' para que el sistema sepa que no son racks almacenables normales
            Location::create([
                'warehouse_id' => $warehouse->id,
                'code' => 'RECEPCION',
                'type' => 'staging',   
                'aisle' => null,       
                'rack' => null,
                'shelf' => null,
                'position' => null,
                'is_blocked' => false, 
                'description' => 'Zona de entrada y clasificación (Inbound)'
            ]);
            
            // 3. Crear Zona de Despacho Automática (Staging)
            Location::create([
                'warehouse_id' => $warehouse->id,
                'code' => 'DESPACHO', 
                'type' => 'staging',
                'aisle' => null,
                'rack' => null,
                'shelf' => null,
                'position' => null,
                'is_blocked' => false,
                'description' => 'Zona de salida y carga (Outbound)'
            ]);

            DB::commit();
            return back()->with('success', 'Bodega creada exitosamente con zonas de RECEPCION y DESPACHO.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error creating warehouse: " . $e->getMessage());
            // Mostramos el error exacto para depuración en la prueba piloto
            return back()->with('error', 'Error al crear bodega: ' . $e->getMessage());
        }
    }

    public function updateWarehouse(Request $request, $id)
    {
        $warehouse = Warehouse::findOrFail($id);
        
        $validated = $request->validate([
            'name'   => 'required|string|max:100',
            'rows'   => 'required|integer|min:1|max:100', // Actualiza dimensiones visuales
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
             return back()->with('error', 'No se puede eliminar la bodega porque tiene stock activo. Vacíe los bines primero.');
        }
        
        $warehouse->delete();
        return back()->with('success', 'Bodega eliminada correctamente.');
    }

    // =========================================================================
    // 6. LÓGICA TÉCNICA: MAPA Y GENERACIÓN DE LAYOUT (API JSON)
    // =========================================================================

    /**
     * Devuelve la estructura JSON para pintar el mapa en el Canvas/JS.
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
     * Generación masiva de ubicaciones (A-01-01...) basado en config.
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
                'message' => "Estructura generada. Se procesaron {$count} ubicaciones."
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error generating layout: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Guardar configuración de un Rack específico (click en el mapa).
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
     * Obtener datos de un rack para el modal de edición.
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

            // Agrupar por nivel para mostrar en el formulario
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

            // Ordenar niveles ascendentemente
            usort($levels, fn($a, $b) => $a['level'] <=> $b['level']);
            
            return response()->json(['status' => 'success', 'levels' => $levels]);

        } catch (\Exception $e) {
            Log::error('Error getRackDetails: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Error cargando datos'], 500);
        }
    }

    // =========================================================================
    // 7. IMPRESIÓN DE ETIQUETAS (QR)
    // =========================================================================

    public function printLabels($id)
    {
        $warehouse = Warehouse::with(['branch', 'locations.binType'])->findOrFail($id);
        $labels = [];
        
        // 1. Etiqueta Principal Bodega
        $labels[] = [
            'type'=>'WAREHOUSE', 
            'title'=>$warehouse->name, 
            'subtitle'=>$warehouse->branch->name, 
            'code'=>$warehouse->code, 
            'qr_data'=>$warehouse->code
        ];

        // 2. Etiquetas de Pasillos, Racks y Bines
        // Filtramos para no imprimir 'RECEPCION' ni 'DESPACHO' masivamente aqui
        $locations = $warehouse->locations()
            ->whereNotNull('aisle') 
            ->orderBy('aisle')->orderBy('rack')->orderBy('level')->orderBy('position')
            ->get();
            
        $aisles = $locations->groupBy('aisle');

        foreach ($aisles as $aisleNum => $locsInAisle) {
            // Etiqueta Pasillo
            $labels[] = [
                'type'=>'AISLE', 
                'title'=>"PASILLO $aisleNum", 
                'subtitle'=>$warehouse->name, 
                'code'=>"P$aisleNum", 
                'qr_data'=>"{$warehouse->code}-P$aisleNum"
            ];
            
            foreach ($locsInAisle->groupBy('rack') as $rackNum => $locsInRack) {
                // Etiqueta Rack
                $labels[] = [
                    'type'=>'RACK', 
                    'title'=>"RACK $rackNum", 
                    'subtitle'=>"PASILLO $aisleNum", 
                    'code'=>"R$rackNum", 
                    'qr_data'=>"{$warehouse->code}-P$aisleNum-R$rackNum"
                ];
                
                foreach ($locsInRack as $bin) {
                    // Etiqueta Bin Individual
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