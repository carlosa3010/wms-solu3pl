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
use App\Models\Payment;
use App\Models\PreInvoice;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod; 
use App\Models\Client;
use App\Models\Country;
use App\Models\State;
use App\Services\BillingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientPortalController extends Controller
{
    protected $billingService;

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

        // --- Datos Operativos ---
        $productsCount = Product::where('client_id', $clientId)->count();
        $pendingRmas = RMA::where('client_id', $clientId)->where('status', 'pending')->count();
        $activeAsns = ASN::where('client_id', $clientId)
            ->whereIn('status', ['sent', 'processing', 'receiving'])
            ->count();
        
        $recentOrders = Order::where('client_id', $clientId)->latest()->take(5)->get();
        $stockCount = Inventory::whereHas('product', fn($q) => $q->where('client_id', $clientId))->sum('quantity');

        // --- Datos Financieros ---
        // 1. Gasto Acumulado del Mes (Pre-factura abierta)
        $currentMonthInvoice = PreInvoice::where('client_id', $clientId)
            ->where('period_month', now()->format('Y-m'))
            ->first();
        $corteCuenta = $currentMonthInvoice ? $currentMonthInvoice->total_amount : 0;

        // 2. Saldo en Billetera y Métodos de Pago para el Modal
        $wallet = $this->billingService->getWallet($clientId);
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        return view('client.portal', compact(
            'productsCount', 
            'pendingRmas', 
            'activeAsns', 
            'corteCuenta',
            'recentOrders',
            'stockCount',
            'wallet',
            'paymentMethods'
        ));
    }

    /**
     * Catálogo: Gestión de SKUs.
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

    public function destroySku($id)
    {
        $clientId = auth()->user()->client_id;
        $product = Product::where('id', $id)->where('client_id', $clientId)->withSum('inventory as total_stock', 'quantity')->firstOrFail();

        if ($product->total_stock > 0) {
            return redirect()->back()->with('error', 'No puedes eliminar un producto que tiene existencias en inventario.');
        }

        $product->delete();
        return redirect()->route('client.catalog')->with('success', 'Producto eliminado correctamente.');
    }

    /**
     * Inventario y Exportación.
     */
    public function stock()
    {
        $clientId = auth()->user()->client_id;
        $stocks = Inventory::whereHas('product', fn($q) => $q->where('client_id', $clientId))
            ->with(['product', 'location.warehouse.branch'])->get();
        return view('client.stock', compact('stocks'));
    }

    public function exportStock()
    {
        $clientId = auth()->user()->client_id;
        $stocks = Inventory::whereHas('product', fn($q) => $q->where('client_id', $clientId))
            ->with(['product', 'location.warehouse.branch'])->get();

        if (class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('client.pdf_stock', compact('stocks'));
            return $pdf->download('Inventario_Solu3PL_' . now()->format('d_m_Y') . '.pdf');
        }
        return view('client.pdf_stock', compact('stocks'));
    }

    /**
     * Pedidos de Salida.
     */
    public function ordersIndex()
    {
        $clientId = auth()->user()->client_id;
        $orders = Order::where('client_id', $clientId)->with('items')->latest()->get();
        return view('client.orders_index', compact('orders'));
    }

    public function createOrder()
    {
        $clientId = auth()->user()->client_id;
        $products = Product::where('client_id', $clientId)
            ->withSum('inventory as stock_available', 'quantity')
            ->get()->filter(fn($p) => $p->stock_available > 0)->sortBy('sku')->values();

        $shippingMethods = ShippingMethod::where('is_active', true)->get();
        $countries = Country::orderBy('name')->get();

        return view('client.orders_create', compact('products', 'shippingMethods', 'countries'));
    }

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

    public function getStatesByCountry($countryId)
    {
        $states = State::where('country_id', $countryId)->orderBy('name')->get();
        return response()->json($states);
    }

    /**
     * ASN (Aviso de Envío).
     */
    public function asnIndex()
    {
        $clientId = auth()->user()->client_id;
        $asns = ASN::where('client_id', $clientId)->with('items')->latest()->get();
        return view('client.asn_index', compact('asns'));
    }

    public function createAsn()
    {
        $clientId = auth()->user()->client_id;
        $products = Product::where('client_id', $clientId)->orderBy('sku')->get();
        return view('client.asn_create', compact('products'));
    }

    public function storeAsn(Request $request)
    {
        $clientId = auth()->user()->client_id;
        $request->validate([
            'reference_number' => 'required|string|max:50',
            'expected_arrival_date' => 'required|date|after_or_equal:today',
            'total_packages' => 'required|integer|min:1',
            'items' => 'required|array|min:1',
        ]);

        try {
            $asn = DB::transaction(function () use ($request, $clientId) {
                $asn = ASN::create([
                    'client_id' => $clientId,
                    'asn_number' => 'ASN-' . strtoupper(Str::random(8)),
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
            return redirect()->route('client.asn.index')->with('success', "ASN {$asn->asn_number} creado correctamente.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function printAsnLabels($id)
    {
        $clientId = auth()->user()->client_id;
        $asn = ASN::where('id', $id)->where('client_id', $clientId)->with(['client', 'items.product'])->firstOrFail();
        return view('client.asn_label', compact('asn'));
    }

    /**
     * RMA (Devoluciones).
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
        $rma->update([
            'status' => ($request->action === 'approve') ? 'approved' : 'disputed',
            'client_notes' => 'Acción de cliente el ' . now()
        ]);
        return redirect()->back()->with('success', 'Respuesta enviada.');
    }

    /**
     * Facturación y Gestión de Billetera.
     */
    public function billing()
    {
        $client = Auth::user()->client;
        $invoices = PreInvoice::where('client_id', $client->id)
            ->whereIn('status', ['closed', 'invoiced'])
            ->orderBy('period_month', 'desc')->get();
            
        $wallet = $this->billingService->getWallet($client->id);
        $paymentMethods = PaymentMethod::where('is_active', true)->get();
        
        return view('client.billing_index', compact('invoices', 'wallet', 'paymentMethods'));
    }

    /**
     * Reportar Pago o Recarga.
     */
    public function storePayment(Request $request)
    {
        $clientId = auth()->user()->client_id;
        
        $request->validate([
            'payment_method_id' => 'required|exists:payment_methods,id',
            'amount' => 'required|numeric|min:1',
            'proof_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'type' => 'required|in:invoice,wallet_recharge',
            'invoice_id' => 'required_if:type,invoice|exists:pre_invoices,id',
            'reference' => 'required|string|max:100',
        ]);
        
        $path = $request->hasFile('proof_file') ? $request->file('proof_file')->store('payments', 'public') : null;
        $method = PaymentMethod::find($request->payment_method_id);

        $concept = $request->type === 'wallet_recharge' 
            ? 'Recarga de Billetera' 
            : 'Pago Factura #' . ($request->invoice_id ?? 'N/A');

        Payment::create([
            'client_id' => $clientId,
            'amount' => $request->amount,
            'payment_method' => $method->name, 
            'payment_date' => $request->payment_date ?? now(),
            'reference' => $request->reference,
            'proof_path' => $path,
            'status' => 'pending',
            'notes' => json_encode([
                'type' => ($request->type === 'wallet_recharge') ? 'wallet' : 'invoice', 
                'invoice_id' => $request->invoice_id,
                'user_notes' => $request->notes
            ]),
        ]);

        return redirect()->back()->with('success', 'Pago reportado correctamente. El administrador validará la transacción.');
    }

    /**
     * Solicitud de Retiro.
     */
    public function requestWithdrawal(Request $request)
    {
        $request->validate(['amount' => 'required|numeric|min:10']);
        try {
            $clientId = Auth::user()->client_id;
            $fee = $this->billingService->requestWithdrawal($clientId, $request->amount);
            return back()->with('success', "Retiro de $$request->amount solicitado. Fee: $$fee.");
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Descarga de Pre-factura (Corte actual).
     */
    public function downloadPreInvoice()
    {
        $clientId = auth()->user()->client_id;
        $preInvoice = PreInvoice::where('client_id', $clientId)
            ->where('period_month', now()->format('Y-m'))
            ->with(['details', 'client'])
            ->firstOrFail();
            
        $data = [
            'client' => $preInvoice->client,
            'items' => $preInvoice->details,
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