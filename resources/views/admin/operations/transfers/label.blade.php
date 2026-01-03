<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Warehouse;
use App\Models\Location;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransferController extends Controller
{
    public function index()
    {
        $transfers = StockMovement::with(['product', 'fromLocation.warehouse', 'toLocation.warehouse', 'user'])
            ->whereNotNull('from_location_id')
            ->whereNotNull('to_location_id')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.operations.transfers.index', compact('transfers'));
    }

    /**
     * Formulario de creación con carga de infraestructura.
     */
    public function create()
    {
        $branches = Branch::with('warehouses')->where('is_active', true)->get();
        // Solo cargamos productos que tengan stock real en alguna parte
        $products = Product::whereHas('inventory', function($q) {
            $q->where('quantity', '>', 0);
        })->orderBy('name')->get();

        return view('admin.operations.transfers.create', compact('branches', 'products'));
    }

    /**
     * Ejecuta el traslado físico y lógico.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_location_id' => 'required|exists:locations,id',
            'to_location_id' => 'required|exists:locations,id|different:from_location_id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string'
        ]);

        try {
            $transfer = DB::transaction(function () use ($request) {
                // 1. Validar disponibilidad en origen
                $sourceStock = Inventory::where('product_id', $request->product_id)
                    ->where('location_id', $request->from_location_id)
                    ->first();

                if (!$sourceStock || $sourceStock->quantity < $request->quantity) {
                    throw new \Exception("No hay suficiente stock en la ubicación de origen.");
                }

                // 2. Descontar stock
                $sourceStock->decrement('quantity', $request->quantity);

                // 3. Aumentar o Crear stock en destino
                $destStock = Inventory::firstOrCreate(
                    ['product_id' => $request->product_id, 'location_id' => $request->to_location_id],
                    ['quantity' => 0]
                );
                $destStock->increment('quantity', $request->quantity);

                // 4. Registrar en Kardex
                return StockMovement::create([
                    'product_id' => $request->product_id,
                    'from_location_id' => $request->from_location_id,
                    'to_location_id' => $request->to_location_id,
                    'quantity' => $request->quantity,
                    'reason' => $request->reason ?? 'Traslado Interno / Re-ubicación',
                    'reference_number' => 'TRF-' . time(),
                    'user_id' => Auth::id(),
                    'created_at' => now()
                ]);
            });

            return redirect()->route('admin.transfers.index')
                ->with('success', "Traslado {$transfer->reference_number} procesado. Puede imprimir la etiqueta ahora.")
                ->with('print_transfer_id', $transfer->id);

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Genera la etiqueta de transferencia (Pallet/Caja).
     */
    public function printLabel($id)
    {
        $movement = StockMovement::with(['product', 'fromLocation.warehouse.branch', 'toLocation.warehouse.branch', 'user'])->findOrFail($id);
        return view('admin.operations.transfers.label', compact('movement'));
    }

    /**
     * AJAX: Obtiene bines con stock de un producto en una bodega.
     */
    public function getSourceLocations(Request $request)
    {
        $locs = Location::whereHas('stock', function($q) use ($request) {
            $q->where('product_id', $request->product_id)->where('quantity', '>', 0);
        })->where('warehouse_id', $request->warehouse_id)
          ->with(['stock' => function($q) use ($request) {
              $q->where('product_id', $request->product_id);
          }])->get();

        return response()->json($locs);
    }
}