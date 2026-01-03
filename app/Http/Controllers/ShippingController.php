<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\StockMovement;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ShippingController extends Controller
{
    /**
     * Muestra la cola de despacho (Pedidos que ya pasaron por Picking/Packing).
     */
    public function index(Request $request)
    {
        // Buscamos órdenes que estén listas para salir (en estado 'allocated', 'picking' o 'packing')
        // Nota: En un flujo ideal sería solo 'packing', pero permitimos 'allocated' para mayor flexibilidad
        $query = Order::with(['client', 'branch'])
            ->whereIn('status', ['allocated', 'picking', 'packing']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('customer_id_number', 'like', "%{$search}%");
            });
        }

        $pendingShipments = $query->orderBy('updated_at', 'asc')->paginate(15);

        return view('admin.operations.shipping.index', compact('pendingShipments'));
    }

    /**
     * Formulario para completar los datos de envío (Bultos, Peso, Guía).
     */
    public function process($id)
    {
        $order = Order::with(['client', 'items.product'])->findOrFail($id);
        return view('admin.operations.shipping.process', compact('order'));
    }

    /**
     * Finaliza el despacho: Descuenta stock oficialmente y registra en Kardex.
     */
    public function ship(Request $request, $id)
    {
        $order = Order::with('items.allocations')->findOrFail($id);

        $request->validate([
            'carrier_name' => 'required|string',
            'tracking_number' => 'required|string|unique:orders,external_ref', // O usar un campo específico
            'total_packages' => 'required|integer|min:1',
            'total_weight_kg' => 'required|numeric|min:0.1',
        ]);

        try {
            DB::transaction(function () use ($request, $order) {
                // 1. Actualizar datos de despacho en la orden
                $order->update([
                    'status' => 'shipped',
                    'shipping_method' => $request->carrier_name,
                    'external_ref' => $request->tracking_number, // Guardamos el tracking aquí o en un campo nuevo
                    'notes' => $order->notes . "\n[DESPACHO] Bultos: {$request->total_packages}, Peso: {$request->total_weight_kg}kg."
                ]);

                // 2. Registrar movimientos de salida en el KARDEX
                foreach ($order->items as $item) {
                    // Cada línea del pedido genera un movimiento de salida
                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'from_location_id' => $item->allocations->first()->location_id ?? null, // Simplificado a la primera loc
                        'to_location_id' => null, // Salida del sistema
                        'quantity' => $item->requested_quantity,
                        'reason' => 'Salida por Despacho Pedido #' . $order->order_number,
                        'reference_number' => $order->order_number,
                        'user_id' => Auth::id(),
                        'created_at' => now()
                    ]);
                }
            });

            return redirect()->route('admin.shipping.index')->with('success', "Pedido {$order->order_number} despachado correctamente.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Fallo al procesar salida: ' . $e->getMessage()]);
        }
    }

    /**
     * Genera la Guía de Despacho (Documento Legal).
     */
    public function printManifest($id)
    {
        $order = Order::with(['client', 'items.product', 'branch'])->findOrFail($id);
        return view('admin.operations.shipping.manifest', compact('order'));
    }
}