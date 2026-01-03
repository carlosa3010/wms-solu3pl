<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Client;
use App\Models\BillingProfile;
use App\Models\Invoice;
use App\Models\ServiceCharge;
use App\Models\ClientBillingAgreement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    /**
     * Muestra el panel principal de finanzas con KPIs y listado de facturas.
     * Ruta: admin.billing.index
     */
    public function index()
    {
        $invoices = Invoice::with('client')->orderBy('created_at', 'desc')->paginate(15);
        
        // Estadísticas financieras globales
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
     * Ruta: admin.billing.rates
     */
    public function rates()
    {
        $profiles = BillingProfile::all();
        // Cargamos clientes con su acuerdo y el perfil asociado para ver quién tiene qué tarifa
        $clients = Client::with('billingAgreement.profile')->get();

        return view('admin.billing.rates', compact('profiles', 'clients'));
    }

    /**
     * Crea un nuevo Perfil Tarifario (Plan de cobro).
     */
    public function storeProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'currency' => 'required|string|max:3',
            'storage_fee_per_bin_daily' => 'required|numeric|min:0',
            'picking_fee_base' => 'required|numeric|min:0',
            'inbound_fee_per_unit' => 'required|numeric|min:0',
        ]);

        BillingProfile::create($validated);

        return back()->with('success', 'Perfil tarifario creado correctamente.');
    }

    /**
     * Vincula un cliente con un plan tarifario específico.
     * Ruta: admin.billing.assign_agreement
     */
    public function assignAgreement(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'billing_profile_id' => 'required|exists:billing_profiles,id',
            'start_date' => 'required|date'
        ]);

        ClientBillingAgreement::updateOrCreate(
            ['client_id' => $request->client_id],
            [
                'billing_profile_id' => $request->billing_profile_id,
                'start_date' => $request->start_date
            ]
        );

        return back()->with('success', 'El cliente ahora está vinculado al plan tarifario.');
    }

    /**
     * Genera la vista/PDF de la Pre-Factura basada en cargos acumulados no facturados.
     * Ruta: admin.billing.pre_invoice
     */
    public function downloadPreInvoice($clientId)
    {
        // Obtenemos al cliente con sus cargos pendientes de facturar
        $client = Client::with(['serviceCharges' => function($q) {
            $q->where('is_invoiced', false)->orderBy('charge_date', 'asc');
        }])->findOrFail($clientId);

        $data = [
            'client'   => $client,
            'items'    => $client->serviceCharges,
            'total'    => $client->accumulated_charges, // Atributo dinámico del modelo Client
            'title'    => 'REPORTE DE SERVICIOS ACUMULADOS',
            'is_draft' => true,
            'date'     => now()->format('d/m/Y')
        ];

        // Retorna la vista optimizada para impresión (o PDF si la librería está instalada)
        return view('admin.billing.pdf_template', $data);
    }

    /**
     * Muestra/Descarga una factura oficial ya emitida.
     * Ruta: admin.billing.invoice.download
     */
    public function downloadInvoice($invoiceId)
    {
        $invoice = Invoice::with(['client'])->findOrFail($invoiceId);
        
        $data = [
            'client'   => $invoice->client,
            'invoice'  => $invoice,
            'title'    => 'FACTURA COMERCIAL: ' . $invoice->invoice_number,
            'is_draft' => false,
            'date'     => $invoice->created_at->format('d/m/Y')
        ];

        return view('admin.billing.pdf_template', $data);
    }

    /**
     * Lógica programada (Simulación): Calcula el almacenamiento diario.
     * Este método debería ejecutarse automáticamente cada noche vía Cron.
     */
    public function runDailyBilling()
    {
        // 1. Buscar todos los clientes con acuerdos activos
        $agreements = ClientBillingAgreement::with(['client', 'profile'])->get();

        DB::transaction(function () use ($agreements) {
            foreach ($agreements as $agreement) {
                // 2. Contar bines ocupados por este cliente hoy
                // (Se asume una relación o consulta que cuente bines con stock del cliente)
                $occupiedBinsCount = DB::table('inventory')
                    ->join('products', 'inventory.product_id', '=', 'products.id')
                    ->where('products.client_id', $agreement->client_id)
                    ->distinct('location_id')
                    ->count();

                if ($occupiedBinsCount > 0) {
                    $amount = $occupiedBinsCount * $agreement->profile->storage_fee_per_bin_daily;

                    // 3. Registrar el cargo de servicio
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

        return back()->with('success', 'El proceso de cargos diarios de almacenamiento se ha completado.');
    }
}