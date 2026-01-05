<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\ASN;
use App\Models\ASNItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RMA;
use App\Models\Invoice; // Asegúrate de que este modelo apunte a la tabla correcta (o PreInvoice si es lo que usas)
use App\Models\PreInvoice; // Agregado para la lógica de facturación nueva
use App\Models\ServiceCharge;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod; 
use App\Models\Client;
use App\Models\Country;
use App\Models\State;
use App\Services\BillingService; // Importamos el servicio de facturación
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientPortalController extends Controller
{
    protected $billingService;

    // Inyectamos el BillingService
    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Dashboard Principal: Resumen operativo y financiero.
     */
    public function dashboard()
    {
        $user = Auth::user();
        $client = $user->client;

        if (!$client) {
            return redirect()->route('login')->with('error', 'Usuario no vinculado a un cliente.');
        }

        $clientId = $client->id;

        // Datos Operativos
        $productsCount = Product::where('client_id', $clientId)->count();
        $pendingRmas = RMA::where('client_id', $clientId)->where('status', 'pending')->count();
        
        // Contar ASNs activos
        $activeAsns = ASN::where('client_id', $clientId)
            ->whereIn('status', ['sent', 'processing', 'receiving'])
            ->count();
        
        $recentOrders = Order::where('client_id', $clientId)->latest()->take(5)->get();
        $stockCount = Inventory::whereHas('product', fn($q) => $q->where('client_id', $clientId))->sum('quantity');

        // Datos Financieros (NUEVO)
        // 1. Gasto Acumulado del Mes (Pre-factura abierta)
        $currentMonthInvoice = PreInvoice::where('client_id', $clientId)
            ->where('period_month', now()->format('Y-m'))
            ->first();
        $currentSpend = $currentMonthInvoice ? $currentMonthInvoice->total_amount : 0;

        // 2. Saldo en Billetera
        $wallet = $this->billingService->getWallet($clientId);

        // 'corteCuenta' se mantiene como referencia histórica o se puede reemplazar por $currentSpend
        $corteCuenta = $currentSpend; 

        return view('client.portal', compact(
            'productsCount', 
            'pendingRmas', 
            'activeAsns', 
            'corteCuenta', // Gasto del mes
            'recentOrders',
            'stockCount',
            'wallet' // Objeto billetera con saldo
        ));
    }

    /**
     * Catálogo: Gestión de SKUs con dimensiones y stock total.
     */
    public function catalog()
    {
        $clientId = auth()->user()->client_id;
        
        $skus = Product::where('client_id', $clientId)
            ->with('category')
            ->withSum('inventory as total_stock', 'quantity')
            ->latest()
            ->get();
            
        $categories = Category::orderBy('name')->get();

        return view('client.catalog', compact('skus', 'categories'));
    }

    /**
     * Registro de nuevo producto.
     */
    public function storeSku(Request $request)
    {
        $clientId = auth()->user()->client_id;

        $validated = $request->validate([
            'sku' => 'required|unique:products,sku',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'weight_kg' => 'nullable|numeric|min:0',
            'length_cm' => 'nullable|numeric|min:0',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        $validated['client_id'] = $clientId;

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        Product::create($validated);

        return redirect()->route('client.catalog')->with('success', 'Producto registrado correctamente.');
    }

    /**
     * Actualizar SKU.
     */
    public function updateSku(Request $request, $id)
    {
        $clientId = auth()->user()->client_id;
        $product = Product::where('id', $id)->where('client_id', $clientId)->firstOrFail();

        $validated = $request->validate([
            'sku' => 'required|unique:products,sku,' . $id,
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'weight_kg' => 'nullable|numeric|min:0',
            'length_cm' => 'nullable|numeric|min:0',
            'width_cm' => 'nullable|numeric|min:0',
            'height_cm' => 'nullable|numeric|min:0',
            'barcode' => 'nullable|string|max:100',
            'description' => 'nullable|string',
        ]);

        if ($request->hasFile('image')) {
            $validated['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($validated);

        return redirect()->route('client.catalog')->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Eliminar SKU si no tiene stock.
     */
    public function destroySku($id)
    {
        $clientId = auth()->user()->client_id;
        $product = Product::where('id', $id)
            ->where('client_id', $clientId)
            ->withSum('inventory as total_stock', 'quantity')
            ->firstOrFail();

        if ($product->total_stock > 0) {
            return redirect()->back()->with('error', 'No puedes eliminar un producto que tiene existencias en inventario.');
        }

        $product->delete();

        return redirect()->route('client.catalog')->with('success', 'Producto eliminado correctamente.');
    }

    /**
     * Inventario por ubicación.
     */
    public function stock()
    {
        $clientId = auth()->user()->client_id;
        $stocks = Inventory::whereHas('product', function($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })->with(['product', 'location.warehouse.branch'])->get();

        return view('client.stock', compact('stocks'));
    }

    /**
     * Exportar Stock PDF.
     */
    public function exportStock()
    {
        $clientId = auth()->user()->client_id;
        $stocks = Inventory::whereHas('product', function($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })->with(['product', 'location.warehouse.branch'])->get();

        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('client.pdf_stock', compact('stocks'));
            return $pdf->download('Inventario_Solu3PL_' . now()->format('d_m_Y') . '.pdf');
        }

        return view('client.pdf_stock', compact('stocks'));
    }

    /**
     * Listado de Pedidos de Salida.
     */
    public function ordersIndex()
    {
        $clientId = auth()->user()->client_id;
        $orders = Order::where('client_id', $clientId)->with('items')->latest()->get();
        return view('client.orders_index', compact('orders'));
    }

    /**
     * Creación de Pedido.
     */
    public function createOrder()
    {
        $clientId = auth()->user()->client_id;
        $products = Product::where('client_id', $clientId)
            ->withSum('inventory as stock_available', 'quantity')
            ->get()
            ->filter(fn($p) => $p->stock_available > 0)
            ->sortBy('sku')->values();

        $shippingMethods = ShippingMethod::where('is_active', true)->get();
        $countries = Country::orderBy('name')->get();

        return view('client.orders_create', compact('products', 'shippingMethods', 'countries'));
    }

    /**
     * Guardar Pedido.
     */
    public function storeOrder(Request $request)
    {
        $clientId = auth()->user()->client_id;
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_address' => 'required|string|max:255',
            'customer_country' => 'required',
            'customer_state' => 'required',
            'items' => 'required|array|min:1',
        ]);

        DB::transaction(function () use ($request, $clientId) {
            $order = Order::create([
                'client_id' => $clientId,
                'order_number' => 'ORD-' . strtoupper(Str::random(6)),
                'reference_number' => $request->reference_number,
                'customer_name' => $request->customer_name,
                'customer_address' => $request->customer_address,
                'customer_city' => $request->customer_city,
                'customer_state' => $request->customer_state,
                'customer_zip' => $request->customer_zip,
                'customer_country' => $request->customer_country,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'shipping_method' => $request->shipping_method,
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            foreach ($request->items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'price' => 0,
                ]);
            }
        });

        return redirect()->route('client.orders.index')->with('success', 'Pedido creado exitosamente.');
    }

    /**
     * Exportar Pedido PDF.
     */
    public function exportOrder($id)
    {
        $clientId = auth()->user()->client_id;
        $order = Order::where('client_id', $clientId)->with(['items.product', 'client'])->findOrFail($id);

        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('client.pdf_order', compact('order'));
            return $pdf->download('Pedido_' . $order->order_number . '.pdf');
        }

        return view('client.pdf_order', compact('order'));
    }

    /**
     * AJAX: Estados por País.
     */
    public function getStatesByCountry($countryId)
    {
        $states = State::where('country_id', $countryId)->orderBy('name')->get();
        return response()->json($states);
    }

    /**
     * ASN: Listado de Avisos de Envío.
     */
    public function asnIndex()
    {
        $clientId = auth()->user()->client_id;
        $asns = ASN::where('client_id', $clientId)->with('items')->latest()->get();
        return view('client.asn_index', compact('asns'));
    }

    /**
     * ASN: Formulario de Creación.
     */
    public function createAsn()
    {
        $clientId = auth()->user()->client_id;
        $products = Product::where('client_id', $clientId)->orderBy('sku')->get();
        return view('client.asn_create', compact('products'));
    }

    /**
     * Almacena el ASN generado por el cliente.
     */
    public function storeAsn(Request $request)
    {
        $clientId = auth()->user()->client_id;

        $request->validate([
            'reference_number' => 'required|string|max:50',
            'expected_arrival_date' => 'required|date|after_or_equal:today',
            'total_packages' => 'required|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        try {
            $asn = DB::transaction(function () use ($request, $clientId) {
                $asnNumber = 'ASN-' . strtoupper(Str::random(8));

                $asn = ASN::create([
                    'client_id' => $clientId,
                    'asn_number' => $asnNumber,
                    'reference_number' => $request->reference_number,
                    'expected_arrival_date' => $request->expected_arrival_date,
                    'total_packages' => $request->total_packages,
                    'notes' => $request->notes,
                    'status' => 'sent',
                ]);

                foreach ($request->items as $item) {
                    ASNItem::create([
                        'asn_id' => $asn->id,
                        'product_id' => $item['product_id'],
                        'expected_quantity' => $item['quantity'],
                        'received_quantity' => 0,
                    ]);
                }
                return $asn;
            });

            return redirect()->route('client.asn.index')->with('success', "ASN {$asn->asn_number} creado correctamente. Procede a imprimir las etiquetas.");

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al procesar el ASN: ' . $e->getMessage()]);
        }
    }

    /**
     * Vista de etiquetas de bulto para el ASN.
     */
    public function printAsnLabels($id)
    {
        $clientId = auth()->user()->client_id;
        $asn = ASN::where('id', $id)->where('client_id', $clientId)->with(['client', 'items.product'])->firstOrFail();
        
        return view('client.asn_label', compact('asn'));
    }

    /**
     * RMA: Gestión de Devoluciones.
     */
    public function rmaIndex()
    {
        $clientId = auth()->user()->client_id;
        $rmas = RMA::where('client_id', $clientId)->with(['items.product', 'order'])->latest()->get();
        return view('client.rma_index', compact('rmas'));
    }

    public function rmaShow($id)
    {
        $clientId = auth()->user()->client_id;
        $rma = RMA::where('client_id', $clientId)->with(['items.product', 'images'])->findOrFail($id);
        return view('client.rma_show', compact('rma'));
    }

    public function rmaAction(Request $request, $id)
    {
        $clientId = auth()->user()->client_id;
        $rma = RMA::where('client_id', $clientId)->findOrFail($id);
        if ($rma->status !== 'waiting_client') return redirect()->back();

        $request->validate(['action' => 'required|in:approve,reject']);
        $status = ($request->action === 'approve') ? 'approved' : 'disputed';
        
        $rma->update([
            'status' => $status,
            'client_notes' => 'Acción de cliente el ' . now()
        ]);

        return redirect()->back()->with('success', 'Respuesta enviada.');
    }

    /**
     * Facturación y Reporte de Pagos (Vista Principal).
     * Muestra facturas mensuales y billetera.
     */
    public function billing()
    {
        $client = Auth::user()->client;
        
        // Obtenemos facturas cerradas o emitidas (usando PreInvoice como modelo base)
        $invoices = PreInvoice::where('client_id', $client->id)
            ->whereIn('status', ['closed', 'invoiced']) // Solo mostrar facturas listas
            ->orderBy('period_month', 'desc')
            ->get();
            
        // Obtenemos la billetera y sus transacciones
        $wallet = $this->billingService->getWallet($client->id);
        
        // Métodos de pago disponibles para reportar
        $paymentMethodsRaw = PaymentMethod::where('is_active', true)->get();
        $paymentMethods = [];
        foreach($paymentMethodsRaw as $method) {
            $paymentMethods[$method->id] = ['name' => $method->name, 'details' => $method->details];
        }
        
        return view('client.billing_index', compact('invoices', 'wallet', 'paymentMethods'));
    }

    /**
     * Reportar Pago (Factura o Recarga).
     */
    public function storePayment(Request $request)
    {
        $clientId = auth()->user()->client_id;
        
        $request->validate([
            'payment_method' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:1',
            'proof_file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'type' => 'required|in:invoice,wallet', // Nuevo: Tipo de pago
            'invoice_id' => 'required_if:type,invoice|exists:pre_invoices,id', // Requerido si es factura
        ]);
        
        $path = $request->file('proof_file')->store('payments', 'public');
        $methodName = PaymentMethod::find($request->payment_method)->name;

        // Construir referencia o concepto
        $concept = $request->type === 'wallet' 
            ? 'Recarga de Billetera' 
            : 'Pago Factura #' . $request->invoice_id;

        DB::table('payments')->insert([
            'client_id' => $clientId,
            'amount' => $request->amount,
            'payment_method' => $methodName, 
            'payment_date' => $request->payment_date ?? now(),
            'reference' => $request->reference ?? $concept,
            'proof_path' => $path,
            'status' => 'pending', // Pendiente de aprobación por admin
            'notes' => json_encode([
                'type' => $request->type, 
                'invoice_id' => $request->invoice_id
            ]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('client.billing.index')->with('success', 'Pago reportado correctamente. Esperando validación.');
    }

    /**
     * Solicitud de Retiro de Billetera.
     */
    public function requestWithdrawal(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:10']);
        
        try {
            $clientId = Auth::user()->client_id;
            // Llamamos al servicio para procesar la lógica del retiro y fee
            $fee = $this->billingService->requestWithdrawal($clientId, $request->amount);
            
            return back()->with('success', "Retiro de $$request->amount solicitado. Se descontó un fee de $$fee de tu saldo.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Descargar Pre-factura (Servicios acumulados del mes actual).
     */
    public function downloadPreInvoice()
    {
        $clientId = auth()->user()->client_id;
        
        // Buscar la pre-factura abierta del mes actual
        $preInvoice = PreInvoice::where('client_id', $clientId)
            ->where('period_month', now()->format('Y-m'))
            ->with('details')
            ->firstOrFail();
            
        $data = [
            'client' => $preInvoice->client,
            'items' => $preInvoice->details, // Usamos la relación 'details' de PreInvoice
            'total' => $preInvoice->total_amount,
            'title' => 'REPORTE DE CONSUMO (MES ACTUAL)',
            'is_draft' => true,
            'date' => now()->format('d/m/Y')
        ];
        
        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
             $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.billing.pdf_template', $data);
             return $pdf->download('Corte_Cuenta_' . now()->format('Y_m') . '.pdf');
        }
        
        return view('admin.billing.pdf_template', $data);
    }

    public function api() { return view('client.api'); }
    public function support() { return view('client.support'); }
}