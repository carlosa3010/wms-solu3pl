<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ASN;
use App\Models\ASNItem;
use App\Models\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
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
                // Log::error("Fallo en Auto-Slotting ASN {$asn->asn_number}: " . $e->getMessage());
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
     * Generar vista de impresión de etiquetas UNITARIAS.
     * Genera una etiqueta individual por cada producto físico (1 a 1) para pegar en la caja.
     */
    public function printLabels($id)
    {
        $asn = ASN::with(['items.product', 'items.allocations.location'])->findOrFail($id);
        
        $labels = [];
        
        foreach ($asn->items as $item) {
            // Escenario A: Con Auto-Slotting (Ubicaciones asignadas por el sistema)
            if ($item->allocations->count() > 0) {
                foreach ($item->allocations as $allocation) {
                    $qty = $allocation->quantity;
                    
                    // Generamos UNA etiqueta por cada unidad física planificada para esta ubicación
                    for ($i = 1; $i <= $qty; $i++) {
                        $labels[] = [
                            'product_name' => $item->product->name,
                            'sku' => $item->product->sku,
                            'location_code' => $allocation->location->code, // Destino específico
                            'asn_number' => $asn->asn_number,
                            'qr_data' => $allocation->location->code, // Escanear para confirmar guardado
                            'counter' => "$i / $qty", // Ej: "Caja 5 / 20"
                            'quantity' => 1 // Cada etiqueta representa 1 unidad
                        ];
                    }
                }
            } 
            // Escenario B: Sin asignación (Ubicación manual requerida)
            else {
                $qty = $item->expected_quantity;
                // Generamos etiquetas genéricas para asignar en piso
                for ($i = 1; $i <= $qty; $i++) {
                    $labels[] = [
                        'product_name' => $item->product->name,
                        'sku' => $item->product->sku,
                        'location_code' => 'POR ASIGNAR',
                        'asn_number' => $asn->asn_number,
                        'qr_data' => $item->product->sku, // Escanear producto para identificar
                        'counter' => "$i / $qty",
                        'quantity' => 1
                    ];
                }
            }
        }

        return view('admin.operations.receptions.print_labels', compact('asn', 'labels'));
    }
}