<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Client;
use App\Models\Location;
use App\Models\Inventory; // Importación necesaria para corregir el error
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * MÓDULO ADMIN: Resumen ejecutivo con KPIs comerciales y de almacenamiento.
     */
    public function adminDashboard()
    {
        // KPIs Comerciales
        $totalOrders = Order::count();
        $pendingOrders = Order::where('status', 'pending')->count();
        $totalProducts = Product::count();
        
        // Ingresos proyectados (Simulación basada en despachos)
        $revenue = Order::where('status', 'shipped')->count() * 5.00; 

        // KPIs de Almacenamiento (Infraestructura)
        $totalBins = Location::count();
        $occupiedBins = Location::whereHas('stock', function($query) {
            $query->where('quantity', '>', 0);
        })->count();

        $availableBins = $totalBins - $occupiedBins;
        $occupancyRate = $totalBins > 0 ? round(($occupiedBins / $totalBins) * 100, 1) : 0;

        // Pedidos Recientes para el Dashboard
        $recentOrders = Order::with('client')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'totalOrders', 'pendingOrders', 'totalProducts', 'revenue',
            'totalBins', 'occupiedBins', 'availableBins', 'occupancyRate',
            'recentOrders'
        ));
    }

    /**
     * MÓDULO WAREHOUSE: Terminal para operarios de picking y packing.
     */
    public function warehouseStation()
    {
        // Obtenemos la cola de trabajo: Pedidos listos para procesar
        $pendingQueue = Order::with(['client', 'items.product', 'branch'])
            ->whereIn('status', ['pending', 'allocated', 'picking'])
            ->orderBy('created_at', 'asc')
            ->get();

        // El trabajo actual es el primero en la fila
        $currentJob = $pendingQueue->first();

        return view('warehouse.station', compact('pendingQueue', 'currentJob'));
    }

    /**
     * MÓDULO CLIENTE: Portal de autogestión para dueños de mercancía.
     */
    public function clientPortal()
    {
        $user = Auth::user();
        
        // Si el usuario no tiene client_id vinculado (es admin o error), redirigir
        if (!$user->client_id) {
            return redirect()->route('admin.dashboard');
        }

        $client = Client::findOrFail($user->client_id);
        
        // Órdenes del cliente
        $myOrders = Order::where('client_id', $client->id)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Con la importación de App\Models\Inventory arriba, esta línea ya no fallará
        $myStockCount = Inventory::whereHas('product', function($q) use ($client) {
            $q->where('client_id', $client->id);
        })->sum('quantity');
        
        return view('client.portal', compact('client', 'myOrders', 'myStockCount'));
    }
}