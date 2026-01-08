<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ASN;
use App\Models\ASNItem;
use App\Models\Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\BinAllocator; 

class ReceptionController extends Controller
{
    /**
     * Listado de Recepciones (ASN) con filtros CORREGIDOS.
     */
    public function index(Request $request)
    {
        $query = ASN::with(['client', 'items'])
                    ->orderBy('created_at', 'desc'); // Ordenar por defecto

        // 1. Filtro por estado (Aplicar primero)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // 2. Búsqueda General (AGRUPADA para no romper el filtro de estado)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('asn_number', 'like', "%{$search}%")
                  ->orWhere('document_ref', 'like', "%{$search}%")
                  ->orWhere('tracking_number', 'like', "%{$search}%") // Agregado tracking
                  ->orWhereHas('client', function($subQ) use ($search) {
                      $subQ->where('company_name', 'like', "%{$search}%");
                  });
            });
        }

        $asns = $query->paginate(15)->withQueryString(); // Mantiene filtros en paginación
        
        return view('admin.operations.receptions.index', compact('asns'));
    }

    /**
     * Muestra el formulario para registrar una nueva ASN.
     */
    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        $nextId = 'ASN-' . strtoupper(Str::random(6)); 
        
        return view('admin.operations.receptions.create', compact('clients', 'nextId'));
    }

    /**
     * Guarda la ASN y ejecuta Auto-Slotting.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'asn_number' => 'required|unique:asns,asn_number',
            'expected_arrival_date' => 'required|date',
            'total_packages' => 'required|integer|min:1',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Cabecera
            $asn = ASN::create([
                'asn_number' => $request->asn_number,
                'client_id' => $request->client_id,
                'expected_arrival_date' => $request->expected_arrival_date,
                'carrier_name' => $request->carrier_name,
                'tracking_number' => $request->tracking_number,
                'document_ref' => $request->document_ref,
                'total_packages' => $request->total_packages,
                'notes' => $request->notes,
                'status' => 'pending'
            ]);

            // 2. Ítems
            foreach ($request->items as $itemData) {
                ASNItem::create([
                    'asn_id' => $asn->id,
                    'product_id' => $itemData['product_id'],
                    'expected_quantity' => $itemData['qty'],
                    'received_quantity' => 0,
                    'status' => 'pending'
                ]);
            }

            // 3. Auto-Slotting (Inteligencia de ubicación)
            try {
                if (class_exists(BinAllocator::class)) {
                    $allocator = new BinAllocator();
                    $allocator->allocateASN($asn);
                }
            } catch (\Exception $e) {
                Log::warning("Fallo en Auto-Slotting ASN {$asn->asn_number}: " . $e->getMessage());
            }
        });

        return redirect()->route('admin.receptions.index')
            ->with('success', 'ASN creada exitosamente.');
    }

    /**
     * Ver Detalle (Dashboard de Supervisión).
     */
    public function show($id)
    {
        $asn = ASN::with([
            'client', 
            'items.product',
            // Si usas el sistema de allocated bins, descomenta la siguiente línea:
            'items.allocations.location' 
        ])->findOrFail($id);

        // Cálculos para KPIs en tiempo real
        $totalExpected = $asn->items->sum('expected_quantity');
        $totalReceived = $asn->items->sum('received_quantity');
        
        // Evitar división por cero
        $progress = $totalExpected > 0 ? round(($totalReceived / $totalExpected) * 100) : 0;

        // Detectar si hay discrepancias (Faltantes o Sobrantes) para mostrar alertas
        $hasDiscrepancies = $asn->items->contains(function ($item) {
            return $item->received_quantity !== $item->expected_quantity;
        });

        return view('admin.operations.receptions.show', compact('asn', 'totalExpected', 'totalReceived', 'progress', 'hasDiscrepancies'));
    }

    /**
     * Eliminar ASN (Solo si no ha iniciado).
     */
    public function destroy($id)
    {
        $asn = ASN::findOrFail($id);

        // Bloquear borrado si ya se empezó a trabajar en bodega
        if (!in_array($asn->status, ['pending', 'draft'])) {
            return back()->withErrors(['error' => 'No se puede eliminar una ASN en proceso o completada.']);
        }

        DB::transaction(function () use ($asn) {
            // Limpiar asignaciones si existen
            if (method_exists($asn->items()->getRelated(), 'allocations')) {
                foreach ($asn->items as $item) {
                    $item->allocations()->delete();
                }
            }
            
            $asn->items()->delete();
            $asn->delete();
        });

        return redirect()->route('admin.receptions.index')->with('success', 'ASN eliminada correctamente.');
    }

    /**
     * Imprimir Etiquetas Master de Bultos.
     */
    public function printLabels($id)
    {
        $asn = ASN::with('client')->findOrFail($id);
        
        $labels = [];
        $totalPackages = max($asn->total_packages, 1);

        for ($i = 1; $i <= $totalPackages; $i++) {
            $labels[] = [
                'client_name'   => $asn->client->company_name,
                'asn_number'    => $asn->asn_number,
                'tracking'      => $asn->tracking_number ?? 'S/N',
                'carrier'       => $asn->carrier_name ?? 'N/A',
                'box_number'    => "$i / $totalPackages",
                // QR para "Check-in" rápido de bultos
                'qr_data'       => "ASN:{$asn->asn_number}|BOX:{$i}|TOTAL:{$totalPackages}",
                'date'          => now()->format('d/m/Y'),
                'type'          => 'MASTER LABEL'
            ];
        }

        return view('admin.operations.receptions.print_labels', compact('asn', 'labels'));
    }
}