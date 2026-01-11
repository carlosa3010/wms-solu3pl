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
use App\Models\Order; // Necesario para actualizar órdenes en espera
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
        $user = Auth::user();

        $query = Transfer::with(['originBranch', 'destinationBranch', 'items.product', 'items.targetLocation'])
            ->orderBy('created_at', 'desc');

        // Filtro por sucursal para operarios
        if ($user->branch_id) {
            $query->where(function($q) use ($user) {
                $q->where('origin_branch_id', $user->branch_id)
                  ->orWhere('destination_branch_id', $user->branch_id);
            });
        }

        $transfers = $query->paginate(15);

        return view('admin.operations.transfers.index', compact('transfers'));
    }

    public function create()
    {
        $user = Auth::user();

        // Si es admin global ve todas, si es operario solo su sucursal (como origen)
        if ($user->branch_id) {
            $branches = Branch::where('id', $user->branch_id)->with('warehouses')->get();
            $allBranches = Branch::where('is_active', true)->get(); // Para destino
        } else {
            $branches = Branch::with('warehouses')->where('is_active', true)->get();
            $allBranches = $branches;
        }

        // Productos con stock (optimizado)
        $products = Product::where('is_active', true)->orderBy('sku')->get();

        return view('admin.operations.transfers.create', compact('branches', 'allBranches', 'products'));
    }

    /**
     * Crea un traslado (Manual o Interno).
     */
    public function store(Request $request)
    {
        $request->validate([
            'origin_branch_id' => 'required|exists:branches,id',
            'destination_branch_id' => 'required|exists:branches,id', // Se eliminó 'different' para permitir internos
            'items' => 'required|array|min:1',
            'items.*.quantity' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            // 1. Determinar Tipo de Traslado
            $type = ($request->origin_branch_id == $request->destination_branch_id) 
                ? Transfer::TYPE_INTERNAL 
                : Transfer::TYPE_INTER_BRANCH;

            // 2. Crear Cabecera
            $transfer = Transfer::create([
                'origin_branch_id' => $request->origin_branch_id,
                'destination_branch_id' => $request->destination_branch_id,
                'transfer_number' => 'TR-' . strtoupper(Str::random(8)),
                'status' => 'pending',
                'type' => $type,
                'notes' => $request->notes ?? ($type == Transfer::TYPE_INTERNAL ? 'Movimiento Interno' : 'Traslado entre Sucursales'),
                'created_by' => Auth::id()
            ]);

            $destBranch = Branch::with('warehouses')->find($request->destination_branch_id);
            $destWarehouse = $destBranch->warehouses->first(); 
            $allocator = class_exists(BinAllocator::class) ? new BinAllocator() : null;

            // 3. Procesar Items
            foreach ($request->items as $itemData) {
                $product = Product::find($itemData['product_id']);
                
                // Sugerir bin destino (si existe lógica de inteligencia)
                $targetLocationId = null;
                if ($allocator && $destWarehouse) {
                    $res = $allocator->getBestLocationForProduct($product, $destWarehouse);
                    $targetLocationId = is_array($res) ? $res['location_id'] : $res;
                }

                // Validar Stock en Origen (Simple Check)
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
                    'target_location_id' => $targetLocationId 
                ]);
            }

            DB::commit();

            // Si es interno, podríamos redirigir a una vista diferente o procesarlo directo si se desea.
            // Por ahora mantenemos el flujo estándar: Crear -> Despachar (Sacar de bin) -> Recibir (Poner en bin)
            // Incluso en movimiento interno tiene sentido para asegurar que el operario fue al bin A y puso en bin B.
            
            return redirect()->route('admin.transfers.index')
                ->with('success', "Traslado {$transfer->transfer_number} ({$type}) creado correctamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['error' => 'Error al crear el traslado: ' . $e->getMessage()]);
        }
    }

    /**
     * Despacho: Picking en origen.
     * Mueve el stock de "Inventario Físico" a "En Tránsito".
     */
    public function ship($id)
    {
        $transfer = Transfer::with('items.product')->findOrFail($id);
        
        if ($transfer->status !== 'pending') {
            return back()->with('error', 'El traslado no está listo para despacho.');
        }

        try {
            DB::beginTransaction();

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

                // Picking (Consumo de inventario origen)
                foreach ($inventories as $inv) {
                    if ($needed <= 0) break;
                    
                    $take = min($needed, $inv->quantity);
                    $inv->decrement('quantity', $take);
                    
                    // Si queda en 0, eliminamos la fila para limpiar DB
                    if ($inv->quantity <= 0) $inv->delete();
                    
                    // Registro de Movimiento (Kardex)
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

            DB::commit();
            
            // Si es interno, el mensaje cambia ligeramente
            $msg = $transfer->isInternal() ? 'Producto retirado del origen. Proceda a ubicar en destino.' : 'Traslado despachado. Mercancía en tránsito.';
            
            return back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error en despacho: ' . $e->getMessage());
        }
    }

    /**
     * Recepción: Put-away en destino.
     * Mueve de "En Tránsito" a "Inventario Destino" y Libera Órdenes.
     */
    public function receive(Request $request, $id)
    {
        $transfer = Transfer::with(['items.product', 'destinationBranch.warehouses'])->findOrFail($id);
        
        if ($transfer->status !== 'in_transit') {
            return back()->with('error', 'El traslado debe estar en tránsito para ser recibido.');
        }

        try {
            DB::beginTransaction();
            
            $warehouse = $transfer->destinationBranch->warehouses->first();
            if (!$warehouse) throw new \Exception("La sucursal destino no tiene almacén configurado.");

            // Instancia opcional del allocator
            $allocator = class_exists(BinAllocator::class) ? new BinAllocator() : null;

            foreach ($transfer->items as $item) {
                $qtyRemaining = $item->quantity;
                $targetLocationId = null;

                // 1. Selección manual
                if ($request->location_id) {
                    $targetLocationId = $request->location_id;
                } 
                // 2. Pre-asignado en la creación
                elseif ($item->target_location_id) {
                    $targetLocationId = $item->target_location_id;
                }
                // 3. Inteligencia (BinAllocator)
                elseif ($allocator) {
                    $res = $allocator->getBestLocationForProduct($item->product, $warehouse);
                    $targetLocationId = is_array($res) ? $res['location_id'] : $res;
                }

                // 4. Fallback: Buscar cualquier ubicación 'storage' vacía o general
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

                // Crear/Incrementar Inventario en destino
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

            // ---------------------------------------------------------
            // CRÍTICO: Liberar Órdenes en Espera (Cross-Docking / Backorders)
            // ---------------------------------------------------------
            // Buscamos órdenes que estén esperando ESTE traslado específicamente
            $ordersWaiting = Order::where('transfer_id', $transfer->id)
                                  ->where('status', 'waiting_transfer')
                                  ->get();

            if ($ordersWaiting->count() > 0) {
                foreach ($ordersWaiting as $order) {
                    // Cambiamos estado a 'pending' para que aparezca en el Picking
                    $order->update([
                        'status' => 'pending',
                        'notes' => $order->notes . " [Stock recibido via TR-{$transfer->transfer_number}]"
                    ]);
                }
            }

            DB::commit();
            
            $msg = 'Mercancía recibida correctamente.';
            if ($ordersWaiting->count() > 0) {
                $msg .= " Se han liberado {$ordersWaiting->count()} pedido(s) para picking.";
            }

            return redirect()->route('admin.transfers.index')->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error en recepción: ' . $e->getMessage());
        }
    }
}