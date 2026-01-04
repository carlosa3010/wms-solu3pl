<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RMA;
use App\Models\RMAItem;
use App\Models\Order;
use App\Models\Client;
use App\Models\Inventory;
use App\Models\Location;
use App\Models\ServiceCharge;
use App\Models\ClientBillingAgreement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class RMAController extends Controller
{
    /**
     * Listado maestro de devoluciones.
     */
    public function index(Request $request)
    {
        $query = RMA::with(['client', 'order']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('rma_number', 'like', "%{$search}%")
                  ->orWhere('customer_name', 'like', "%{$search}%");
        }

        $rmas = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.operations.rma.index', compact('rmas'));
    }

    /**
     * Formulario para registrar una devolución.
     */
    public function create()
    {
        $clients = Client::where('is_active', true)->get();
        $rmaNumber = 'RMA-' . date('Y') . '-' . strtoupper(Str::random(4));
        return view('admin.operations.rma.create', compact('clients', 'rmaNumber'));
    }

    /**
     * Almacena la solicitud de RMA.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'customer_name' => 'required|string',
            'reason' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $rma = RMA::create([
                    'rma_number' => $request->rma_number,
                    'client_id' => $request->client_id,
                    'order_id' => $request->order_id,
                    'customer_name' => $request->customer_name,
                    'reason' => $request->reason,
                    'status' => 'pending',
                    'internal_notes' => $request->notes
                ]);

                foreach ($request->items as $item) {
                    RMAItem::create([
                        'rma_id' => $rma->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['qty']
                    ]);
                }
            });

            return redirect()->route('admin.rma.index')->with('success', 'Solicitud de devolución registrada correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al crear RMA: ' . $e->getMessage()]);
        }
    }

    /**
     * Ver detalle del RMA antes de procesar.
     */
    public function show($id)
    {
        $rma = RMA::with(['client', 'order', 'items.product', 'images'])->findOrFail($id);
        return view('admin.operations.rma.show', compact('rma'));
    }

    /**
     * Procesa la recepción física y aplica el cobro por manejo logístico.
     */
    public function process(Request $request, $id)
    {
        $rma = RMA::with('client')->findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:received,rejected,processing',
            'admin_notes' => 'nullable|string'
        ]);

        try {
            DB::transaction(function () use ($rma, $request) {
                // 1. Actualizar estado
                $rma->update([
                    'status' => $request->status,
                    'admin_notes' => $request->admin_notes,
                    'processed_at' => now()
                ]);

                // 2. LÓGICA DE COBRO AUTOMÁTICO (Si se marca como recibido)
                if ($request->status === 'received') {
                    $agreement = ClientBillingAgreement::where('client_id', $rma->client_id)
                        ->where('is_active', true)
                        ->with('profile') // 'profile' es el nombre de la relación en el modelo Agreement
                        ->first();

                    if ($agreement && $agreement->profile) {
                        $fee = $agreement->profile->rma_handling_fee;
                        
                        if ($fee > 0) {
                            ServiceCharge::create([
                                'client_id' => $rma->client_id,
                                'type' => 'rma_handling',
                                'description' => "Manejo logístico de devolución: Folio {$rma->rma_number}",
                                'amount' => $fee,
                                'charge_date' => now(),
                                'is_invoiced' => false,
                                'reference_id' => $rma->id
                            ]);
                        }
                    }
                }
            });

            return redirect()->route('admin.rma.index')->with('success', 'Estado de la devolución actualizado y cargos aplicados si corresponde.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar la devolución: ' . $e->getMessage());
        }
    }
}