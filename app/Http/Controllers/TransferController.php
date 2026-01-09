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
use Illuminate\Support\Facades\Str;

class TransferController extends Controller
{
    /**
     * Listado de traslados.
     */
    public function index()
    {
        // Se asume que TransferItem tiene la relación 'targetLocation' definida
        $transfers = Transfer::with(['originBranch', 'destinationBranch', 'items.product', 'items.targetLocation'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.operations.transfers.index', compact('transfers'));
    }

    public function create()
    {
        $branches = Branch::with('warehouses')->where('is_active', true)->get();
        // Solo productos con stock físico
        $products = Product::whereHas('inventory', function($q){
            $q->where('quantity', '>', 0);
        })->orderBy('sku')->get();

        return view('admin.operations.transfers.create', compact('branches', 'products'));
    }

    /**
     * Crea un traslado manual.
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
                // 1. Crear Cabecera
                $transfer = Transfer::create([
                    'origin_branch_id' => $request->origin_branch_id,
                    'destination_branch_id' => $request->destination_branch_id,
                    'transfer_number' => 'TR-' . strtoupper(Str::random(8)),
                    'status' => 'pending',
                    'notes' => $request->notes ?? 'Traslado Manual',
                    'created_by' => Auth::id()
                ]);

                // Instancia del servicio de inteligencia (si existe)
                // Si no tienes BinAllocator, usa lógica simple o null
                $allocator = class_exists(BinAllocator::class) ? new BinAllocator() : null;
                
                $destBranch = Branch::with('warehouses')->find($request->destination_branch_id);
                $destWarehouse = $destBranch->warehouses->first(); // Asumiendo 1 almacén por branch principal

                // 2. Procesar Items
                foreach ($request->items as $itemData) {
                    $product = Product::find($itemData['product_id']);
                    
                    // Sugerir bin destino desde la creación si el servicio existe
                    $targetLocationId = null;
                    if ($allocator && $destWarehouse) {
                        $targetLocationId = $allocator->getBestLocationForProduct($product, $destWarehouse);
                    }

                    // A. Validar Stock Origen (Simple check)
                    // La validación real ocurre en el momento del 'ship' (picking), 
                    // pero es bueno prevenir aquí.
                    $stockEnOrigen = Inventory::where('product_id', $product->id)
                        ->whereHas('location.warehouse', function($q) use ($request) {
                            $q->where('branch_id', $request->origin_branch_id);
                        })->sum('quantity');

                    if ($stockEnOrigen < $itemData['quantity']) {
                        throw new \Exception("Stock insuficiente en origen para {$product->sku}. Disponible: {$stockEnOrigen}");
                    }

                    TransferItem::create([
                        'transfer_id' => $transfer->id,
                        'product_id' => $product->id,
                        'quantity' => $itemData['quantity'],
                        'target_location_id' => $targetLocationId // Puede ser null
                    ]);
                }
            });

            return redirect()->route('admin.transfers.index')->with('success', 'Traslado registrado correctamente.');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Error al crear el traslado: ' . $e->getMessage()]);
        }
    }

    /**
     * Despacho: Picking multi-bin en origen.
     * Mueve el stock de "Inventario" a "En Tránsito" (Lógica Lógica).
     */
    public function ship($id)
    {
        $transfer = Transfer::with('items.product')->findOrFail($id);
        if ($transfer->status !== 'pending') return back()->with('error', 'El traslado no está listo para despacho.');

        try {
            DB::transaction(function () use ($transfer) {
                foreach ($transfer->items as $item) {
                    $needed = $item->quantity;
                    
                    // Buscar stock FIFO en la sucursal de origen
                    $inventories = Inventory::where('product_id', $item->product_id)
                        ->where('quantity', '>', 0)
                        ->whereHas('location.warehouse', function ($q) use ($transfer) {
                            $q->where('branch_id', $transfer->origin_branch_id);
                        })
                        ->orderBy('created_at', 'asc') // FIFO
                        ->get();

                    if ($inventories->sum('quantity') < $needed) {
                        throw new \Exception("Stock total insuficiente en origen para SKU: {$item->product->sku}");
                    }

                    // Picking multi-bin
                    foreach ($inventories as $inv) {
                        if ($needed <= 0) break;
                        
                        $take = min($needed, $inv->quantity);
                        $inv->decrement('quantity', $take);
                        
                        // Si queda en 0, se elimina la fila de inventario
                        if ($inv->quantity == 0) $inv->delete();
                        
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
     * Recepción: Put-away en destino.
     */
    public function receive(Request $request, $id)
    {
        $transfer = Transfer::with(['items.product', 'destinationBranch.warehouses'])->findOrFail($id);
        if ($transfer->status !== 'in_transit') return back()->with('error', 'El traslado debe estar en tránsito para ser recibido.');

        try {
            DB::transaction(function () use ($transfer, $request) {
                // Instancia opcional del allocator
                $allocator = class_exists(BinAllocator::class) ? new BinAllocator() : null;

                foreach ($transfer->items as $item) {
                    $warehouse = $transfer->destinationBranch->warehouses->first();
                    // Si no hay warehouse definido en destino, fallar o usar default
                    if (!$warehouse) throw new \Exception("La sucursal destino no tiene almacén configurado.");

                    $qtyRemaining = $item->quantity;

                    // Lógica simplificada de recepción (todo a un bin o bin sugerido)
                    // Si se quiere multi-bin complex, se requiere bucle while.
                    // Aquí usamos una versión directa para asegurar estabilidad.

                    $targetLocationId = null;

                    // 1. Selección manual del request (si aplica a todos los items, cuidado)
                    if ($request->location_id) {
                        $targetLocationId = $request->location_id;
                    } 
                    // 2. Pre-asignado en la creación
                    elseif ($item->target_location_id) {
                        $targetLocationId = $item->target_location_id;
                    }
                    // 3. Inteligencia
                    elseif ($allocator) {
                        $res = $allocator->getBestLocationForProduct($item->product, $warehouse);
                        $targetLocationId = is_array($res) ? $res['location_id'] : $res;
                    }

                    // 4. Fallback: Buscar cualquier ubicación 'storage' vacía o con el mismo producto
                    if (!$targetLocationId) {
                        $loc = Location::where('warehouse_id', $warehouse->id)
                            ->where('is_blocked', false)
                            ->orderBy('id')
                            ->first();
                        $targetLocationId = $loc ? $loc->id : null;
                    }

                    if (!$targetLocationId) {
                        throw new \Exception("No hay ubicación disponible para recibir {$item->product->sku}");
                    }

                    // Crear Inventario en destino
                    $inv = Inventory::firstOrCreate(
                        ['product_id' => $item->product_id, 'location_id' => $targetLocationId],
                        ['quantity' => 0]
                    );
                    $inv->increment('quantity', $qtyRemaining);

                    // Registrar Kardex
                    StockMovement::create([
                        'product_id' => $item->product_id,
                        'to_location_id' => $targetLocationId,
                        'quantity' => $qtyRemaining,
                        'reason' => 'Entrada por Recepción de Traslado #' . $transfer->transfer_number,
                        'reference_number' => $transfer->transfer_number,
                        'user_id' => Auth::id()
                    ]);
                }
                $transfer->update(['status' => 'completed']);
            });
            return redirect()->route('admin.transfers.index')->with('success', 'Mercancía recibida correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error en recepción: ' . $e->getMessage());
        }
    }
}