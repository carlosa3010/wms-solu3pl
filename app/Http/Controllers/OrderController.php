<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderAllocation;
use App\Models\Inventory;
use App\Models\Client;
use App\Models\Product;
use App\Models\Branch;
use App\Models\Country;
use App\Models\State;
use App\Models\ShippingMethod;
use App\Models\Transfer; 
use App\Models\TransferItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Listado principal de órdenes para administración.
     */
    public function index(Request $request)
    {
        $query = Order::with(['client', 'items', 'branch']);
        
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_id_number', 'like', "%{$search}%");
            });
        }
        
        $orders = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.operations.orders.index', compact('orders'));
    }

    /**
     * Muestra el formulario de creación de pedidos (Lado Admin).
     */
    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        $countries = Country::orderBy('name')->get();
        $shippingMethods = ShippingMethod::where('is_active', true)->get();
        $branches = Branch::where('is_active', true)->get();

        // Generar número de orden automático (Correlativo)
        $lastOrder = Order::latest('id')->first();
        $nextId = $lastOrder ? $lastOrder->id + 1 : 1;
        $nextOrderNumber = 'ORD-' . str_pad($nextId, 6, '0', STR_PAD_LEFT);

        return view('admin.operations.orders.create', compact('clients', 'countries', 'shippingMethods', 'branches', 'nextOrderNumber'));
    }

    /**
     * Obtiene los productos de un cliente vía AJAX.
     * Solo devuelve productos que tengan stock físico en alguna sede.
     */
    public function getClientProducts($clientId)
    {
        $products = Product::where('client_id', $clientId)
            ->withSum('inventory as stock_available', 'quantity')
            ->get()
            ->filter(fn($p) => $p->stock_available > 0)
            ->values();

        return response()->json($products);
    }

    /**
     * Obtiene los estados de un país vía AJAX.
     */
    public function getStatesByCountry($countryId)
    {
        $states = State::where('country_id', $countryId)->orderBy('name')->get();
        return response()->json($states);
    }

    /**
     * Almacena la orden y ejecuta la INTELIGENCIA DE ASIGNACIÓN Y CONSOLIDACIÓN.
     */
    public function store(Request $request)
    {
        // Validación ajustada a la estructura de la base de datos
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'order_number' => 'required|unique:orders,order_number',
            'customer_name' => 'required|string|max:255',
            'customer_id_number' => 'required|string|max:50', 
            'shipping_address' => 'required|string',
            'country' => 'required',
            'state' => 'required',
            'city' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        // Mapeo de items para el motor de decisión
        $mappedItems = array_map(function($item) {
            return [
                'product_id' => $item['product_id'],
                'qty' => $item['qty']
            ];
        }, $request->items);

        // Ejecutar el motor de decisión basado en el destino para elegir la mejor Sede
        $plan = $this->calculateAssignmentPlan($request->customer_state, $request->customer_country, $mappedItems);

        if (!$plan['success']) {
            return back()->withInput()->withErrors(['error' => $plan['message']]);
        }

        try {
            $order = DB::transaction(function () use ($request, $plan) {
                // 1. Crear la orden principal en la sede target elegida
                $order = Order::create([
                    'order_number' => $request->order_number,
                    'external_ref' => $request->external_ref, // Campo habilitado por migración
                    'client_id' => $request->client_id,
                    'branch_id' => $plan['target_branch_id'],
                    'customer_name' => $request->customer_name,
                    'customer_id_number' => $request->customer_id_number, // Campo habilitado por migración
                    'customer_email' => $request->customer_email,
                    'shipping_address' => $request->shipping_address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'customer_zip' => $request->customer_zip,
                    'country' => $request->country,
                    'phone' => $request->phone,
                    'shipping_method' => $request->shipping_method,
                    'status' => $plan['requires_transfer'] ? 'waiting_transfer' : 'pending',
                    'notes' => $request->notes,
                ]);

                // 2. Crear los ítems de la orden
                foreach ($request->items as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'requested_quantity' => $itemData['qty'],
                    ]);
                }

                // 3. Crear traslados automáticos si se requiere consolidación multisede
                if ($plan['requires_transfer']) {
                    $this->createConsolidationTransfers($order, $plan['transfer_plan']);
                }

                return $order;
            });

            // 4. Reservar el stock disponible físicamente en la sede destino (Allocation/Picking)
            $this->performStockAllocation($order);

            $message = $plan['requires_transfer'] 
                ? "Orden creada en espera de consolidación. Se generaron traslados automáticos para completar el stock en {$order->branch->name}."
                : "Orden asignada y stock reservado automáticamente en {$order->branch->name}.";

            return redirect()->route('admin.orders.index')->with('success', $message);

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Fallo en la inteligencia de asignación: ' . $e->getMessage()]);
        }
    }

    /**
     * MOTOR DE DECISIÓN: Evalúa sedes por stock total vs cercanía geográfica.
     */
    private function calculateAssignmentPlan($destState, $destCountry, $requestedItems)
    {
        $branches = Branch::where('is_active', true)->get();
        $branchEvaluations = [];

        foreach ($branches as $branch) {
            $geoScore = $this->calculateGeoScore($branch, $destState, $destCountry);
            if ($geoScore === 0) continue; 

            $fulfillment = $this->evaluateStockFulfillment($branch->id, $requestedItems);
            
            $branchEvaluations[] = [
                'branch' => $branch,
                'geo_score' => $geoScore,
                'is_full_stock' => $fulfillment['is_complete'],
                'fulfillment_data' => $fulfillment
            ];
        }

        if (empty($branchEvaluations)) {
            return ['success' => false, 'message' => 'No hay sedes con cobertura para esta zona geográfica.'];
        }

        // REGLA 1: Priorizar sede con stock 100% y mayor cercanía (GeoScore)
        $fullStockCandidates = collect($branchEvaluations)->where('is_full_stock', true)->sortByDesc('geo_score');
        if ($fullStockCandidates->isNotEmpty()) {
            return [
                'success' => true,
                'target_branch_id' => $fullStockCandidates->first()['branch']->id,
                'requires_transfer' => false,
                'transfer_plan' => []
            ];
        }

        // REGLA 2: Si nadie tiene 100%, elegir la sede más cercana (Target) y planear traslados desde otras sedes
        $bestGeoBranch = collect($branchEvaluations)->sortByDesc('geo_score')->first();
        $targetBranchId = $bestGeoBranch['branch']->id;
        $transferPlan = [];

        foreach ($requestedItems as $item) {
            $prodId = $item['product_id'];
            $needed = $item['qty'];
            $stockInTarget = $bestGeoBranch['fulfillment_data']['items'][$prodId]['available'] ?? 0;

            if ($stockInTarget < $needed) {
                $toTransfer = $needed - $stockInTarget;
                
                // Buscar quién tiene este SKU sobrante para enviarlo a la sede Target
                $sources = Branch::where('id', '!=', $targetBranchId)->where('is_active', true)->get();
                foreach ($sources as $source) {
                    if ($toTransfer <= 0) break;
                    
                    $availableInSource = $this->getStockInBranch($prodId, $source->id);
                    if ($availableInSource > 0) {
                        $take = min($toTransfer, $availableInSource);
                        $transferPlan[] = [
                            'product_id' => $prodId,
                            'from_branch_id' => $source->id,
                            'to_branch_id' => $targetBranchId,
                            'quantity' => $take
                        ];
                        $toTransfer -= $take;
                    }
                }

                if ($toTransfer > 0) {
                    $p = Product::find($prodId);
                    return ['success' => false, 'message' => "Ni sumando todas las sedes se alcanza el stock para: " . ($p->sku ?? 'Desconocido') . " (Faltan {$toTransfer})"];
                }
            }
        }

        return [
            'success' => true,
            'target_branch_id' => $targetBranchId,
            'requires_transfer' => true,
            'transfer_plan' => $transferPlan
        ];
    }

    /**
     * Evalúa qué porcentaje de la orden puede cubrir una sede específica.
     */
    private function evaluateStockFulfillment($branchId, $requestedItems)
    {
        $data = ['is_complete' => true, 'items' => []];
        foreach ($requestedItems as $item) {
            $stock = $this->getStockInBranch($item['product_id'], $branchId);
            $data['items'][$item['product_id']] = [
                'available' => $stock,
                'requested' => $item['qty']
            ];
            if ($stock < $item['qty']) $data['is_complete'] = false;
        }
        return $data;
    }

    /**
     * Crea las órdenes de traslado necesarias para la consolidación automática.
     */
    private function createConsolidationTransfers(Order $order, $plan)
    {
        $groupedBySource = collect($plan)->groupBy('from_branch_id');

        foreach ($groupedBySource as $sourceId => $items) {
            $transfer = Transfer::create([
                'origin_branch_id' => $sourceId,
                'destination_branch_id' => $order->branch_id,
                'transfer_number' => 'AUTO-TR-' . strtoupper(Str::random(6)),
                'status' => 'pending',
                'notes' => "Consolidación automática para Pedido #{$order->order_number}",
                'created_by' => auth()->id() ?? 1
            ]);

            foreach ($items as $itemData) {
                TransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                ]);
            }
        }
    }

    private function calculateGeoScore($branch, $destState, $destCountry) {
        // Validación de cobertura por país
        $coversCountry = ($branch->country === $destCountry) || (is_array($branch->covered_countries) && in_array($destCountry, $branch->covered_countries));
        if (!$coversCountry) return 0;

        if ($branch->country === $destCountry && $branch->state === $destState) return 100;
        if (is_array($branch->covered_states) && in_array($destState, $branch->covered_states)) return 80;
        if ($branch->country === $destCountry) return 50;
        return 20; 
    }

    private function getStockInBranch($productId, $branchId) {
        return Inventory::where('product_id', $productId)
            ->whereHas('location.warehouse', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->sum('quantity');
    }

    /**
     * Realiza la reserva física de stock en bines (Allocation).
     */
    private function performStockAllocation(Order $order)
    {
        DB::transaction(function() use ($order) {
            foreach($order->items as $item) {
                $needed = $item->requested_quantity;
                $availableStock = Inventory::where('product_id', $item->product_id)
                    ->where('quantity', '>', 0)
                    ->whereHas('location.warehouse', function($q) use ($order) {
                        $q->where('branch_id', $order->branch_id);
                    })
                    ->orderBy('quantity', 'desc')
                    ->get();

                foreach($availableStock as $stockRecord) {
                    if($needed <= 0) break;
                    $take = min($needed, $stockRecord->quantity);

                    OrderAllocation::create([
                        'order_item_id' => $item->id,
                        'location_id'   => $stockRecord->location_id,
                        'quantity'      => $take,
                        'status'        => 'planned'
                    ]);

                    $stockRecord->decrement('quantity', $take);
                    $needed -= $take;
                }
                $item->update(['allocated_quantity' => ($item->requested_quantity - $needed)]);
            }
        });
    }

    public function show($id) {
        $order = Order::with(['client', 'items.product', 'branch'])->findOrFail($id);
        return view('admin.operations.orders.show', compact('order'));
    }

    public function destroy($id) {
        $order = Order::with('items.allocations')->findOrFail($id);
        DB::transaction(function() use ($order) {
            foreach($order->items as $item) {
                foreach($item->allocations as $alloc) {
                    Inventory::where('location_id', $alloc->location_id)
                             ->where('product_id', $item->product_id)
                             ->increment('quantity', $alloc->quantity);
                    $alloc->delete();
                }
            }
            $order->update(['status' => 'cancelled']);
        });
        return redirect()->route('admin.orders.index')->with('success', 'Orden anulada y stock retornado a bines.');
    }
}