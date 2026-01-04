<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\BillingProfile;
use App\Models\Invoice;
use App\Models\ServiceCharge;
use App\Models\ClientBillingAgreement;
use App\Models\Payment; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceGenerated;
use Illuminate\Support\Facades\Log;

class BillingController extends Controller
{
    /**
     * Muestra el panel principal de finanzas con KPIs y listado de facturas.
     */
    public function index()
    {
        $invoices = Invoice::with('client')->orderBy('created_at', 'desc')->paginate(15);
        
        $stats = [
            'total_pending' => Invoice::where('status', 'unpaid')->sum('total_amount'),
            'collected_month' => Invoice::where('status', 'paid')
                                        ->whereMonth('created_at', now()->month)
                                        ->sum('total_amount'),
            'pending_charges' => ServiceCharge::where('is_invoiced', false)->count()
        ];

        return view('admin.billing.index', compact('invoices', 'stats'));
    }

    /**
     * Gestión de Tarifas: Visualiza perfiles existentes y acuerdos con clientes.
     */
    public function rates()
    {
        $profiles = BillingProfile::all();
        // Cargamos clientes con su acuerdo y el perfil asociado
        $clients = Client::with('billingAgreement.profile')->get();
        
        // Cargamos los acuerdos explícitamente para asegurar su visualización en la tabla de la vista
        $agreements = ClientBillingAgreement::with(['client', 'profile'])->get();

        return view('admin.billing.rates', compact('profiles', 'clients', 'agreements'));
    }

    /**
     * Crea un nuevo Perfil Tarifario con servicios extras incluidos.
     */
    public function storeProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100|unique:billing_profiles,name',
            'currency' => 'required|string|max:3',
            'storage_fee_per_bin_daily' => 'required|numeric|min:0',
            'picking_fee_base' => 'required|numeric|min:0',
            'inbound_fee_per_unit' => 'required|numeric|min:0',
            'premium_packing_fee' => 'required|numeric|min:0', // Nuevo: Empaque Premium
            'rma_handling_fee' => 'required|numeric|min:0',    // Nuevo: Manejo de RMA
        ]);

        BillingProfile::create($request->all());

        return back()->with('success', 'Perfil tarifario creado correctamente.');
    }

    /**
     * Vincula un cliente con un plan tarifario específico.
     * CORRECCIÓN: Manejo de errores y persistencia robusta.
     */
    public function assignAgreement(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'billing_profile_id' => 'required|exists:billing_profiles,id',
            'start_date' => 'required|date'
        ]);

        try {
            // Actualizamos o creamos el acuerdo para el cliente
            ClientBillingAgreement::updateOrCreate(
                ['client_id' => $request->client_id],
                [
                    'billing_profile_id' => $request->billing_profile_id,
                    'start_date' => $request->start_date,
                    'is_active' => true // Aseguramos que el acuerdo esté activo al asignarse
                ]
            );

            return back()->with('success', 'El perfil tarifario ha sido asignado al cliente exitosamente.');
        } catch (\Exception $e) {
            Log::error("Error al asignar perfil tarifario: " . $e->getMessage());
            return back()->with('error', 'No se pudo completar la asignación: ' . $e->getMessage());
        }
    }

    /**
     * Genera la vista/PDF de la Pre-Factura.
     */
    public function downloadPreInvoice($clientId)
    {
        $client = Client::with(['serviceCharges' => function($q) {
            $q->where('is_invoiced', false)->orderBy('charge_date', 'asc');
        }])->findOrFail($clientId);

        $data = [
            'client'    => $client,
            'items'     => $client->serviceCharges,
            'total'     => $client->accumulated_charges,
            'title'     => 'REPORTE DE SERVICIOS ACUMULADOS',
            'is_draft'  => true,
            'date'      => now()->format('d/m/Y')
        ];

        return view('admin.billing.pdf_template', $data);
    }

    /**
     * Descarga factura oficial.
     */
    public function downloadInvoice($invoiceId)
    {
        $invoice = Invoice::with(['client'])->findOrFail($invoiceId);
        
        $data = [
            'client'    => $invoice->client,
            'invoice'   => $invoice,
            'title'     => 'FACTURA COMERCIAL: ' . $invoice->invoice_number,
            'is_draft'  => false,
            'date'      => $invoice->created_at->format('d/m/Y')
        ];

        return view('admin.billing.pdf_template', $data);
    }

    /**
     * Lógica de cobro diario automatizado.
     */
    public function runDailyBilling()
    {
        $agreements = ClientBillingAgreement::with(['client', 'profile'])->where('is_active', true)->get();

        DB::transaction(function () use ($agreements) {
            foreach ($agreements as $agreement) {
                // Contar bines ocupados por este cliente hoy
                $occupiedBinsCount = DB::table('inventory')
                    ->join('products', 'inventory.product_id', '=', 'products.id')
                    ->where('products.client_id', $agreement->client_id)
                    ->distinct('location_id')
                    ->count();

                if ($occupiedBinsCount > 0) {
                    $amount = $occupiedBinsCount * $agreement->profile->storage_fee_per_bin_daily;

                    ServiceCharge::create([
                        'client_id'    => $agreement->client_id,
                        'type'         => 'storage',
                        'description'  => "Almacenamiento diario: {$occupiedBinsCount} bines ocupados.",
                        'amount'       => $amount,
                        'charge_date'  => now(),
                        'is_invoiced'  => false,
                        'created_at'   => now()
                    ]);
                }
            }
        });

        // Lógica de generación masiva de facturas
        $clientsToBill = Client::whereHas('serviceCharges', function($q) {
            $q->where('is_invoiced', false);
        })->get();

        foreach ($clientsToBill as $client) {
            $pendingCharges = $client->serviceCharges()->where('is_invoiced', false)->get();
            $total = $pendingCharges->sum('amount');

            if ($total > 0) {
                try {
                    DB::transaction(function () use ($client, $total, $pendingCharges) {
                        $invoice = Invoice::create([
                            'client_id' => $client->id,
                            'invoice_number' => 'INV-' . strtoupper(uniqid()),
                            'total_amount' => $total,
                            'status' => 'unpaid',
                            'due_date' => now()->addDays(15),
                        ]);

                        foreach ($pendingCharges as $charge) {
                            $charge->update(['is_invoiced' => true, 'invoice_id' => $invoice->id]);
                        }

                        if ($client->email) {
                            Mail::to($client->email)->send(new InvoiceGenerated($invoice));
                        }
                    });
                } catch (\Exception $e) {
                    Log::error("Error al generar factura para cliente {$client->id}: " . $e->getMessage());
                }
            }
        }

        return back()->with('success', 'Cargos diarios calculados y facturas generadas correctamente.');
    }

    /**
     * Listado de pagos reportados.
     */
    public function paymentsIndex(Request $request)
    {
        $query = Payment::with('client')->latest();

        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $payments = $query->get();

        return view('admin.billing.payments_index', compact('payments'));
    }

    public function approvePayment($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->status = 'approved';
        $payment->approved_at = now();
        $payment->save();

        return redirect()->back()->with('success', 'Pago acreditado correctamente.');
    }

    public function rejectPayment($id)
    {
        $payment = Payment::findOrFail($id);
        $payment->status = 'rejected';
        $payment->save();

        return redirect()->back()->with('success', 'Pago rechazado.');
    }
}