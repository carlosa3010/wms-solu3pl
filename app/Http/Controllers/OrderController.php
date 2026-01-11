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
    /**
     * Listado de Órdenes
     * Filtra según el rol del usuario (Admin Global vs Operario de Sucursal)
     */
    public function index()
    {
        $user = Auth::user();
        
        $query = Order::with(['client', 'items.product', 'branch', 'transfer'])
            ->latest();

        // Filtro: Si es cliente externo
        if (!empty($user->client_id)) {
            $query->where('client_id', $user->client_id);
        }
        
        // Filtro: Si es operario de almacén (limitado a su sucursal)
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
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
            $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        }

        // 2. Productos y Datos Auxiliares
        $products = collect([]); 
        $countries = Country::orderBy('name')->get();
        $shippingMethods = ShippingMethod::where('is_active', true)->get();

        $nextOrderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
        
        return view('admin.operations.orders.create', compact('clients', 'products', 'countries', 'shippingMethods', 'nextOrderNumber'));
    }

    /**
     * API: Obtener productos de un cliente con cálculo seguro de Stock
     * Soluciona el error de consulta reportado.
     */
    public function getClientProducts($clientId)
    {
        try {
            // Consulta optimizada usando joins para sumar stock directamente desde Inventario
            $products = Product::where('client_id', $clientId)
                ->where('is_active', true)
                ->withSum('inventories as global_stock', 'quantity') // Suma eficiente
                ->get()
                ->map(function($product) {
                    return [
                        'id' => $product->id,
                        'sku' => $product->sku,
                        'name' => $product->name,
                        'stock_available' => (float) $product->global_stock, // Stock Global Real
                        'stock_physical' => 0 // Dato referencial, se puede detallar si se requiere
                    ];
                })
                ->filter(function($p) {
                    // Opcional: Mostrar solo productos con stock para evitar errores de pedido
                    return $p['stock_available'] > 0;
                })
                ->values();

            return response()->json($products);

        } catch (\Exception $e) {
            Log::error('Error cargando productos de cliente: ' . $e->getMessage());
            return response()->json(['error' => 'Error al cargar productos: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Guardar Orden con Lógica de Traslado Automático
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

            // 1. Determinar Bodega de Destino (Coverage)
            // Esta es la bodega que debería despachar la última milla
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

            // Fallback a bodega principal si no hay cobertura específica
            if (!$assignedBranch) {
                $assignedBranch = $activeBranches->first(); 
                if (!$assignedBranch) throw new \Exception("Error Crítico: No hay sucursales activas configuradas.");
            }

            // 2. Analizar Disponibilidad de Stock y Necesidad de Traslado
            $needsTransfer = false;
            $supplyBranchId = null;
            $finalStatus = 'pending'; // Estado por defecto
            
            // Verificamos ítem por ítem
            foreach ($request->items as $itemData) {
                $product = Product::find($itemData['product_id']);
                $qtyNeeded = $itemData['qty'];

                // A. Verificar Stock Local (En la bodega asignada)
                $localStock = Inventory::where('branch_id', $assignedBranch->id)
                                       ->where('product_id', $product->id)
                                       ->sum('quantity');

                if ($localStock < $qtyNeeded) {
                    // B. Verificar Stock Global
                    $globalStock = Inventory::where('product_id', $product->id)->sum('quantity');

                    if ($globalStock < $qtyNeeded) {
                        throw new \Exception("Stock insuficiente GLOBAL para: {$product->sku}. Disponible Total: {$globalStock}");
                    }

                    // C. Si hay global pero no local, necesitamos un traslado
                    $needsTransfer = true;
                    $finalStatus = 'waiting_transfer';

                    // D. Buscar mejor bodega proveedora (la que tenga más stock)
                    // NOTA: Para simplificar, buscamos un único proveedor para toda la orden por ahora
                    if (!$supplyBranchId) {
                        $bestSupplier = Inventory::where('product_id', $product->id)
                                                 ->where('branch_id', '!=', $assignedBranch->id)
                                                 ->where('quantity', '>=', $qtyNeeded)
                                                 ->orderBy('quantity', 'desc')
                                                 ->with('branch')
                                                 ->first();
                        
                        if ($bestSupplier) {
                            $supplyBranchId = $bestSupplier->branch_id;
                        } else {
                            // Caso complejo: Stock fragmentado en varias bodegas.
                            // Por ahora lanzamos error para obligar a consolidar manualmente o simplificar lógica.
                            throw new \Exception("El stock de {$product->sku} está disponible pero fragmentado. No se puede generar traslado automático único.");
                        }
                    }
                }
            }

            // 3. Crear Transferencia Automática (Si es necesaria)
            $transferId = null;
            $isBackorder = false;

            if ($needsTransfer && $supplyBranchId) {
                $transfer = Transfer::create([
                    'origin_branch_id' => $supplyBranchId,
                    'destination_branch_id' => $assignedBranch->id,
                    'transfer_number' => 'TR-AUTO-' . strtoupper(Str::random(8)),
                    'status' => 'pending', // Requiere picking en origen
                    'type' => 'cross_docking', // Importante para diferenciar en reportes
                    'notes' => 'Generado automáticamente para Orden ' . $request->order_number,
                    'created_by' => Auth::id()
                ]);

                // Agregar items al traslado
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

            // 4. Crear la Orden
            $orderNumber = $request->order_number ?? ('ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5)));

            $order = Order::create([
                'order_number' => $orderNumber,
                'client_id' => $request->client_id,
                'branch_id' => $assignedBranch->id, // La orden pertenece a la bodega destino (quien despacha al cliente)
                'transfer_id' => $transferId, // Vinculación
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

            // 5. Crear Items de la Orden
            foreach ($request->items as $itemData) {
                 OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $itemData['product_id'],
                    'requested_quantity' => $itemData['qty'], 
                    'picked_quantity' => 0
                ]);
            }

            DB::commit();

            $msg = "Pedido {$order->order_number} creado exitosamente.";
            if ($needsTransfer) {
                $msg .= " Se generó automáticamente el traslado #TR-{$transfer->transfer_number} para abastecer el stock.";
            }

            return redirect()->route('admin.orders.index')->with('success', $msg);

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
        // Cargar relaciones profundas incluyendo el traslado si existe
        $order = Order::with([
            'client', 
            'items.product', 
            'branch', 
            'transfer', // Ver estado del traslado vinculado
            'allocations.inventory.location'
        ])->findOrFail($id);
        
        $user = Auth::user();

        // Validar acceso cliente
        if (!empty($user->client_id) && $order->client_id !== $user->client_id) {
            abort(403);
        }

        // Validar acceso sucursal
        if ($user->branch_id && $order->branch_id !== $user->branch_id) {
            abort(403, 'No tiene permiso para ver órdenes de otra sucursal.');
        }

        return view('admin.operations.orders.show', compact('order'));
    }

    /**
     * Editar Orden (Solo si está pendiente)
     */
    public function edit($id)
    {
        $order = Order::with('items')->findOrFail($id);
        
        if ($order->status !== 'pending' && $order->status !== 'waiting_transfer') {
            return redirect()->back()->with('error', 'Solo se pueden editar pedidos pendientes.');
        }

        $user = Auth::user();
        
        if (!empty($user->client_id)) {
            $clients = Client::where('id', $user->client_id)->get();
        } else {
            $clients = Client::where('is_active', true)->get();
        }
        
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

        // Permitir edición si está esperando traslado
        if (!in_array($order->status, ['pending', 'waiting_transfer'])) {
            return back()->with('error', 'No se puede modificar un pedido en proceso avanzado.');
        }

        $request->validate([
            'shipping_address' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();
            $order->shipping_address = $request->shipping_address;
            $order->notes = $request->notes;
            $order->save();
            DB::commit();

            return redirect()->route('admin.orders.index')->with('success', 'Pedido actualizado.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar Orden
     */
    public function destroy($id)
    {
        $order = Order::findOrFail($id);
        
        if (!in_array($order->status, ['pending', 'draft', 'cancelled', 'waiting_transfer'])) {
            return back()->with('error', 'No se puede eliminar un pedido activo en bodega.');
        }
        
        // Si tiene un traslado automático pendiente, advertir o cancelar (Lógica opcional)
        if ($order->transfer_id) {
            // Podríamos cancelar el traslado también, pero por seguridad solo desvinculamos o avisamos
            // Opción: $order->transfer->update(['status' => 'cancelled']);
        }

        $order->delete();
        
        return redirect()->route('admin.orders.index')->with('success', 'Pedido eliminado.');
    }

    /**
     * Cancelar Orden
     */
    public function cancel($id)
    {
        $order = Order::findOrFail($id);

        if (in_array($order->status, ['shipped', 'delivered'])) {
            return back()->with('error', 'No se puede anular un pedido ya despachado.');
        }

        try {
            DB::beginTransaction();
            
            // 1. Liberar Reserva Física (Allocations)
            if ($order->status === 'allocated' || $order->status === 'processing') {
                OrderAllocation::where('order_id', $order->id)->delete();
            }
            
            // 2. Si tenía traslado vinculado, ¿qué hacemos? 
            // Por ahora solo cancelamos la orden. El traslado queda como "stock de reposición" para la bodega.
            
            $order->update(['status' => 'cancelled']);
            
            DB::commit();
            return back()->with('success', 'Pedido anulado y stock liberado correctamente.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al anular: ' . $e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        $request->validate([
            'status' => 'required|string'
        ]);

        if ($request->status === 'cancelled') {
            return $this->cancel($id);
        }

        $order->update(['status' => $request->status]);

        return back()->with('success', 'Estado del pedido actualizado.');
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