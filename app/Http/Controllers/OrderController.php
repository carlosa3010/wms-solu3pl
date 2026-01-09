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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Listado de Órdenes
     */
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

    /**
     * Formulario de Creación
     */
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

        // 2. Productos (Pre-carga básica, la lógica pesada va en getClientProducts via AJAX)
        $products = collect([]); 

        // 3. Países y Métodos
        $countries = Country::orderBy('name')->get();
        $shippingMethods = ShippingMethod::where('is_active', true)->get();

        $nextOrderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        
        return view('admin.operations.orders.create', compact('clients', 'products', 'countries', 'shippingMethods', 'nextOrderNumber'));
    }

    /**
     * API: Obtener productos de un cliente con su Stock Disponible Real
     */
    public function getClientProducts($clientId)
    {
        try {
            // Optimización: Solo traer productos activos
            $products = Product::where('client_id', $clientId)
                ->where('is_active', true)
                ->get()
                ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'sku' => $product->sku,
                        'name' => $product->name,
                        'stock_available' => $product->available_stock, // Usa el accessor del Modelo Product
                        'stock_physical' => $product->physical_stock    // Dato informativo
                    ];
                })
                // Opcional: Filtrar solo los que tienen stock > 0 para evitar pedidos imposibles
                ->filter(function($p) {
                    return $p['stock_available'] > 0;
                })
                ->values();

            return response()->json($products);

        } catch (\Exception $e) {
            Log::error('Error cargando productos de cliente: ' . $e->getMessage());
            return response()->json(['error' => 'Error al cargar productos'], 500);
        }
    }

    /**
     * Guardar Orden (Reserva Lógica de Stock)
     */
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

            // Fallback a la primera sucursal si no hay cobertura específica
            if (!$assignedBranch) {
                $assignedBranch = $activeBranches->first();
                if (!$assignedBranch) throw new \Exception("No hay sucursales activas configuradas.");
            }

            // 2. Crear Cabecera de Orden
            $orderNumber = $request->order_number ?? ('ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)));

            $order = new Order();
            $order->order_number = $orderNumber;
            $order->client_id = $request->client_id;
            $order->branch_id = $assignedBranch->id;
            $order->status = 'pending'; // Estado inicial: Pendiente de Picking (Reserva Lógica)
            
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

            // 3. Procesar Ítems y Validar Stock Estricto
            foreach ($request->items as $itemData) {
                if(empty($itemData['product_id'])) continue;

                // Bloqueamos la fila del producto para lectura consistente durante la transacción
                $product = Product::lockForUpdate()->find($itemData['product_id']);
                
                // Usamos el cálculo centralizado del modelo Product
                if ($product->available_stock < $itemData['qty']) {
                    throw new \Exception("Stock insuficiente para: {$product->sku}. Disponible: {$product->available_stock}, Solicitado: {$itemData['qty']}");
                }

                // CORRECCIÓN: Usamos 'requested_quantity' según la migración de DB
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'requested_quantity' => $itemData['qty'], 
                    'picked_quantity' => 0
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

    /**
     * Ver Detalle de Orden
     */
    public function show($id)
    {
        // Cargar relaciones profundas para ver detalles y asignaciones de picking
        $order = Order::with([
            'client', 
            'items.product', 
            'branch', 
            'allocations.inventory.location' // Para ver de dónde se va a sacar el stock
        ])->findOrFail($id);
        
        $user = Auth::user();
        if (!empty($user->client_id) && $order->client_id !== $user->client_id) {
            abort(403);
        }

        return view('admin.operations.orders.show', compact('order'));
    }

    /**
     * Editar Orden (Solo si está pendiente)
     */
    public function edit($id)
    {
        $order = Order::with('items')->findOrFail($id);
        
        if ($order->status !== 'pending') {
            return redirect()->back()->with('error', 'Solo se pueden editar pedidos pendientes (antes de asignación).');
        }

        $user = Auth::user();
        
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

    /**
     * Actualizar Orden
     */
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
            
            // Nota: Si permites editar ítems aquí, debes implementar la lógica de 
            // recalcular la reserva de stock (borrar anteriores, validar nuevos, etc.)
            
            $order->save();

            DB::commit();
            return redirect()->route('admin.orders.index')->with('success', 'Pedido actualizado.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar Orden (Soft Delete) y Liberar Reserva
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        
        // Solo permitir eliminar si no ha iniciado proceso físico en bodega
        if (!in_array($order->status, ['pending', 'draft', 'cancelled'])) {
            return back()->with('error', 'No se puede eliminar un pedido activo en bodega.');
        }
        
        // Al eliminar la orden, los OrderItems se borran (o soft-delete), 
        // liberando automáticamente el "Committed Stock" calculado en el modelo Product.
        $order->delete();
        
        return redirect()->route('admin.orders.index')->with('success', 'Pedido eliminado y stock liberado.');
    }

    /**
     * Cancelar Orden (Lógica avanzada con liberación de Allocation)
     */
    public function cancel($id)
    {
        $order = Order::findOrFail($id);

        if (in_array($order->status, ['shipped', 'delivered'])) {
            return back()->with('error', 'No se puede anular un pedido ya despachado.');
        }

        try {
            DB::beginTransaction();
            
            // 1. Liberar Reserva Física (Allocations) si existían
            // Esto "desbloquea" los bines específicos en bodega
            if ($order->status === 'allocated' || $order->status === 'processing') {
                OrderAllocation::where('order_id', $order->id)->delete();
            }
            
            // 2. Cambiar estado a Cancelado
            // Esto libera la Reserva Lógica (el CommittedStock bajará)
            $order->update(['status' => 'cancelled']);
            
            DB::commit();
            return back()->with('success', 'Pedido anulado y stock liberado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error anulando pedido: ' . $e->getMessage());
            return back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }

    /**
     * Actualizar Estado Manualmente (Uso administrativo)
     */
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        $request->validate([
            'status' => 'required|string|in:pending,processing,shipped,delivered,cancelled'
        ]);

        // Si se cancela manualmente, ejecutar la lógica de limpieza
        if ($request->status === 'cancelled') {
            return $this->cancel($id);
        }

        $order->update(['status' => $request->status]);

        return back()->with('success', 'Estado del pedido actualizado.');
    }

    /**
     * Imprimir Lista de Picking (PDF/Vista)
     */
    public function printPickingList($id) {
        $order = Order::with(['items.product', 'allocations.inventory.location'])->findOrFail($id);
        return view('admin.operations.orders.picking_list', compact('order'));
    }

    /**
     * API: Obtener estados/provincias por país
     */
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