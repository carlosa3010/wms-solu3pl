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
use App\Models\Transfer; 
use App\Models\TransferItem;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Listado principal de órdenes.
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
     * Almacena la orden y ejecuta la INTELIGENCIA DE ASIGNACIÓN Y CONSOLIDACIÓN.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'order_number' => 'required|unique:orders,order_number',
            'customer_name' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        // Ejecutar el motor de decisión
        $plan = $this->calculateAssignmentPlan($request->state, $request->country, $request->items);

        if (!$plan['success']) {
            return back()->withInput()->withErrors(['error' => $plan['message']]);
        }

        try {
            $order = DB::transaction(function () use ($request, $plan) {
                // 1. Crear la orden en la sede principal elegida
                $order = Order::create([
                    'order_number' => $request->order_number,
                    'client_id' => $request->client_id,
                    'branch_id' => $plan['target_branch_id'],
                    'customer_name' => $request->customer_name,
                    'customer_id_number' => $request->customer_id_number,
                    'customer_email' => $request->customer_email,
                    'shipping_address' => $request->shipping_address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                    'phone' => $request->phone,
                    'shipping_method' => $request->shipping_method,
                    'status' => $plan['requires_transfer'] ? 'waiting_transfer' : 'pending'
                ]);

                foreach ($request->items as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'requested_quantity' => $itemData['qty'],
                    ]);
                }

                // 2. Si hay un plan de traslado, ejecutar creación de documentos
                if ($plan['requires_transfer']) {
                    $this->createConsolidationTransfers($order, $plan['transfer_plan']);
                }

                return $order;
            });

            // 3. Reservar el stock que YA está en la sede destino (Picking Parcial o Total)
            $this->performStockAllocation($order);

            $message = $plan['requires_transfer'] 
                ? "Orden creada en espera de consolidación. Se generaron traslados para completar el stock en {$order->branch->name}."
                : "Orden asignada y stock reservado automáticamente en {$order->branch->name}.";

            return redirect()->route('admin.orders.index')->with('success', $message);

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Fallo en la inteligencia de asignación: ' . $e->getMessage()]);
        }
    }

    /**
     * MOTOR DE DECISIÓN: Evalúa sedes por stock total vs cercanía + traslados.
     */
    private function calculateAssignmentPlan($destState, $destCountry, $requestedItems)
    {
        $branches = Branch::where('is_active', true)->get();
        $branchEvaluations = [];

        foreach ($branches as $branch) {
            $geoScore = $this->calculateGeoScore($branch, $destState, $destCountry);
            if ($geoScore === 0) continue; // Sede no cubre el país

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

        // REGLA 1: Si hay sedes con 100% de stock, elegir la más cercana de ellas
        $fullStockCandidates = collect($branchEvaluations)->where('is_full_stock', true)->sortByDesc('geo_score');
        if ($fullStockCandidates->isNotEmpty()) {
            return [
                'success' => true,
                'target_branch_id' => $fullStockCandidates->first()['branch']->id,
                'requires_transfer' => false,
                'transfer_plan' => []
            ];
        }

        // REGLA 2: Nadie tiene 100% de stock. Elegir la sede más cercana (Target) y planear traslados
        $bestGeoBranch = collect($branchEvaluations)->sortByDesc('geo_score')->first();
        $targetBranchId = $bestGeoBranch['branch']->id;
        $transferPlan = [];

        foreach ($requestedItems as $item) {
            $prodId = $item['product_id'];
            $needed = $item['qty'];
            $stockInTarget = $bestGeoBranch['fulfillment_data']['items'][$prodId]['available'];

            if ($stockInTarget < $needed) {
                $toTransfer = $needed - $stockInTarget;
                
                // Buscar quién tiene este SKU sobrante
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
                    return ['success' => false, 'message' => "Ni sumando todas las sedes se alcanza el stock para: {$p->sku} (Faltan {$toTransfer})"];
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
     * Crea las órdenes de traslado necesarias para la consolidación.
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
        return 20; // Atiende el país pero desde lejos
    }

    private function getStockInBranch($productId, $branchId) {
        return Inventory::where('product_id', $productId)
            ->whereHas('location.warehouse', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->sum('quantity');
    }

    /**
     * Realiza la reserva física de stock en bines (Picking).
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