<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use App\Models\Location;
use App\Models\PackageType;

class WarehouseApiController extends Controller
{
    /**
     * Dashboard Data: Resumen para la pantalla de inicio de la App
     */
    public function dashboard()
    {
        // KPIs rápidos para el operador
        $ordersPending = Order::where('status', 'picking')->count();
        $ordersPacking = Order::where('status', 'packing')->count();

        return response()->json([
            'tasks' => [
                'picking' => $ordersPending,
                'packing' => $ordersPacking
            ]
        ]);
    }

    /**
     * Endpoint Genérico: Escanear cualquier código y determinar qué es
     * Ideal para la función "Scan & Go"
     */
    public function globalScan(Request $request)
    {
        $code = $request->code;

        // 1. ¿Es una Ubicación?
        $location = Location::where('code', $code)->first();
        if ($location) {
            return response()->json(['type' => 'location', 'data' => $location]);
        }

        // 2. ¿Es un Producto?
        $product = Product::where('sku', $code)->first();
        if ($product) {
            // Cargar inventario asociado
            $stock = $product->inventory()->sum('quantity');
            return response()->json([
                'type' => 'product', 
                'data' => $product, 
                'total_stock' => $stock
            ]);
        }

        // 3. ¿Es una Orden?
        $order = Order::where('order_number', $code)->first();
        if ($order) {
            return response()->json(['type' => 'order', 'data' => $order]);
        }

        return response()->json(['type' => 'unknown', 'message' => 'Código no encontrado'], 404);
    }

    // --- MÓDULO PACKING (API) ---

    public function getOrderForPacking($orderNumber)
    {
        $order = Order::with(['items.product', 'client'])
                      ->where('order_number', $orderNumber)
                      ->first();

        if (!$order) {
            return response()->json(['message' => 'Orden no encontrada'], 404);
        }

        // Sugerir cajas (misma lógica que en web)
        $boxes = PackageType::where('is_active', true)
                    ->where(function($q) use ($order) {
                        $q->whereNull('client_id')
                          ->orWhere('client_id', $order->client_id);
                    })->get();

        return response()->json([
            'order' => $order,
            'suggested_boxes' => $boxes
        ]);
    }
}