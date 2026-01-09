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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\BinAllocator;

class OrderController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $query = Order::with(['client', 'items.product', 'branch'])
            ->latest();

        if (!empty($user->client_id)) {
            $query->where('client_id', $user->client_id);
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
        $shippingMethods = collect([]);

        // 1. Obtener Clientes
        if (!empty($user->client_id)) {
            $clients = Client::where('id', $user->client_id)->get();
        } else {
            try {
                $clients = Client::where('is_active', true)->get();
            } catch (\Exception $e) {
                $clients = Client::all();
            }
        }

        // 2. Productos (Solo con stock físico disponible)
        try {
            $productIdsWithStock = Inventory::where('quantity', '>', 0)
                ->pluck('product_id')
                ->unique();
            $productsQuery = Product::whereIn('id', $productIdsWithStock);
        } catch (\Exception $e) {
            $productsQuery = Product::query(); 
        }

        if (!empty($user->client_id)) {
            $productsQuery->where('client_id', $user->client_id);
        } else {
            $productsQuery->with('client'); 
        }

        $products = $productsQuery->get();

        // 3. Países y Métodos
        $countries = Country::orderBy('name')->get();
        $shippingMethods = ShippingMethod::where('is_active', true)->get();

        $nextOrderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        
        return view('admin.operations.orders.create', compact('clients', 'products', 'countries', 'shippingMethods', 'nextOrderNumber'));
    }

    public function getClientProducts($clientId)
    {
        try {
            // Filtrar productos que tengan inventario
            $productIds = Inventory::where('quantity', '>', 0)
                ->pluck('product_id')
                ->unique();

            $products = Product::where('client_id', $clientId)
                ->whereIn('id', $productIds)
                ->select('id', 'sku', 'name')
                ->get()
                ->map(function($product) {
                    // Calcular disponible REAL (Físico - Comprometido)
                    $physical = Inventory::where('product_id', $product->id)->sum('quantity');
                    
                    $committed = OrderItem::whereHas('order', function($q) {
                        $q->whereIn('status', ['pending', 'allocated', 'processing']);
                    })->where('product_id', $product->id)->sum('quantity');

                    $product->stock_available = max(0, $physical - $committed);
                    return $product;
                });

            return response()->json($products);

        } catch (\Exception $e) {
            Log::error('Error cargando productos de cliente: ' . $e->getMessage());
            return response()->json(['error' => 'Error al cargar productos'], 500);
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

            // 1. Asignar Sucursal (Lógica de Cobertura)
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
                if (!$assignedBranch) throw new \Exception("No hay sucursales activas.");
            }

            // 2. Crear Cabecera de Orden
            $orderNumber = $request->order_number ?? ('ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)));

            $order = new Order();
            $order->order_number = $orderNumber;
            $order->client_id = $request->client_id;
            $order->branch_id = $assignedBranch->id;
            $order->status = 'pending'; // Inicia Reserva Lógica
            
            $order->customer_name = $request->customer_name;
            $order->customer_id_number = $request->customer_id_number ?? 'N/A';
            $order->customer_email = $request->customer_email;
            $order->phone = $request->customer_phone;
            
            $order->shipping_address = $request->shipping_address;
            $order->city = $request->city;
            $order->state = $state;
            $order->country = $country;
            $order->customer_zip = $request->customer_zip;
            
            $order->notes = $request->notes;
            $order->shipping_method = $request->shipping_method;
            
            $order->save();

            // 3. Procesar Ítems y Validar Stock (Reserva Lógica)
            foreach ($request->items as $itemData) {
                if(empty($itemData['product_id'])) continue;

                $product = Product::findOrFail($itemData['product_id']);
                
                // A. Stock Físico
                $physicalStock = Inventory::where('product_id', $product->id)->sum('quantity');

                // B. Stock Comprometido (En otras órdenes activas)
                $committedStock = OrderItem::whereHas('order', function($q) {
                    $q->whereIn('status', ['pending', 'allocated', 'processing']);
                })->where('product_id', $product->id)->sum('quantity');

                $availableToSell = $physicalStock - $committedStock;
                
                if ($availableToSell < $itemData['qty']) {
                    throw new \Exception("Stock insuficiente para: {$product->sku}. Disponible real: {$availableToSell}");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $itemData['qty'], // Esto aumenta el "committedStock" para la próxima orden
                    'quantity_picked' => 0
                ]);
            }

            DB::commit();

            return redirect()->route('admin.orders.index')
                ->with('success', "Pedido {$order->order_number} creado exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating order: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        // Cargar relaciones profundas incluyendo las asignaciones de bines
        $order = Order::with(['client', 'items.product', 'branch', 'allocations.inventory.location'])->findOrFail($id);
        
        $user = Auth::user();
        if (!empty($user->client_id) && $order->client_id !== $user->client_id) {
            abort(403);
        }

        return view('admin.operations.orders.show', compact('order'));
    }

    public function edit($id)
    {
        $order = Order::with('items')->findOrFail($id);
        
        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', 'Solo se pueden editar pedidos pendientes.');
        }

        $user = Auth::user();
        $shippingMethods = collect([]);

        // Clientes
        if (!empty($user->client_id)) {
            $clients = Client::where('id', $user->client_id)->get();
        } else {
            $clients = Client::where('is_active', true)->get();
        }
        
        // Productos del cliente
        $products = Product::where('client_id', $order->client_id)->get();
        $countries = Country::orderBy('name')->get();
        $shippingMethods = ShippingMethod::where('is_active', true)->get();

        return view('admin.operations.orders.edit', compact('order', 'clients', 'products', 'countries', 'shippingMethods'));
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'pending') {
            return back()->with('error', 'No se puede modificar un pedido que ya está en proceso.');
        }

        $request->validate([
            'shipping_address' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $order->shipping_address = $request->shipping_address;
            $order->notes = $request->notes;
            // Aquí podrías agregar lógica para actualizar ítems si fuera necesario (recalculando reserva)
            
            $order->save();

            DB::commit();
            return redirect()->route('admin.orders.index')->with('success', 'Pedido actualizado.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        
        // Solo permitir eliminar si no ha iniciado proceso en bodega
        if (!in_array($order->status, ['pending', 'draft', 'cancelled'])) {
            return back()->with('error', 'No se puede eliminar un pedido activo en bodega.');
        }
        
        // Al eliminar, se libera automáticamente la "Reserva Lógica" al borrarse los OrderItems
        $order->delete();
        
        return redirect()->route('admin.orders.index')->with('success', 'Pedido eliminado y stock liberado.');
    }

    public function cancel($id)
    {
        $order = Order::findOrFail($id);

        if (in_array($order->status, ['shipped', 'delivered'])) {
            return back()->with('error', 'No se puede anular un pedido ya despachado.');
        }

        try {
            DB::beginTransaction();
            
            // Si estaba "Allocated", hay que liberar la reserva física de los bines
            if ($order->status === 'allocated' || $order->status === 'processing') {
                // Usamos el modelo OrderAllocation para borrar las reservas
                \App\Models\OrderAllocation::where('order_id', $order->id)->delete();
            }
            
            // Cambiar estado a Cancelado
            $order->update(['status' => 'cancelled']);
            
            DB::commit();
            return back()->with('success', 'Pedido anulado y stock liberado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando pedido: ' . $e->getMessage());
            return back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        $request->validate([
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled'
        ]);

        $order->update(['status' => $request->status]);

        return back()->with('success', 'Estado del pedido actualizado.');
    }

    public function printPickingList($id) {
        $order = Order::with(['items.product', 'allocations.inventory.location'])->findOrFail($id);
        return view('admin.operations.orders.picking_list', compact('order'));
    }

    public function getStatesByCountry($countryId)
    {
        try {
            $states = State::where('country_id', $countryId)
                ->orderBy('name')
                ->get();

            return response()->json($states);
        } catch (\Exception $e) {
            Log::error('Error cargando estados: ' . $e->getMessage());
            return response()->json(['error' => 'Error interno al cargar estados'], 500);
        }
    }
}