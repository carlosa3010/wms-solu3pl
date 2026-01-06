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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        
        $query = Order::with(['client', 'items.product', 'branch'])
            ->latest();

        // Si el usuario tiene un client_id asignado, filtramos sus órdenes
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
        $shippingMethods = collect([]); // Inicializar vacío para evitar error "Undefined variable"

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

        // 2. Obtener Productos con Stock
        try {
            // Primero obtenemos los IDs de productos que tienen stock en la tabla inventory
            $productIdsWithStock = Inventory::where('quantity', '>', 0)
                ->pluck('product_id')
                ->unique();
            
            // Construimos la consulta de productos
            $productsQuery = Product::whereIn('id', $productIdsWithStock);
        } catch (\Exception $e) {
            // Fallback si falla la tabla inventory: mostrar productos activos sin validar stock DB
            Log::error('Error consultando inventario: ' . $e->getMessage());
            $productsQuery = Product::query(); 
        }

        // Si es un usuario cliente, filtramos SOLO sus productos
        if (!empty($user->client_id)) {
            $productsQuery->where('client_id', $user->client_id);
        } else {
            // Si es admin, cargamos la relación cliente
            $productsQuery->with('client'); 
        }

        $products = $productsQuery->get();

        // 3. Países
        try {
            $countries = Country::orderBy('name')->get();
        } catch (\Exception $e) {
            $countries = collect([]); 
        }

        // 4. Métodos de Envío
        try {
            $shippingMethods = ShippingMethod::where('is_active', true)->get();
        } catch (\Exception $e) {
            try {
                $shippingMethods = ShippingMethod::all();
            } catch (\Exception $ex) {
                $shippingMethods = collect([]); // Fallback final si la tabla no existe
            }
        }
        
        return view('admin.operations.orders.create', compact('clients', 'products', 'countries', 'shippingMethods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'shipping_name' => 'required|string|max:255',
            'shipping_address' => 'required|string|max:255',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'required|string|max:100',
            'shipping_country' => 'required|string|max:100',
            'shipping_zip' => 'required|string|max:20',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'shipping_method_id' => 'nullable', // Quitamos exists estricto por si acaso la tabla es opcional
        ]);

        try {
            DB::beginTransaction();

            // 1. Lógica de Cobertura de Sucursal
            $activeBranches = Branch::where('is_active', true)->get();
            $assignedBranch = null;

            foreach ($activeBranches as $branch) {
                if ($branch->hasCoverage($request->shipping_country, $request->shipping_state)) {
                    $assignedBranch = $branch;
                    break; 
                }
            }

            if (!$assignedBranch) {
                return back()->withInput()->withErrors([
                    'shipping_address' => "No hay sedes con cobertura para: {$request->shipping_country} - {$request->shipping_state}. Verifique la configuración."
                ]);
            }

            // 2. Generar Número de Orden
            // Generamos un ID único: ORD-YYYYMMDD-XXXX (ej: ORD-20231025-A1B2C)
            $generatedOrderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));

            // 3. Crear Objeto Order manual (evita problemas de $fillable)
            $order = new Order();
            
            // Asignamos el número. Si la columna en BD es 'number' en vez de 'order_number', intenta asignar ambos por seguridad
            $order->order_number = $generatedOrderNumber;
            // $order->number = $generatedOrderNumber; // Descomentar si tu columna se llama 'number'
            
            $order->client_id = $request->client_id;
            $order->branch_id = $assignedBranch->id;
            $order->status = 'pending';
            
            $order->shipping_name = $request->shipping_name;
            $order->shipping_address = $request->shipping_address;
            $order->shipping_city = $request->shipping_city;
            $order->shipping_state = $request->shipping_state;
            $order->shipping_country = $request->shipping_country;
            $order->shipping_zip = $request->shipping_zip;
            $order->shipping_phone = $request->shipping_phone ?? null;
            
            $order->notes = $request->notes ?? null;
            $order->shipping_method_id = $request->shipping_method_id ?? null;
            
            $order->total_weight = 0;
            $order->total_volume = 0;
            
            $order->save();

            // 4. Procesar Items
            $totalWeight = 0;
            $totalVolume = 0;

            foreach ($request->items as $itemData) {
                $product = Product::find($itemData['product_id']);

                // Validar que el producto pertenezca al cliente de la orden
                if ($product->client_id != $request->client_id) {
                    throw new \Exception("El producto '{$product->name}' no pertenece al cliente seleccionado.");
                }
                
                // Validar Stock en tabla Inventory
                $currentStock = Inventory::where('product_id', $product->id)->sum('quantity');

                if ($currentStock < $itemData['quantity']) {
                    throw new \Exception("Stock insuficiente para: {$product->name} (Disponible: {$currentStock})");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $itemData['quantity'],
                    'unit_price' => 0, 
                ]);

                $totalWeight += ($product->weight ?? 0) * $itemData['quantity'];
                $totalVolume += (($product->width * $product->height * $product->length) ?? 0) * $itemData['quantity'];
            }

            // Actualizar totales
            $order->total_weight = $totalWeight;
            $order->total_volume = $totalVolume;
            $order->save();

            DB::commit();

            return redirect()->route('admin.operations.orders.index')
                ->with('success', "Pedido {$order->order_number} creado exitosamente en sucursal: {$assignedBranch->name}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear pedido: ' . $e->getMessage());
            return back()->withInput()->withErrors(['error' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function show($id)
    {
        $order = Order::with(['client', 'items.product', 'branch', 'allocations.bin.warehouse'])->findOrFail($id);
        
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
        $shippingMethods = collect([]); // Inicializar vacío

        // Clientes
        if (!empty($user->client_id)) {
            $clients = Client::where('id', $user->client_id)->get();
        } else {
            try {
                $clients = Client::where('is_active', true)->get();
            } catch (\Exception $e) {
                $clients = Client::all();
            }
        }
        
        // Productos
        $products = Product::where('client_id', $order->client_id)->get();
        
        // Países
        try {
            $countries = Country::orderBy('name')->get();
        } catch (\Exception $e) {
            $countries = collect([]);
        }

        // Métodos de Envío
        try {
            $shippingMethods = ShippingMethod::where('is_active', true)->get();
        } catch (\Exception $e) {
            try {
                $shippingMethods = ShippingMethod::all();
            } catch (\Exception $ex) {
                $shippingMethods = collect([]);
            }
        }

        return view('admin.operations.orders.edit', compact('order', 'clients', 'products', 'countries', 'shippingMethods'));
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        if ($order->status !== 'pending') {
            return back()->with('error', 'No se puede modificar un pedido que ya está en proceso.');
        }

        $request->validate([
            'shipping_name' => 'required|string|max:255',
            'shipping_address' => 'required|string|max:255',
            // ... validaciones ...
        ]);

        try {
            DB::beginTransaction();

            $order->shipping_name = $request->shipping_name;
            $order->shipping_address = $request->shipping_address;
            $order->shipping_city = $request->shipping_city;
            $order->shipping_state = $request->shipping_state;
            $order->shipping_country = $request->shipping_country;
            $order->shipping_zip = $request->shipping_zip;
            $order->shipping_method_id = $request->shipping_method_id;
            $order->notes = $request->notes;
            $order->save();

            DB::commit();
            return redirect()->route('admin.operations.orders.index')->with('success', 'Pedido actualizado.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $order = Order::findOrFail($id);

        if (!in_array($order->status, ['pending', 'draft', 'cancelled'])) {
            return back()->with('error', 'No se puede eliminar un pedido activo.');
        }

        try {
            $order->delete();
            return redirect()->route('admin.operations.orders.index')->with('success', 'Pedido eliminado correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar: ' . $e->getMessage());
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

    public function pickingList($id)
    {
        $order = Order::with(['items.product', 'allocations.bin'])->findOrFail($id);
        return view('admin.operations.orders.picking_list', compact('order'));
    }
}