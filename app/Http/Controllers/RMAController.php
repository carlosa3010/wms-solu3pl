<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RMA;
use App\Models\RMAItem;
use App\Models\Order;
use App\Models\Client;
use App\Models\Inventory;
use App\Models\Location;
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
    }

    /**
     * Procesa el re-ingreso a inventario o cuarentena.
     */
    public function process(Request $request, $id)
    {
        $rma = RMA::with('items')->findOrFail($id);
        
        // Aquí se implementaría la lógica para mover a ubicación de 'Devoluciones' 
        // o directamente al stock general si el producto está en buen estado.
        return back()->with('success', 'Estado de la devolución actualizado.');
    }
}