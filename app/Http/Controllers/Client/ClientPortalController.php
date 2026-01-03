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
use App\Models\Invoice;
use App\Models\ServiceCharge;
use App\Models\Category;
use App\Models\PaymentMethod;
use App\Models\ShippingMethod; 
use App\Models\Client;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ClientPortalController extends Controller
{
    /**
     * Dashboard Principal: Resumen operativo y financiero.
     */
    public function dashboard()
    {
        $clientId = auth()->user()->client_id;
        if (!$clientId) {
            return redirect()->route('login')->with('error', 'Usuario no vinculado a un cliente.');
        }

        $productsCount = Product::where('client_id', $clientId)->count();
        $pendingRmas = RMA::where('client_id', $clientId)->where('status', 'pending')->count();
        
        // Contar ASNs que están en camino o procesándose
        $activeAsns = ASN::where('client_id', $clientId)
            ->whereIn('status', ['sent', 'processing', 'receiving'])
            ->count();
        
        $corteCuenta = ServiceCharge::where('client_id', $clientId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $recentOrders = Order::where('client_id', $clientId)->latest()->take(5)->get();

        return view('client.portal', compact('productsCount', 'pendingRmas', 'activeAsns', 'corteCuenta', 'recentOrders'));
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
     * MEJORADO: Generación automática de ID y estado 'sent'.
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
                // GENERACIÓN AUTOMÁTICA DE ID ÚNICO
                $asnNumber = 'ASN-' . strtoupper(Str::random(8));

                $asn = ASN::create([
                    'client_id' => $clientId,
                    'asn_number' => $asnNumber,
                    'reference_number' => $request->reference_number, // Requiere ALTER TABLE
                    'expected_arrival_date' => $request->expected_arrival_date,
                    'total_packages' => $request->total_packages, // Requiere ALTER TABLE
                    'notes' => $request->notes,
                    'status' => 'sent', // Visible inmediatamente para el almacén
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
     * Facturación y Reporte de Pagos.
     */
    public function billing()
    {
        $clientId = auth()->user()->client_id;
        $invoices = Invoice::where('client_id', $clientId)->latest()->get();
        $paymentMethodsRaw = PaymentMethod::where('is_active', true)->get();
        
        $paymentMethods = [];
        foreach($paymentMethodsRaw as $method) {
            $paymentMethods[$method->id] = ['name' => $method->name, 'details' => $method->details];
        }
        
        return view('client.billing_index', compact('invoices', 'paymentMethods'));
    }

    public function storePayment(Request $request)
    {
        $clientId = auth()->user()->client_id;
        $request->validate(['payment_method' => 'required', 'amount' => 'required', 'proof_file' => 'required|file']);
        
        $path = $request->file('proof_file')->store('payments', 'public');
        $methodName = PaymentMethod::find($request->payment_method)?->name ?? 'Desconocido';

        DB::table('payments')->insert([
            'client_id' => $clientId,
            'amount' => $request->amount,
            'payment_method' => $methodName, 
            'payment_date' => $request->payment_date ?? now(),
            'reference' => $request->reference,
            'proof_path' => $path,
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('client.billing.index')->with('success', 'Pago reportado.');
    }

    /**
     * Descargar Pre-factura (Servicios acumulados).
     */
    public function downloadPreInvoice()
    {
        $clientId = auth()->user()->client_id;
        $client = Client::with(['serviceCharges' => fn($q) => $q->where('is_invoiced', false)])->findOrFail($clientId);
        $data = [
            'client' => $client,
            'items' => $client->serviceCharges,
            'total' => $client->serviceCharges->sum('amount'),
            'title' => 'REPORTE DE SERVICIOS ACUMULADOS',
            'is_draft' => true,
            'date' => now()->format('d/m/Y')
        ];
        return view('admin.billing.pdf_template', $data);
    }

    public function api() { return view('client.api'); }
    public function support() { return view('client.support'); }
}