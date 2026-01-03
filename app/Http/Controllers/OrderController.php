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
use App\Models\Transfer; // Asumido: Modelo para traslados
use App\Models\TransferItem; // Asumido: Items del traslado
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

        // 1. Determinar la mejor sede (por geografía) y verificar si requiere traslados
        $assignmentPlan = $this->calculateAssignmentPlan($request->state, $request->country, $request->items);

        if (!$assignmentPlan['success']) {
            return back()->withInput()->withErrors(['error' => $assignmentPlan['message']]);
        }

        try {
            $order = DB::transaction(function () use ($request, $assignmentPlan) {
                // 2. Crear la orden
                $order = Order::create([
                    'order_number' => $request->order_number,
                    'client_id' => $request->client_id,
                    'branch_id' => $assignmentPlan['branch_id'],
                    'customer_name' => $request->customer_name,
                    'customer_id_number' => $request->customer_id_number,
                    'customer_email' => $request->customer_email,
                    'shipping_address' => $request->shipping_address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                    'phone' => $request->phone,
                    'shipping_method' => $request->shipping_method,
                    'status' => $assignmentPlan['requires_transfer'] ? 'waiting_transfer' : 'pending'
                ]);

                foreach ($request->items as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'requested_quantity' => $itemData['qty'],
                    ]);
                }

                // 3. Si requiere consolidación, crear los traslados automáticos
                if ($assignmentPlan['requires_transfer']) {
                    $this->createConsolidationTransfers($order, $assignmentPlan['transfer_plan']);
                }

                return $order;
            });

            // 4. Reservar stock solo de lo que ya está disponible en la sede destino
            if (!$assignmentPlan['requires_transfer']) {
                $this->performStockAllocation($order);
                $msg = 'Pedido asignado y stock reservado automáticamente.';
            } else {
                $msg = 'Pedido en espera. Se han generado traslados automáticos para consolidar stock en ' . $order->branch->name;
            }

            return redirect()->route('admin.orders.index')->with('success', $msg);

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al procesar: ' . $e->getMessage()]);
        }
    }

    /**
     * Calcula la mejor sede y planea traslados si es necesario.
     */
    private function calculateAssignmentPlan($destState, $destCountry, $requestedItems)
    {
        $branches = Branch::where('is_active', true)->get();
        $bestBranch = null;
        $maxGeoScore = -1;

        // Fase 1: Encontrar la mejor sede geográfica (Target)
        foreach ($branches as $branch) {
            $score = $this->calculateGeoScore($branch, $destState, $destCountry);
            if ($score > $maxGeoScore) {
                $maxGeoScore = $score;
                $bestBranch = $branch;
            }
        }

        if (!$bestBranch || $maxGeoScore === 0) {
            return ['success' => false, 'message' => 'Ninguna sede cubre esta zona geográfica.'];
        }

        // Fase 2: Verificar Stock en la sede elegida y planear traslados
        $transferPlan = [];
        $requiresTransfer = false;

        foreach ($requestedItems as $item) {
            $productId = $item['product_id'];
            $qtyNeeded = $item['qty'];

            // Stock actual en la sede destino
            $stockInTarget = $this->getStockInBranch($productId, $bestBranch->id);
            
            if ($stockInTarget < $qtyNeeded) {
                $requiresTransfer = true;
                $missingQty = $qtyNeeded - $stockInTarget;

                // Buscar en otras sedes para cubrir el faltante
                $otherBranches = Branch::where('id', '!=', $bestBranch->id)->where('is_active', true)->get();
                foreach ($otherBranches as $sourceBranch) {
                    if ($missingQty <= 0) break;

                    $stockInSource = $this->getStockInBranch($productId, $sourceBranch->id);
                    if ($stockInSource > 0) {
                        $take = min($missingQty, $stockInSource);
                        $transferPlan[] = [
                            'product_id' => $productId,
                            'from_branch_id' => $sourceBranch->id,
                            'to_branch_id' => $bestBranch->id,
                            'quantity' => $take
                        ];
                        $missingQty -= $take;
                    }
                }

                if ($missingQty > 0) {
                    $p = Product::find($productId);
                    return [
                        'success' => false, 
                        'message' => "Stock insuficiente global para el producto: {$p->sku}. Faltan {$missingQty} unidades."
                    ];
                }
            }
        }

        return [
            'success' => true,
            'branch_id' => $bestBranch->id,
            'requires_transfer' => $requiresTransfer,
            'transfer_plan' => $transferPlan
        ];
    }

    /**
     * Crea los registros de traslado para consolidar stock.
     */
    private function createConsolidationTransfers(Order $order, $plan)
    {
        // Agrupar plan por sede de origen para crear un traslado por sede
        $groupedPlan = collect($plan)->groupBy('from_branch_id');

        foreach ($groupedPlan as $sourceBranchId => $items) {
            $sourceBranch = Branch::find($sourceBranchId);
            
            $transfer = Transfer::create([
                'origin_branch_id' => $sourceBranchId,
                'destination_branch_id' => $order->branch_id,
                'transfer_number' => 'TR-' . strtoupper(Str::random(8)),
                'status' => 'pending',
                'notes' => "Consolidación automática para pedido #{$order->order_number}",
                'created_by' => auth()->id() ?? 1
            ]);

            foreach ($items as $itemData) {
                TransferItem::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $itemData['product_id'],
                    'quantity' => $itemData['quantity'],
                ]);
                
                // Opcional: Podrías reservar el stock en la sede de origen aquí mismo
                // para evitar que otro pedido lo tome mientras se procesa el traslado.
            }
        }
    }

    private function calculateGeoScore($branch, $destState, $destCountry) {
        if ($branch->country !== $destCountry && !in_array($destCountry, $branch->covered_countries ?? [])) return 0;
        if ($branch->country === $destCountry && $branch->state === $destState) return 100;
        if (in_array($destState, $branch->covered_states ?? [])) return 80;
        if ($branch->country === $destCountry) return 50;
        if ($branch->can_export) return 20;
        return 5;
    }

    private function getStockInBranch($productId, $branchId) {
        return Inventory::where('product_id', $productId)
            ->whereHas('location.warehouse', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            })->sum('quantity');
    }

    /**
     * Ejecuta la reserva física de stock (Picking).
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
        return redirect()->route('admin.orders.index')->with('success', 'Orden anulada.');
    }
}