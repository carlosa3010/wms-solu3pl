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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ClientPortalController extends Controller
{
    /**
     * Dashboard Principal: Resumen operativo y financiero segmentado.
     */
    public function dashboard()
    {
        $clientId = auth()->user()->client_id;
        if (!$clientId) {
            return redirect()->route('login')->with('error', 'Usuario no vinculado a un cliente.');
        }

        // Conteos segmentados por client_id
        $productsCount = Product::where('client_id', $clientId)->count();
        $pendingRmas = RMA::where('client_id', $clientId)->where('status', 'pending')->count();
        $activeAsns = ASN::where('client_id', $clientId)->whereIn('status', ['draft', 'sent'])->count();
        
        // Corte de cuenta: Suma de cargos de servicio del mes actual
        $corteCuenta = ServiceCharge::where('client_id', $clientId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('amount');

        $recentOrders = Order::where('client_id', $clientId)->latest()->take(5)->get();

        return view('client.portal', compact('productsCount', 'pendingRmas', 'activeAsns', 'corteCuenta', 'recentOrders'));
    }

    /**
     * Catálogo: Ver solo los productos (SKUs) del cliente.
     */
    public function catalog()
    {
        $clientId = auth()->user()->client_id;
        
        // Enviamos $skus para la tabla y $categories para el formulario de creación
        $skus = Product::where('client_id', $clientId)->with('category')->latest()->get();
        $categories = Category::orderBy('name')->get();

        return view('client.catalog', compact('skus', 'categories'));
    }

    /**
     * Guardar nuevo producto (SKU) con los mismos campos del Admin.
     */
    public function storeSku(Request $request)
    {
        $clientId = auth()->user()->client_id;

        $request->validate([
            'sku' => 'required|unique:products,sku',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'weight_kg' => 'nullable|numeric|min:0',
            'min_stock_level' => 'nullable|integer|min:0',
        ]);

        Product::create([
            'client_id' => $clientId,
            'sku' => $request->sku,
            'barcode' => $request->barcode,
            'name' => $request->name,
            'description' => $request->description,
            'category_id' => $request->category_id,
            'weight_kg' => $request->weight_kg,
            'min_stock_level' => $request->min_stock_level,
        ]);

        return redirect()->route('client.catalog')->with('success', 'Producto registrado correctamente.');
    }

    /**
     * Stock Actual: Cantidad por producto, sucursal y bodega.
     */
    public function stock()
    {
        $clientId = auth()->user()->client_id;

        // Consultamos el inventario filtrando por los productos que pertenecen al cliente
        $stocks = Inventory::whereHas('product', function($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })->with(['product', 'location.warehouse.branch'])->get();

        return view('client.stock', compact('stocks'));
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

    /**
     * RMA: Devoluciones segmentadas.
     */
    public function rmaIndex()
    {
        $clientId = auth()->user()->client_id;
        $rmas = RMA::where('client_id', $clientId)->with(['items.product', 'order'])->latest()->get();
        return view('client.rma_index', compact('rmas'));
    }

    /**
     * Facturación: Lista de facturas.
     */
    public function billing()
    {
        $clientId = auth()->user()->client_id;
        $invoices = Invoice::where('client_id', $clientId)->latest()->get();
        return view('client.billing_index', compact('invoices'));
    }
}