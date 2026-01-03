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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Muestra el listado principal de órdenes.
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
     * ACCIÓN MANUAL: Permite forzar o reintentar la asignación de stock.
     */
    public function executeAllocation($id)
    {
        $order = Order::with(['items.product', 'branch'])->findOrFail($id);
        
        if (!$order->branch_id) {
            return back()->withErrors(['error' => 'La orden no tiene una sede asignada.']);
        }

        try {
            $this->performStockAllocation($order);
            return back()->with('success', 'Stock re-asignado correctamente en la sede: ' . $order->branch->name);
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Fallo en la asignación: ' . $e->getMessage()]);
        }
    }

    /**
     * CEREBRO DE PICKING: Busca stock real y lo reserva.
     */
    private function performStockAllocation(Order $order)
    {
        DB::transaction(function() use ($order) {
            foreach($order->items as $item) {
                foreach($item->allocations as $alloc) {
                    Inventory::where('location_id', $alloc->location_id)
                             ->where('product_id', $item->product_id)
                             ->increment('quantity', $alloc->quantity);
                    $alloc->delete();
                }
            }

            foreach($order->items as $item) {
                $needed = $item->requested_quantity;
                
                $availableStock = Inventory::where('product_id', $item->product_id)
                    ->where('quantity', '>', 0)
                    ->whereHas('location.warehouse', function($q) use ($order) {
                        $q->where('branch_id', $order->branch_id);
                    })
                    ->orderByRaw('COALESCE(created_at, id) ASC')
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
            $order->update(['status' => 'allocated']);
        });
    }

    /**
     * Muestra el detalle de la orden.
     */
    public function show($id)
    {
        $order = Order::with(['client', 'items.product', 'branch'])->findOrFail($id);
        return view('admin.operations.orders.show', compact('order'));
    }

    /**
     * Formulario de creación.
     */
    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        $nextOrderNumber = 'ORD-' . strtoupper(Str::random(6));
        
        // Consumimos la lista maestra de estados desde una fuente centralizada (Model Branch)
        $states = defined('App\Models\Branch::VENEZUELA_STATES') 
                  ? Branch::VENEZUELA_STATES 
                  : ['Amazonas', 'Anzoátegui', 'Apure', 'Aragua', 'Barinas', 'Bolívar', 'Carabobo', 'Cojedes', 'Delta Amacuro', 'Distrito Capital', 'Falcón', 'Guárico', 'Lara', 'Mérida', 'Miranda', 'Monagas', 'Nueva Esparta', 'Portuguesa', 'Sucre', 'Táchira', 'Trujillo', 'Vargas', 'Yaracuy', 'Zulia'];

        return view('admin.operations.orders.create', compact('clients', 'nextOrderNumber', 'states'));
    }

    /**
     * Almacena la orden y ejecuta la ASIGNACIÓN AUTOMÁTICA.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'order_number' => 'required|unique:orders,order_number',
            'customer_name' => 'required|string',
            'customer_id_number' => 'required|string',
            'shipping_address' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'country' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        $assignedBranchId = $this->determineBestBranch($request->state, $request->country);

        try {
            $order = DB::transaction(function () use ($request, $assignedBranchId) {
                $order = Order::create([
                    'order_number' => $request->order_number,
                    'client_id' => $request->client_id,
                    'branch_id' => $assignedBranchId,
                    'customer_name' => $request->customer_name,
                    'customer_id_number' => $request->customer_id_number,
                    'customer_email' => $request->customer_email,
                    'shipping_address' => $request->shipping_address,
                    'city' => $request->city,
                    'state' => $request->state,
                    'country' => $request->country,
                    'phone' => $request->phone,
                    'shipping_method' => $request->shipping_method,
                    'notes' => $request->notes,
                    'status' => 'pending'
                ]);

                foreach ($request->items as $itemData) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemData['product_id'],
                        'requested_quantity' => $itemData['qty'],
                    ]);
                }
                return $order;
            });

            $this->performStockAllocation($order);

            return redirect()->route('admin.orders.index')->with('success', 'Pedido registrado y stock reservado automáticamente.');

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al procesar el pedido: ' . $e->getMessage()]);
        }
    }

    /**
     * Anula el pedido y devuelve el stock a los bines.
     */
    public function destroy($id)
    {
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

        return redirect()->route('admin.orders.index')->with('success', 'Orden anulada y stock liberado correctamente.');
    }

    /**
     * Genera la vista de Picking List.
     */
    public function printPickingList($id)
    {
        $order = Order::with(['client', 'items.product', 'items.allocations.location', 'branch'])->findOrFail($id);
        return view('admin.operations.orders.picking_list', compact('order'));
    }

    /**
     * INTELIGENCIA GEOGRÁFICA: Determina la sede óptima.
     * Esta lógica consume la configuración que crearemos en el nuevo módulo.
     */
    private function determineBestBranch($state, $country)
    {
        // 1. Caso Exportación: Buscamos la primera sede activa con permiso de exportación
        if (strtolower($country) !== 'venezuela') {
            $exportBranch = Branch::where('can_export', true)->where('is_active', true)->first();
            if ($exportBranch) return $exportBranch->id;
        }
        
        // 2. Caso Nacional: Buscamos la sede que cubra el estado solicitado
        $localBranch = Branch::where('is_active', true)
                     ->whereJsonContains('covered_states', $state)
                     ->value('id');

        // 3. Fallback: Si no hay cobertura específica, asignamos la sede principal (primera activa)
        return $localBranch ?? Branch::where('is_active', true)->first()->id;
    }
}