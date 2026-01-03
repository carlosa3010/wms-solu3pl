<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\Location;
use App\Models\Branch;
use App\Models\Product;
use App\Services\BinAllocator; 
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TransferController extends Controller
{
    /**
     * Listado de traslados con bines pre-asignados.
     */
    public function index()
    {
        $transfers = Transfer::with(['originBranch', 'destinationBranch', 'items.product', 'items.targetLocation'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.operations.transfers.index', compact('transfers'));
    }

    public function create()
    {
        $branches = Branch::with('warehouses')->where('is_active', true)->get();
        $products = Product::orderBy('sku')->get();
        return view('admin.operations.transfers.create', compact('branches', 'products'));
    }

    /**
     * Crea un traslado manual con inteligencia de bines.
     */
    public function store(Request $request)
    {
        $request->validate([
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id|different:origin_branch_id',
            'items' => 'required|array|min:1',
        ]);

        try {
            DB::transaction(function () use ($request) {
                $transfer = Transfer::create([
                    'origin_branch_id' => $request->origin_branch_id,
                    'destination_branch_id' => $request->destination_branch_id,
                    'transfer_number' => 'TR-' . strtoupper(Str::random(8)),
                    'status' => 'pending',
                    'notes' => $request->notes ?? 'Traslado Manual',
                    'created_by' => Auth::id()
                ]);

                $allocator = new BinAllocator();
                $destBranch = Branch::with('warehouses')->find($request->destination_branch_id);
                $destWarehouse = $destBranch->warehouses->first();

                foreach ($request->items as $itemData) {
                    $product = Product::find($itemData['product_id']);
                    // Sugerir bin destino desde la creación
                    $targetLocationId = $allocator->getBestLocationForProduct($product, $destWarehouse);

                    TransferItem::create([
                        'transfer_id' => $transfer->id,
                        'product_id' => $product->id,
                        'quantity' => $itemData['quantity'],
                        'target_location_id' => $targetLocationId
                    ]);
                }
            });

            return redirect()->route('admin.transfers.index')->with('success', 'Traslado registrado. Bines de destino pre-asignados mediante inteligencia.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al crear el traslado: ' . $e->getMessage()]);
        }
    }

    /**
     * Despacho: Picking multi-bin en origen.
     */
    public function ship($id)
    {
        $transfer = Transfer::with('items.product')->findOrFail($id);
        if ($transfer->status !== 'pending') return back()->with('error', 'El traslado no está listo para despacho.');

        try {
            DB::transaction(function () use ($transfer) {
                foreach ($transfer->items as $item) {
                    $needed = $item->quantity;
                    
                    // REVISIÓN SINTAXIS (Línea 100): Cambio a clausura tradicional para compatibilidad
                    $inventories = Inventory::where('product_id', $item->product_id)
                        ->where('quantity', '>', 0)
                        ->whereHas('location.warehouse', function ($q) use ($transfer) {
                            $q->where('branch_id', $transfer->origin_branch_id);
                        })
                        ->orderBy('quantity', 'desc')
                        ->get();

                    if ($inventories->sum('quantity') < $needed) {
                        throw new \Exception("Stock total insuficiente en origen para SKU: {$item->product->sku}");
                    }

                    // Picking multi-bin: recorremos los bines de la sede de origen hasta cubrir el pedido
                    foreach ($inventories as $inv) {
                        if ($needed <= 0) break;
                        
                        $take = min($needed, $inv->quantity);
                        $inv->decrement('quantity', $take);
                        
                        StockMovement::create([
                            'product_id' => $item->product_id,
                            'from_location_id' => $inv->location_id,
                            'quantity' => $take,
                            'reason' => 'Salida por Traslado #' . $transfer->transfer_number,
                            'reference_number' => $transfer->transfer_number,
                            'user_id' => Auth::id()
                        ]);
                        $needed -= $take;
                    }
                }
                $transfer->update(['status' => 'in_transit']);
            });
            return back()->with('success', 'Traslado despachado exitosamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error en despacho: ' . $e->getMessage());
        }
    }

    /**
     * Recepción Inteligente: Soporta múltiples bines (Put-away multi-bin).
     */
    public function receive(Request $request, $id)
    {
        $transfer = Transfer::with(['items.product', 'destinationBranch.warehouses'])->findOrFail($id);
        if ($transfer->status !== 'in_transit') return back()->with('error', 'El traslado debe estar en tránsito para ser recibido.');

        try {
            DB::transaction(function () use ($transfer, $request) {
                $allocator = new BinAllocator();

                foreach ($transfer->items as $item) {
                    $warehouse = $transfer->destinationBranch->warehouses->first();
                    $qtyRemaining = $item->quantity;

                    // BUCLE MULTI-BIN: Ubicar mercancía hasta agotar la cantidad recibida
                    while ($qtyRemaining > 0) {
                        $targetLocationId = null;
                        $canFit = $qtyRemaining;

                        // Determinar ubicación por prioridades
                        if ($request->location_id) {
                            // 1. Selección manual (el operario fuerza un bin específico)
                            $targetLocationId = $request->location_id;
                        } elseif ($item->target_location_id && $qtyRemaining == $item->quantity) {
                            // 2. Pre-asignado (respetar la planificación inicial si es el primer bin)
                            $targetLocationId = $item->target_location_id;
                        } else {
                            // 3. Inteligencia de bines: Busca el mejor hueco actual (basado en espacio y compatibilidad)
                            // El motor de inteligencia puede devolver un array indicando cuánto cabe en este bin
                            $allocationResult = $allocator->getBestLocationForProduct($item->product, $warehouse, $qtyRemaining);
                            
                            if (is_array($allocationResult)) {
                                $targetLocationId = $allocationResult['location_id'];
                                $canFit = $allocationResult['fits'] ?? $qtyRemaining;
                            } else {
                                $targetLocationId = $allocationResult;
                            }
                        }

                        if (!$targetLocationId) {
                            throw new \Exception("Sin bines disponibles o compatibles en destino para SKU: {$item->product->sku}");
                        }

                        $take = min($qtyRemaining, $canFit);

                        // Registrar inventario en la nueva ubicación
                        $inv = Inventory::firstOrCreate(
                            ['product_id' => $item->product_id, 'location_id' => $targetLocationId],
                            ['quantity' => 0]
                        );
                        $inv->increment('quantity', $take);

                        // Registrar entrada en Kardex
                        StockMovement::create([
                            'product_id' => $item->product_id,
                            'to_location_id' => $targetLocationId,
                            'quantity' => $take,
                            'reason' => 'Entrada por Recepción de Traslado #' . $transfer->transfer_number,
                            'reference_number' => $transfer->transfer_number,
                            'user_id' => Auth::id()
                        ]);

                        $qtyRemaining -= $take;

                        // Si el usuario forzó un bin manual, asumimos que todo entró ahí y salimos del bucle
                        if ($request->location_id) break;
                    }
                }
                $transfer->update(['status' => 'completed']);
            });
            return redirect()->route('admin.transfers.index')->with('success', 'Mercancía recibida y ubicada automáticamente en los bines asignados.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error en recepción inteligente: ' . $e->getMessage());
        }
    }
}