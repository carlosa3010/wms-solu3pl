<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ServicePlan;
use App\Models\ClientBillingAgreement;
use App\Models\Client;
use App\Models\BinType;
use App\Models\Payment;
use App\Models\Wallet;
use App\Models\PreInvoice;
use App\Services\BillingService;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class BillingController extends Controller
{
    protected $billingService;

    public function __construct(BillingService $billingService)
    {
        $this->billingService = $billingService;
    }

    /**
     * Vista Principal: Resumen Financiero
     */
    public function index()
    {
        // Resumen de pre-facturas abiertas
        $openPreInvoices = PreInvoice::where('status', 'open')->with('client')->get();
        
        // Últimos pagos recibidos (Pendientes de aprobación)
        $pendingPayments = Payment::where('status', 'pending')->with('client')->take(10)->get();

        return view('admin.billing.index', compact('openPreInvoices', 'pendingPayments'));
    }

    // =========================================================
    // GESTIÓN DE PLANES Y TARIFAS
    // =========================================================

    public function rates()
    {
        $plans = ServicePlan::with('binPrices.binType')->get();
        $binTypes = BinType::all();
        $clients = Client::where('is_active', true)->get();
        $agreements = ClientBillingAgreement::with(['client', 'servicePlan'])->get();

        return view('admin.billing.rates', compact('plans', 'binTypes', 'clients', 'agreements'));
    }

    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'reception_cost_per_box' => 'required|numeric|min:0',
            'picking_cost_per_order' => 'required|numeric|min:0',
            'additional_item_cost' => 'required|numeric|min:0',
            'premium_packing_cost' => 'required|numeric|min:0',
            'return_cost' => 'required|numeric|min:0',
            'storage_billing_type' => 'required|in:m3,bins',
            'm3_price_monthly' => 'nullable|required_if:storage_billing_type,m3|numeric|min:0',
            'bin_prices' => 'nullable|required_if:storage_billing_type,bins|array'
        ]);

        DB::transaction(function () use ($request, $data) {
            $plan = ServicePlan::create($data);

            if ($request->storage_billing_type === 'bins' && $request->bin_prices) {
                foreach ($request->bin_prices as $binTypeId => $price) {
                    $plan->binPrices()->create([
                        'bin_type_id' => $binTypeId,
                        'price_per_day' => $price
                    ]);
                }
            }
        });

        return back()->with('success', 'Plan de tarifas creado exitosamente.');
    }

    public function assignAgreement(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_plan_id' => 'required|exists:service_plans,id',
            'agreed_m3_volume' => 'nullable|numeric|min:0',
            'has_premium_packing' => 'nullable' // checkbox returns 'on' or null
        ]);

        ClientBillingAgreement::updateOrCreate(
            ['client_id' => $request->client_id],
            [
                'service_plan_id' => $request->service_plan_id,
                'agreed_m3_volume' => $request->agreed_m3_volume ?? 0,
                'has_premium_packing' => $request->has('has_premium_packing'),
                'start_date' => now(),
                'status' => 'active'
            ]
        );

        return back()->with('success', 'Acuerdo asignado al cliente correctamente.');
    }

    // =========================================================
    // GESTIÓN DE PAGOS Y BILLETERA
    // =========================================================

    public function paymentsIndex()
    {
        $payments = Payment::with('client')->latest()->paginate(20);
        $clients = Client::where('is_active', true)->orderBy('name')->get();
        return view('admin.billing.payments_index', compact('payments', 'clients'));
    }

    /**
     * Aprobar un pago (Factura o Recarga de Billetera)
     */
    public function approvePayment($id)
    {
        $payment = Payment::findOrFail($id);
        
        if ($payment->status !== 'pending') {
            return back()->with('error', 'El pago ya fue procesado.');
        }

        DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'approved', 'approved_at' => now()]);

            // Leer metadatos del pago para saber qué hacer
            $notes = json_decode($payment->notes, true);
            $type = $notes['type'] ?? 'wallet'; // Default a wallet si no especifica

            if ($type === 'wallet') {
                // Recarga de Billetera
                $this->billingService->addFunds(
                    $payment->client_id, 
                    $payment->amount, 
                    "Recarga por Transferencia/Depósito #{$payment->reference}",
                    'payment',
                    $payment->id
                );
            } elseif ($type === 'invoice') {
                // Pago de Factura
                // Aquí iría la lógica para marcar la Invoice como pagada
                // $invoice = Invoice::find($notes['invoice_id']); ...
            }
        });

        return back()->with('success', 'Pago aprobado y aplicado.');
    }

    public function rejectPayment($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->update(['status' => 'rejected']);
        return back()->with('success', 'Pago rechazado.');
    }

    /**
     * Crear pago manual o recarga manual desde Admin
     */
    public function storeManualPayment(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:wallet_recharge,invoice_payment',
            'reference' => 'required|string'
        ]);

        if ($request->type === 'wallet_recharge') {
            $this->billingService->addFunds(
                $request->client_id,
                $request->amount,
                "Recarga Manual Admin: {$request->reference}",
                'manual_admin'
            );
            return back()->with('success', 'Billetera recargada exitosamente.');
        }

        return back()->with('info', 'Pago de factura manual registrado.');
    }

    // =========================================================
    // GENERACIÓN DE FACTURAS (CIERRE)
    // =========================================================

    public function runDailyBilling()
    {
        try {
            $this->billingService->calculateDailyCosts(now());
            return back()->with('success', 'Cálculo de costos diarios ejecutado.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al ejecutar: ' . $e->getMessage());
        }
    }

    public function downloadPreInvoice($clientId)
    {
        $preInvoice = PreInvoice::where('client_id', $clientId)
            ->where('status', 'open')
            ->with(['details', 'client'])
            ->firstOrFail();

        $data = [
            'client' => $preInvoice->client,
            'items' => $preInvoice->details,
            'total' => $preInvoice->total_amount,
            'title' => 'PRE-FACTURA EN CURSO',
            'date' => now()->format('d/m/Y')
        ];

        $pdf = Pdf::loadView('admin.billing.pdf_template', $data);
        return $pdf->download("Prefactura_{$preInvoice->client->name}.pdf");
    }
}