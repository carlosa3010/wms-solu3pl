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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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

        // Conteos segmentados
        $productsCount = Product::where('client_id', $clientId)->count();
        $pendingRmas = RMA::where('client_id', $clientId)->where('status', 'pending')->count();
        $activeAsns = ASN::where('client_id', $clientId)->whereIn('status', ['draft', 'sent'])->count();
        
        /**
         * Corte de cuenta: Suma de cargos de servicio del mes actual.
         */
        $corteCuenta = ServiceCharge::where('client_id', $clientId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $recentOrders = Order::where('client_id', $clientId)->latest()->take(5)->get();

        return view('client.portal', compact('productsCount', 'pendingRmas', 'activeAsns', 'corteCuenta', 'recentOrders'));
    }

    /**
     * Catálogo: Ver solo los productos del cliente.
     */
    public function catalog()
    {
        $clientId = auth()->user()->client_id;
        $products = Product::where('client_id', $clientId)->latest()->get();
        return view('client.catalog', compact('products'));
    }

    /**
     * Stock Actual: Cantidad por producto, sucursal y bodega.
     */
    public function stock()
    {
        $clientId = auth()->user()->client_id;

        // Consultamos el inventario filtrando por los productos que pertenecen al cliente
        $inventory = Inventory::whereHas('product', function($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })->with(['product', 'location.warehouse.branch'])->get();

        return view('client.stock', compact('inventory'));
    }

    /**
     * ASN: Gestión de Avisos de Envío.
     */
    public function asnIndex()
    {
        $clientId = auth()->user()->client_id;
        $asns = ASN::where('client_id', $clientId)->latest()->get();
        return view('client.asn_index', compact('asns'));
    }

    public function createAsn()
    {
        $clientId = auth()->user()->client_id;
        $products = Product::where('client_id', $clientId)->get();
        return view('client.asn_create', compact('products'));
    }

    public function storeAsn(Request $request)
    {
        $clientId = auth()->user()->client_id;

        DB::transaction(function () use ($request, $clientId) {
            $asn = ASN::create([
                'asn_number' => 'ASN-' . time(),
                'client_id' => $clientId,
                'carrier_name' => $request->carrier_name,
                'tracking_number' => $request->tracking_number,
                'expected_arrival_date' => $request->expected_arrival_date,
                'status' => 'sent'
            ]);

            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    ASNItem::create([
                        'asn_id' => $asn->id,
                        'product_id' => $item['product_id'],
                        'expected_quantity' => $item['quantity']
                    ]);
                }
            }
        });

        return redirect()->route('client.asn.index')->with('success', 'ASN creado y enviado correctamente.');
    }

    /**
     * Pedidos (Orders): Creación manual desde el panel.
     */
    public function createOrder()
    {
        $clientId = auth()->user()->client_id;
        $products = Product::where('client_id', $clientId)->get();
        return view('client.order_create', compact('products'));
    }

    public function storeOrder(Request $request)
    {
        $clientId = auth()->user()->client_id;

        DB::transaction(function () use ($request, $clientId) {
            $order = Order::create([
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'client_id' => $clientId,
                'customer_name' => $request->customer_name,
                'shipping_address' => $request->shipping_address,
                'status' => 'pending'
            ]);

            if ($request->has('items')) {
                foreach ($request->items as $item) {
                    $product = Product::find($item['product_id']);
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price_at_order' => $product->price ?? 0 
                    ]);
                }
            }
        });

        return redirect()->route('client.portal')->with('success', 'Pedido registrado con éxito.');
    }

    /**
     * RMA: Devoluciones para autorizar o rechazar.
     */
    public function rmaIndex()
    {
        $clientId = auth()->user()->client_id;
        $rmas = RMA::where('client_id', $clientId)->with(['items.product', 'order'])->latest()->get();
        return view('client.rma_index', compact('rmas'));
    }

    public function updateRmaStatus(Request $request, $id)
    {
        $clientId = auth()->user()->client_id;
        $rma = RMA::where('client_id', $clientId)->findOrFail($id);

        $request->validate([
            'status' => 'required|in:authorized,rejected'
        ]);

        $rma->update(['status' => $request->status]);

        return back()->with('success', 'Estado de devolución actualizado correctamente.');
    }

    /**
     * Facturación: Lista de facturas y descarga.
     */
    public function billing()
    {
        $clientId = auth()->user()->client_id;
        $invoices = Invoice::where('client_id', $clientId)->latest()->get();
        return view('client.billing_index', compact('invoices'));
    }

    /**
     * Descarga de Prefactura (Cargos del mes actual)
     */
    public function downloadPreInvoice()
    {
        $clientId = auth()->user()->client_id;
        
        $charges = ServiceCharge::where('client_id', $clientId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->get();
        
        if ($charges->isEmpty()) {
            return back()->with('error', 'No hay cargos registrados en el ciclo actual.');
        }

        return back()->with('info', 'Generando reporte de pre-factura del mes...');
    }
}