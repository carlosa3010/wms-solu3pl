<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Client;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\ASN; // Necesario para Recepciones
use App\Models\RMA; // Necesario para Devoluciones
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * MÓDULO ADMIN: Centro de Comando Logístico.
     */
    public function adminDashboard()
    {
        $today = Carbon::today();

        // --- 1. FLUJO DE PEDIDOS (El Pulso Diario) ---
        $ordersToday = Order::whereDate('created_at', $today)->count();
        $shippedToday = Order::whereDate('shipped_at', $today)->where('status', 'shipped')->count();
        
        // Estado del Pipeline (Total Histórico vs Pendiente Actual)
        $pendingOrders = Order::whereIn('status', ['pending', 'allocated'])->count(); // Por procesar
        $processingOrders = Order::whereIn('status', ['picking', 'packing'])->count(); // En proceso
        
        // --- 2. GESTIÓN DE INVENTARIO E INFRAESTRUCTURA ---
        $totalBins = Location::count();
        // Optimización: Contar locations ocupadas sin cargar todos los modelos
        $occupiedBins = Location::whereHas('stock', function($query) {
            $query->where('quantity', '>', 0);
        })->count();

        $occupancyRate = $totalBins > 0 ? round(($occupiedBins / $totalBins) * 100, 1) : 0;

        // Alerta de Stock Bajo (Productos con stock <= nivel mínimo)
        // Nota: Esto asume que tienes 'min_stock_level' en la tabla products
        $lowStockCount = Product::whereHas('inventory', function($q) {
             $q->select(DB::raw('SUM(quantity) as total_qty'))
               ->groupBy('product_id')
               ->havingRaw('total_qty <= products.min_stock_level');
        })->count();

        // --- 3. ENTRADAS Y SALIDAS (Inbound/Reverse) ---
        $pendingASNs = class_exists(ASN::class) ? ASN::where('status', '!=', 'completed')->count() : 0;
        $pendingRMAs = class_exists(RMA::class) ? RMA::where('status', 'pending')->count() : 0;

        // --- 4. TOP CLIENTES (Por volumen de órdenes este mes) ---
        $topClients = Client::withCount(['orders' => function($q) {
                $q->whereMonth('created_at', Carbon::now()->month);
            }])
            ->orderBy('orders_count', 'desc')
            ->take(5)
            ->get();

        // --- 5. ÚLTIMOS MOVIMIENTOS ---
        $recentOrders = Order::with('client', 'branch')
            ->orderBy('updated_at', 'desc') // Ordenar por última actualización para ver movimiento real
            ->take(6)
            ->get();

        return view('admin.dashboard', compact(
            'ordersToday', 'shippedToday', 'pendingOrders', 'processingOrders',
            'totalBins', 'occupiedBins', 'occupancyRate', 'lowStockCount',
            'pendingASNs', 'pendingRMAs',
            'topClients', 'recentOrders'
        ));
    }

    public function warehouseStation()
    {
        $pendingQueue = Order::with(['client', 'items.product', 'branch'])
            ->whereIn('status', ['pending', 'allocated', 'picking'])
            ->orderBy('created_at', 'asc')
            ->get();

        $currentJob = $pendingQueue->first();

        return view('warehouse.station', compact('pendingQueue', 'currentJob'));
    }

    public function clientPortal()
    {
        $user = Auth::user();
        if (!$user->client_id) return redirect()->route('admin.dashboard');

        $client = Client::findOrFail($user->client_id);
        $myOrders = Order::where('client_id', $client->id)->orderBy('created_at', 'desc')->paginate(10);
        $myStockCount = Inventory::whereHas('product', function($q) use ($client) {
            $q->where('client_id', $client->id);
        })->sum('quantity');
        
        return view('client.portal', compact('client', 'myOrders', 'myStockCount'));
    }
}