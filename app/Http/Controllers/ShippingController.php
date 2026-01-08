<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ServiceCharge;
use App\Models\ClientBillingAgreement;
use App\Models\StockMovement;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ShippingController extends Controller
{
    protected $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    public function index(Request $request)
    {
        // CORRECCIÓN: Solo mostrar órdenes que están en proceso de empaque/listas
        // Se eliminaron 'allocated' y 'picking' para respetar el flujo secuencial.
        $query = Order::with(['client', 'branch'])
            ->whereIn('status', ['packing']); 

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

    public function ship(Request $request, $id)
    {
        $order = Order::with(['items.allocations', 'client.billingAgreement.billingProfile', 'client.servicePlan'])->findOrFail($id);

        $request->validate([
            'carrier_name' => 'required|string',
            'tracking_number' => 'required|string',
            'total_packages' => 'required|integer|min:1',
            'total_weight_kg' => 'required|numeric|min:0.1',
            'shipping_cost' => 'nullable|numeric|min:0',
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
                
                // --- INTEGRACIÓN BILLETERA (COBRO DE ENVÍO) ---
                if ($request->filled('shipping_cost') && $request->shipping_cost > 0) {
                    try {
                        $this->billingService->chargeWalletForShipping(
                            $order->client_id, 
                            $request->shipping_cost, 
                            $order->id
                        );
                    } catch (\Exception $e) {
                        throw new \Exception("Error al cobrar envío de la billetera: " . $e->getMessage());
                    }
                }
                
                // --- CARGOS POR SERVICIOS OPERATIVOS ---
                $plan = $order->client->billingAgreement->servicePlan ?? null;
                
                if ($plan) {
                     // Costo Picking Base
                    if ($plan->picking_cost_per_order > 0) {
                        ServiceCharge::create([
                            'client_id' => $order->client_id,
                            'type' => 'picking',
                            'description' => "Picking Orden #{$order->order_number}",
                            'amount' => $plan->picking_cost_per_order,
                            'charge_date' => now(),
                            'is_invoiced' => false,
                            'reference_id' => $order->id
                        ]);
                    }

                    // Costo Items Adicionales
                    $itemCount = $order->items()->count();
                    $extraItems = max(0, $itemCount - 1);
                    if ($extraItems > 0 && $plan->additional_item_cost > 0) {
                        ServiceCharge::create([
                            'client_id' => $order->client_id,
                            'type' => 'picking_extra',
                            'description' => "Items Adicionales ({$extraItems}) Orden #{$order->order_number}",
                            'amount' => $extraItems * $plan->additional_item_cost,
                            'charge_date' => now(),
                            'is_invoiced' => false,
                            'reference_id' => $order->id
                        ]);
                    }
                    
                    // Costo Empaque Premium
                    if ($order->client->billingAgreement->has_premium_packing && $plan->premium_packing_cost > 0) {
                         ServiceCharge::create([
                            'client_id' => $order->client_id,
                            'type' => 'packing_premium',
                            'description' => "Empaque Premium Orden #{$order->order_number}",
                            'amount' => $plan->premium_packing_cost,
                            'charge_date' => now(),
                            'is_invoiced' => false,
                            'reference_id' => $order->id
                        ]);
                    }
                } else {
                    // LÓGICA LEGACY
                    $agreement = $order->client->billingAgreement;
                    if ($agreement && $agreement->billingProfile) {
                        $profile = $agreement->billingProfile;

                        ServiceCharge::create([
                            'client_id' => $order->client_id,
                            'type' => 'picking',
                            'description' => "Servicio Picking y Despacho: Orden #{$order->order_number}",
                            'amount' => $profile->picking_fee_base,
                            'charge_date' => now(),
                            'is_invoiced' => false,
                            'reference_id' => $order->id
                        ]);

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

            return redirect()->route('admin.shipping.index')->with('success', "Pedido {$order->order_number} despachado. Costo de envío descontado de billetera (si aplica) y cargos operativos registrados.");

        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error: ' . $e->getMessage()]);
        }
    }
}