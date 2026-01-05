<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\Payment;
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
     * Dashboard Financiero Principal.
     */
    public function index()
    {
        $openPreInvoices = PreInvoice::where('status', 'open')->with('client')->get();
        $pendingPayments = Payment::where('status', 'pending')->with('client')->take(10)->get();

        return view('admin.billing.index', compact('openPreInvoices', 'pendingPayments'));
    }

    /**
     * Listado de Pagos Recibidos.
     */
    public function paymentsIndex()
    {
        $payments = Payment::with('client')->latest()->paginate(20);
        
        // Uso consistente de company_name para el ordenamiento
        $clients = Client::where('is_active', true)
            ->orderBy('company_name')
            ->get();
        
        return view('admin.billing.payments_index', compact('payments', 'clients'));
    }

    /**
     * Aprobación de pagos y recarga de billetera.
     */
    public function approvePayment($id)
    {
        $payment = Payment::findOrFail($id);
        
        if ($payment->status !== 'pending') {
            return back()->with('error', 'Este pago ya fue procesado anteriormente.');
        }

        DB::transaction(function () use ($payment) {
            $payment->update([
                'status' => 'approved',
                'approved_at' => now()
            ]);

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

        return back()->with('success', 'Pago aprobado y saldo aplicado exitosamente.');
    }

    /**
     * Rechazo de pagos.
     */
    public function rejectPayment($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->update(['status' => 'rejected']);
        return back()->with('success', 'El pago ha sido marcado como rechazado.');
    }

    /**
     * Registro de recargas manuales desde el panel administrativo.
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
            return back()->with('success', 'Billetera del cliente recargada correctamente.');
        }

        return back()->with('info', 'Funcionalidad de pago directo a factura en desarrollo.');
    }

    /**
     * Ejecuta manualmente el cálculo de costos operativos del día.
     */
    public function runDailyBilling()
    {
        try {
            $this->billingService->calculateDailyCosts(now());
            return back()->with('success', 'El proceso de facturación diaria se ejecutó correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Ocurrió un error al procesar la facturación: ' . $e->getMessage());
        }
    }

    /**
     * Genera y descarga el PDF de pre-factura usando el campo company_name.
     */
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
            'title' => 'DETALLE DE CONSUMO MENSUAL (PRE-FACTURA)',
            'date' => now()->format('d/m/Y')
        ];

        $pdf = Pdf::loadView('admin.billing.pdf_template', $data);
        
        // Uso de company_name para el nombre del archivo descargado
        $fileName = "Prefactura_" . str_replace(' ', '_', $preInvoice->client->company_name) . ".pdf";
        
        return $pdf->download($fileName);
    }
}