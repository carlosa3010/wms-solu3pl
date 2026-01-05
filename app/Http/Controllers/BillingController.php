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

    public function index()
    {
        $openPreInvoices = PreInvoice::where('status', 'open')->with('client')->get();
        $pendingPayments = Payment::where('status', 'pending')->with('client')->take(10)->get();

        return view('admin.billing.index', compact('openPreInvoices', 'pendingPayments'));
    }

    public function rates()
    {
        // Esta lógica ahora se maneja principalmente en ServicePlanController
        return redirect()->route('admin.billing.rates');
    }

    public function paymentsIndex()
    {
        $payments = Payment::with('client')->latest()->paginate(20);
        // Cambio de 'name' a 'company_name' en el ordenamiento
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        return view('admin.billing.payments_index', compact('payments', 'clients'));
    }

    public function approvePayment($id)
    {
        $payment = Payment::findOrFail($id);
        
        if ($payment->status !== 'pending') {
            return back()->with('error', 'El pago ya fue procesado.');
        }

        DB::transaction(function () use ($payment) {
            $payment->update(['status' => 'approved', 'approved_at' => now()]);

            $notes = json_decode($payment->notes, true);
            $type = $notes['type'] ?? 'wallet';

            if ($type === 'wallet') {
                $this->billingService->addFunds(
                    $payment->client_id, 
                    $payment->amount, 
                    "Recarga por Transferencia/Depósito #{$payment->reference}",
                    'payment',
                    $payment->id
                );
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
        // Corrección: Usar company_name para el nombre del archivo
        return $pdf->download("Prefactura_{$preInvoice->client->company_name}.pdf");
    }
}