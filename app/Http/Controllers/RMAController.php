<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RMA;
use App\Models\RMAItem;
use App\Models\Client;
use App\Models\ServiceCharge;
use App\Models\ClientBillingAgreement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
                  ->orWhere('customer_name', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%");
        }

        // Filtro por estado si es necesario
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rmas = $query->orderBy('created_at', 'desc')->paginate(15);
        return view('admin.operations.rma.index', compact('rmas'));
    }

    /**
     * Formulario para registrar una nueva solicitud de RMA.
     */
    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        // Generamos un folio sugerido
        $rmaNumber = 'RMA-' . date('Ymd') . '-' . strtoupper(Str::random(4));
        
        return view('admin.operations.rma.create', compact('clients', 'rmaNumber'));
    }

    /**
     * Almacena la solicitud de RMA inicial.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'rma_number' => 'required|unique:rmas,rma_number',
            'customer_name' => 'required|string',
            'reason' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function () use ($request) {
                // 1. Crear Cabecera
                $rma = RMA::create([
                    'rma_number' => $request->rma_number,
                    'client_id' => $request->client_id,
                    'order_id' => $request->order_id, // Puede ser nulo si no viene de una orden del sistema
                    'customer_name' => $request->customer_name,
                    'reason' => $request->reason,
                    'tracking_number' => $request->tracking_number,
                    'carrier_name' => $request->carrier_name,
                    'status' => 'pending', // Estado inicial esperando recepción física
                    'notes' => $request->notes
                ]);

                // 2. Crear Ítems
                foreach ($request->items as $item) {
                    RMAItem::create([
                        'rma_id' => $rma->id,
                        'product_id' => $item['product_id'],
                        'qty' => $item['qty'], // Asegurarse que la columna en DB sea 'qty' o 'quantity'
                        'condition' => 'pending', // Se evaluará al recibir
                    ]);
                }
            });

            return redirect()->route('admin.rma.index')->with('success', 'Solicitud de devolución registrada correctamente. Esperando recepción física.');

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al crear RMA: ' . $e->getMessage()]);
        }
    }

    /**
     * Ver detalle del RMA, evidencias (fotos) y panel de decisión.
     */
    public function show($id)
    {
        // Cargamos items.product para ver SKU y Nombre
        // Las fotos (reception_photos) se cargan automáticamente al acceder al modelo RMAItem si están en el JSON
        $rma = RMA::with(['client', 'order', 'items.product'])->findOrFail($id);
        
        return view('admin.operations.rma.show', compact('rma'));
    }

    /**
     * Actualiza el estado (Aprobar/Rechazar) y aplica cargos si corresponde.
     * Esta función es llamada desde el panel de decisión en la vista Show.
     */
    public function updateStatus(Request $request, $id)
    {
        $rma = RMA::with('client')->findOrFail($id);
        
        $request->validate([
            'status' => 'required|in:approved,rejected,waiting_client,processing',
            'admin_notes' => 'nullable|string'
        ]);

        try {
            DB::transaction(function () use ($rma, $request) {
                
                // 1. Actualizar el estado del RMA
                $rma->update([
                    'status' => $request->status,
                    'notes' => $rma->notes . "\n[" . now()->format('Y-m-d H:i') . "] Admin: " . $request->admin_notes,
                    // Si se aprueba o rechaza, marcamos como procesado
                    'processed_at' => in_array($request->status, ['approved', 'rejected']) ? now() : $rma->processed_at
                ]);

                // 2. LÓGICA DE FACTURACIÓN (Billing Service)
                // Si el RMA es APROBADO, aplicamos el cargo por servicio de devolución definido en el contrato
                if ($request->status === 'approved') {
                    
                    // Buscar acuerdo de facturación activo
                    $agreement = ClientBillingAgreement::where('client_id', $rma->client_id)
                        ->where('is_active', true)
                        ->with('servicePlan') // Asumiendo relación 'servicePlan' o 'profile'
                        ->first();

                    // Verificar si el plan tiene costo por RMA
                    // Nota: Ajusta 'rma_handling_fee' según el nombre real en tu tabla 'service_plans' o 'billing_profiles'
                    $fee = 0;
                    
                    if ($agreement && $agreement->servicePlan) {
                        // Opción A: El precio está en el plan
                        $fee = $agreement->servicePlan->rma_handling_fee ?? 0;
                    } elseif ($agreement && $agreement->custom_rma_fee) {
                        // Opción B: Precio personalizado en el acuerdo
                        $fee = $agreement->custom_rma_fee;
                    }

                    // Si hay un costo > 0, creamos el cargo
                    if ($fee > 0) {
                        ServiceCharge::create([
                            'client_id' => $rma->client_id,
                            'type' => 'rma_processing', // Tipo interno para contabilidad
                            'description' => "Procesamiento RMA #{$rma->rma_number}",
                            'amount' => $fee,
                            'currency' => 'USD',
                            'status' => 'pending', // Pendiente de facturar
                            'reference_type' => RMA::class,
                            'reference_id' => $rma->id,
                            'created_at' => now()
                        ]);
                    }
                }
            });

            return redirect()->route('admin.rma.show', $id)
                ->with('success', 'El estado del RMA ha sido actualizado. ' . ($request->status == 'approved' ? 'Cargos aplicados.' : ''));

        } catch (\Exception $e) {
            return back()->with('error', 'Error al procesar la actualización: ' . $e->getMessage());
        }
    }
}