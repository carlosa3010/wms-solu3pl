<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ServiceCharge;
use App\Models\ClientBillingAgreement;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ShippingController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['client', 'branch'])
            ->whereIn('status', ['allocated', 'picking', 'packing']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
            });
        }

        $pendingShipments = $query->orderBy('updated_at', 'asc')->paginate(15);
        return view('admin.operations.shipping.index', compact('pendingShipments'));
    }

    public function process($id)
    {
        $order = Order::with(['client', 'items.product'])->findOrFail($id);
        return view('admin.operations.shipping.process', compact('order'));
    }

    /**
     * Finaliza despacho y aplica cobros automáticos por Picking y Empaque Premium.
     */
    public function ship(Request $request, $id)
    {
        $order = Order::with(['items.allocations', 'client.billingAgreement.billingProfile'])->findOrFail($id);

        $request->validate([
            'carrier_name' => 'required|string',
            'tracking_number' => 'required|string',
            'total_packages' => 'required|integer|min:1',
            'total_weight_kg' => 'required|numeric|min:0.1',
        ]);

        try {
            DB::transaction(function () use ($request, $order) {
                // 1. Marcar como enviado
                $order->update([
                    'status' => 'shipped',
                    'shipping_method' => $request->carrier_name,
                    'external_ref' => $request->tracking_number,
                    'shipped_at' => now(),
                    'notes' => $order->notes . "\n[DESPACHO] " . now()
                ]);

                // 2. LÓGICA DE COBRO AUTOMÁTICO
                $agreement = $order->client->billingAgreement;
                if ($agreement && $agreement->billingProfile) {
                    $profile = $agreement->billingProfile;

                    // Cargo por Picking Base
                    ServiceCharge::create([
                        'client_id' => $order->client_id,
                        'type' => 'picking',
                        'description' => "Servicio Picking y Despacho: Orden #{$order->order_number}",
                        'amount' => $profile->picking_fee_base,
                        'charge_date' => now(),
                        'is_invoiced' => false,
                        'reference_id' => $order->id
                    ]);

                    // Cargo por Empaque Premium (Si está marcado en la orden)
                    if ($order->is_premium_packing) {
                        ServiceCharge::create([
                            'client_id' => $order->client_id,
                            'type' => 'extra_service',
                            'description' => "Servicio Empaque Premium: Orden #{$order->order_number}",
                            'amount' => $profile->premium_packing_fee,
                            'charge_date' => now(),
                            'is_invoiced' => false,
                            'reference_id' => $order->id
                        ]);
                    }
                }

                // 3. Registrar Kardex
                foreach ($order->items as $item) {
                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'from_location_id' => $item->allocations->first()->location_id ?? null,
                        'quantity' => $item->quantity,
                        'reason' => 'Despacho Orden #' . $order->order_number,
                        'reference_number' => $order->order_number,
                        'user_id' => Auth::id()
                    ]);
                }
            });

            return redirect()->route('admin.shipping.index')->with('success', "Pedido {$order->order_number} despachado y cargos aplicados.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }
}