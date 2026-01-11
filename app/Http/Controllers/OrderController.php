<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Client;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Country;
use App\Models\ShippingMethod;
use App\Models\State;
use App\Models\OrderAllocation;
use App\Models\Transfer;
use App\Models\TransferItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $query = Order::with(['client', 'items.product', 'branch', 'transfer'])
            ->latest();

        if (!empty($user->client_id)) {
            $query->where('client_id', $user->client_id);
        }
        
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }
        
        if (request('status')) {
            $query->where('status', request('status'));
        }

        $orders = $query->paginate(15);
        return view('admin.operations.orders.index', compact('orders'));
    }

    public function create()
    {
        $user = Auth::user();

        if (!empty($user->client_id)) {
            $clients = Client::where('id', $user->client_id)->get();
        } else {
            // Eliminamos filtro is_active si la columna no existe en clients tampoco, 
            // o usamos try/catch, pero asumiremos que solo products tiene el problema.
            $clients = Client::orderBy('company_name')->get();
        }

        $products = collect([]); 
        $countries = Country::orderBy('name')->get();
        $shippingMethods = ShippingMethod::get(); // Sin filtro active por seguridad

        $nextOrderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        
        return view('admin.operations.orders.create', compact('clients', 'products', 'countries', 'shippingMethods', 'nextOrderNumber'));
    }

    /**
     * API: Obtener productos de un cliente
     * CORRECCIÓN: Eliminado 'where is_active' que causaba Error 500
     */
    public function getClientProducts($clientId)
    {
        try {
            $products = Product::where('client_id', $clientId)
                // ->where('is_active', true) // <--- ELIMINADO: Esta columna no existe en la BD
                ->withSum('inventory as global_stock', 'quantity') 
                ->get()
                ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'sku' => $product->sku,
                        'name' => $product->name,
                        'stock_available' => (float) ($product->global_stock ?? 0),
                        'stock_physical' => 0 
                    ];
                })
                ->values(); // Retornar todos, incluso con stock 0, para que el usuario vea que existen

            return response()->json($products);

        } catch (\Exception $e) {
            Log::error('Error cargando productos de cliente: ' . $e->getMessage());
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'customer_name' => 'required|string|max:255',
            'shipping_address' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $activeBranches = Branch::where('is_active', true)->get();
            $assignedBranch = null;
            $country = $request->country;
            $state   = $request->state;

            foreach ($activeBranches as $branch) {
                if ($branch->hasCoverage($country, $state)) {
                    $assignedBranch = $branch;
                    break; 
                }
            }

            if (!$assignedBranch) {
                $assignedBranch = $activeBranches->first(); 
                if (!$assignedBranch) throw new \Exception("Error: No hay sucursales activas.");
            }

            $needsTransfer = false;
            $supplyBranchId = null;
            $finalStatus = 'pending';
            
            foreach ($request->items as $itemData) {
                $product = Product::find($itemData['product_id']);
                $qtyNeeded = $itemData['qty'];

                $localStock = Inventory::where('branch_id', $assignedBranch->id)
                                       ->where('product_id', $product->id)
                                       ->sum('quantity');

                if ($localStock < $qtyNeeded) {
                    $globalStock = Inventory::where('product_id', $product->id)->sum('quantity');

                    if ($globalStock < $qtyNeeded) {
                        throw new \Exception("Stock insuficiente GLOBAL para: {$product->sku}. Total: {$globalStock}");
                    }

                    $needsTransfer = true;
                    $finalStatus = 'waiting_transfer';

                    if (!$supplyBranchId) {
                        $bestSupplier = Inventory::where('product_id', $product->id)
                                                 ->where('branch_id', '!=', $assignedBranch->id)
                                                 ->where('quantity', '>=', $qtyNeeded)
                                                 ->orderBy('quantity', 'desc')
                                                 ->with('branch')
                                                 ->first();
                        
                        if ($bestSupplier) {
                            $supplyBranchId = $bestSupplier->branch_id;
                        }
                    }
                }
            }

            $transferId = null;
            $isBackorder = false;

            if ($needsTransfer && $supplyBranchId) {
                $transfer = Transfer::create([
                    'origin_branch_id' => $supplyBranchId,
                    'destination_branch_id' => $assignedBranch->id,
                    'transfer_number' => 'TR-AUTO-' . strtoupper(Str::random(8)),
                    'status' => 'pending',
                    'type' => 'cross_docking',
                    'notes' => 'Auto-generado para Orden ' . $request->order_number,
                    'created_by' => Auth::id()
                ]);

                foreach ($request->items as $itemData) {
                    TransferItem::create([
                        'transfer_id' => $transfer->id,
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['qty']
                    ]);
                }

                $transferId = $transfer->id;
                $isBackorder = true;
            }

            $orderNumber = $request->order_number ?? ('ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)));

            $order = Order::create([
                'order_number' => $orderNumber,
                'client_id' => $request->client_id,
                'branch_id' => $assignedBranch->id,
                'transfer_id' => $transferId,
                'status' => $finalStatus,
                'is_backorder' => $isBackorder,
                'customer_name' => $request->customer_name,
                'customer_id_number' => $request->customer_id_number,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'city' => $request->city,
                'state' => $state,
                'country' => $country,
                'customer_zip' => $request->customer_zip,
                'notes' => $request->notes,
                'shipping_method' => $request->shipping_method,
            ]);

            foreach ($request->items as $itemData) {
                 OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'requested_quantity' => $itemData['qty'], 
                    'picked_quantity' => 0
                ]);
            }

            DB::commit();

            $msg = "Pedido {$order->order_number} creado.";
            if ($needsTransfer) {
                $msg .= " Se generó traslado #TR-{$transfer->transfer_number}.";
            }

            return redirect()->route('admin.orders.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order creation failed: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $order = Order::with(['client', 'items.product', 'branch', 'transfer', 'allocations.inventory.location'])->findOrFail($id);
        $user = Auth::user();

        if (!empty($user->client_id) && $order->client_id !== $user->client_id) abort(403);
        if ($user->branch_id && $order->branch_id !== $user->branch_id) abort(403);

        return view('admin.operations.orders.show', compact('order'));
    }

    public function edit($id)
    {
        $order = Order::with('items')->findOrFail($id);
        if ($order->status !== 'pending' && $order->status !== 'waiting_transfer') return back()->with('error', 'No editable.');

        $user = Auth::user();
        $clients = (!empty($user->client_id)) ? Client::where('id', $user->client_id)->get() : Client::orderBy('company_name')->get();
        $products = Product::where('client_id', $order->client_id)->get();
        $countries = Country::orderBy('name')->get();
        $shippingMethods = ShippingMethod::get();

        return view('admin.operations.orders.edit', compact('order', 'clients', 'products', 'countries', 'shippingMethods'));
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        if (!in_array($order->status, ['pending', 'waiting_transfer'])) return back()->with('error', 'No editable.');

        $request->validate(['shipping_address' => 'required|string|max:255']);

        $order->update([
            'shipping_address' => $request->shipping_address,
            'notes' => $request->notes
        ]);

        return redirect()->route('admin.orders.index')->with('success', 'Actualizado.');
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        if (!in_array($order->status, ['pending', 'draft', 'cancelled', 'waiting_transfer'])) return back()->with('error', 'Orden activa, no se puede eliminar.');
        
        $order->delete();
        return redirect()->route('admin.orders.index')->with('success', 'Eliminado.');
    }

    public function cancel($id)
    {
        $order = Order::findOrFail($id);
        if (in_array($order->status, ['shipped', 'delivered'])) return back()->with('error', 'Ya despachado.');

        DB::transaction(function() use ($order) {
            if ($order->status === 'allocated' || $order->status === 'processing') {
                OrderAllocation::where('order_id', $order->id)->delete();
            }
            $order->update(['status' => 'cancelled']);
        });

        return back()->with('success', 'Anulado.');
    }

    public function fulfill(Request $request, $id) {
         // Placeholder para cumplimiento manual si se requiere
         return back()->with('info', 'Función en desarrollo');
    }

    public function printPickingList($id) {
        $order = Order::with(['items.product', 'allocations.inventory.location'])->findOrFail($id);
        return view('admin.operations.orders.picking_list', compact('order'));
    }

    public function getStatesByCountry($countryId)
    {
        return response()->json(State::where('country_id', $countryId)->orderBy('name')->get());
    }
}