<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ASN;
use App\Models\ASNItem;
use App\Models\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log; // Importante para loguear fallos
use App\Services\BinAllocator; // Servicio de Inteligencia de Bines

class ReceptionController extends Controller
{
    /**
     * Listado de Recepciones (ASN) con filtros.
     */
    public function index(Request $request)
    {
        $query = ASN::with(['client', 'items']);

        // Filtro por búsqueda general (ASN, Referencia, Cliente)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('asn_number', 'like', "%{$search}%")
                  ->orWhere('document_ref', 'like', "%{$search}%")
                  ->orWhereHas('client', function($q) use ($search) {
                      $q->where('company_name', 'like', "%{$search}%");
                  });
        }

        // Filtro por estado
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $asns = $query->orderBy('created_at', 'desc')->paginate(15);
        
        return view('admin.operations.receptions.index', compact('asns'));
    }

    /**
     * Muestra el formulario para registrar una nueva ASN.
     */
    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        // Generamos un ID sugerido único para facilitar la creación
        $nextId = 'ASN-' . strtoupper(Str::random(6)); 
        
        return view('admin.operations.receptions.create', compact('clients', 'nextId'));
    }

    /**
     * Guarda la ASN y ejecuta la lógica de Auto-Slotting.
     */
    public function store(Request $request)
    {
        // Validación de datos
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'asn_number' => 'required|unique:asns,asn_number',
            'expected_arrival_date' => 'required|date',
            'total_packages' => 'required|integer|min:1', // Validar campo de cajas
            'items' => 'required|array|min:1', // Debe tener al menos un producto
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Crear Cabecera de la ASN
            $asn = ASN::create([
                'asn_number' => $request->asn_number,
                'client_id' => $request->client_id,
                'expected_arrival_date' => $request->expected_arrival_date,
                'carrier_name' => $request->carrier_name,
                'tracking_number' => $request->tracking_number,
                'document_ref' => $request->document_ref,
                'total_packages' => $request->total_packages, // Guardar el total de cajas para facturación
                'notes' => $request->notes,
                'status' => 'pending' // Estado inicial
            ]);

            // 2. Crear los Ítems (Detalle de productos esperados)
            foreach ($request->items as $itemData) {
                ASNItem::create([
                    'asn_id' => $asn->id,
                    'product_id' => $itemData['product_id'],
                    'expected_quantity' => $itemData['qty'],
                    'received_quantity' => 0,
                    'status' => 'pending'
                ]);
            }

            // 3. EJECUTAR INTELIGENCIA (AUTO-SLOTTING)
            // Intentamos calcular las ubicaciones ideales automáticamente
            try {
                $allocator = new BinAllocator();
                $allocator->allocateASN($asn);
            } catch (\Exception $e) {
                // Si falla el auto-slotting (ej: producto sin medidas), continuamos sin error fatal.
                // La ASN se crea pero requerirá asignación manual.
                Log::warning("Fallo en Auto-Slotting ASN {$asn->asn_number}: " . $e->getMessage());
            }
        });

        return redirect()->route('admin.receptions.index')
            ->with('success', 'ASN creada exitosamente. Planificación de ubicación generada.');
    }

    /**
     * Ver Detalle de la ASN con su Plan de Ubicación.
     */
    public function show($id)
    {
        // Cargamos relaciones profundas para mostrar el detalle completo
        $asn = ASN::with([
            'client', 
            'items.product', 
            'items.allocations.location.warehouse' // Para mostrar dónde se guardará cada cosa
        ])->findOrFail($id);
        
        return view('admin.operations.receptions.show', compact('asn'));
    }

    /**
     * Eliminar una ASN (Solo si está en estado 'pending' o 'draft').
     */
    public function destroy($id)
    {
        $asn = ASN::findOrFail($id);

        // Protección de integridad: No borrar si ya se empezó a trabajar
        if (!in_array($asn->status, ['pending', 'draft'])) {
            return back()->withErrors(['error' => 'No se puede eliminar una ASN que ya está en proceso o completada.']);
        }

        DB::transaction(function () use ($asn) {
            // Eliminar asignaciones planificadas primero
            foreach ($asn->items as $item) {
                $item->allocations()->delete();
            }
            // Eliminar items
            $asn->items()->delete();
            // Eliminar cabecera
            $asn->delete();
        });

        return redirect()->route('admin.receptions.index')->with('success', 'ASN eliminada correctamente.');
    }

    /**
     * Generar vista de impresión de ETIQUETAS MASTER (Bultos).
     * Se genera una etiqueta por cada bulto/caja declarada en la ASN.
     * No se imprimen etiquetas de producto unitario aquí.
     */
    public function printLabels($id)
    {
        $asn = ASN::with('client')->findOrFail($id);
        
        $labels = [];
        // Aseguramos que haya al menos 1 bulto para imprimir si el campo es nulo o 0
        $totalPackages = max($asn->total_packages, 1);

        for ($i = 1; $i <= $totalPackages; $i++) {
            $labels[] = [
                'client_name'   => $asn->client->company_name, // Dato crítico 3PL para no mezclar carga
                'asn_number'    => $asn->asn_number,
                'tracking'      => $asn->tracking_number ?? 'S/N',
                'carrier'       => $asn->carrier_name ?? 'N/A',
                'box_number'    => "$i / $totalPackages", // Ej: Caja 1 / 10
                // QR Único para identificar este bulto específico al escanear en Recepción
                'qr_data'       => "ASN:{$asn->asn_number}|BOX:{$i}|TOTAL:{$totalPackages}",
                'date'          => now()->format('d/m/Y'),
                'type'          => 'MASTER LABEL'
            ];
        }

        return view('admin.operations.receptions.print_labels', compact('asn', 'labels'));
    }
}