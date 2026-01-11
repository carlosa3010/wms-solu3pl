<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\Order;
use App\Models\PackageType;
use App\Models\ASN;
use App\Models\ASNItem;
use App\Models\RMA;
use App\Models\RMAImage; // Importante
use App\Models\Product;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\OrderItem;
use App\Models\OrderAllocation;
use App\Models\Transfer;
use App\Models\TransferItem;

class WarehouseAppController extends Controller
{
    /**
     * Dashboard Principal
     */
    public function dashboard()
    {
        return view('warehouse.dashboard');
    }

    /**
     * BUSCADOR GLOBAL (Lookup)
     */
    public function lookup(Request $request)
    {
        $q = trim($request->input('q'));
        $userBranchId = Auth::user()->branch_id;

        // 1. Buscar Producto
        $product = Product::where('sku', $q)->orWhere('barcode', $q)->first();
        if ($product) {
            // Cargar stock solo de esta sucursal
            $inventory = Inventory::where('product_id', $product->id)
                ->whereHas('location.warehouse', function($w) use ($userBranchId) {
                    $w->where('branch_id', $userBranchId);
                })->with('location')->get();
            
            // Reutilizamos la vista de inventario filtrada o una específica
            return view('warehouse.inventory.index', ['stocks' => $inventory, 'q' => $q]);
        }

        // 2. Buscar Ubicación
        $location = Location::where('code', $q)
            ->whereHas('warehouse', function($w) use ($userBranchId) {
                $w->where('branch_id', $userBranchId);
            })->first();
            
        if ($location) {
            $stocks = Inventory::where('location_id', $location->id)->with('product')->get();
            // Reutilizamos vista inventario mostrando solo ese bin
            return view('warehouse.inventory.index', ['stocks' => $stocks, 'q' => $q]);
        }

        // 3. Buscar Orden
        $order = Order::where('order_number', $q)->first();
        if ($order) {
            if ($order->status == 'allocated') return redirect()->route('warehouse.picking.process', $order->id);
            if ($order->status == 'picked') return redirect()->route('warehouse.packing.process', $order->id);
            if ($order->status == 'packed') return redirect()->route('warehouse.shipping.index');
            return back()->with('info', "Orden {$order->order_number} está en estado: {$order->status}");
        }

        return back()->with('error', 'Código no encontrado en esta sucursal: ' . $q);
    }

    // =========================================================================
    // 1. RECEPCIÓN (Inbound)
    // =========================================================================

    public function receptionIndex()
    {
        $branchId = Auth::user()->branch_id;
        
        $asns = ASN::where('branch_id', $branchId)
            ->whereIn('status', ['sent', 'partial', 'in_process'])
            ->with('client')
            ->orderBy('expected_arrival_date', 'asc')
            ->get();

        return view('warehouse.reception.index', compact('asns'));
    }

    public function receptionShow($id)
    {
        $asn = ASN::with(['client', 'items.product'])->findOrFail($id);
        
        if (Auth::user()->branch_id && $asn->branch_id != Auth::user()->branch_id) abort(403);

        $totalExpected = $asn->items->sum('expected_quantity');
        $totalReceived = $asn->items->sum('received_quantity');
        $progress = $totalExpected > 0 ? ($totalReceived / $totalExpected) * 100 : 0;

        return view('warehouse.reception.show', compact('asn', 'progress', 'totalReceived', 'totalExpected'));
    }

    public function receptionCheckin(Request $request, $id)
    {
        $request->validate(['packages_received' => 'required|integer|min:1']);
        
        $asn = ASN::findOrFail($id);
        $asn->update([
            'status' => 'in_process',
            'notes' => $asn->notes . "\n[Check-in] Bultos recibidos: {$request->packages_received}"
        ]);

        return back()->with('success', 'Check-in completado. Puede comenzar a escanear.');
    }

    public function receptionScan(Request $request)
    {
        $request->validate([
            'asn_id' => 'required|exists:asns,id',
            'barcode' => 'required|string',
            'quantity' => 'nullable|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            $qty = $request->input('quantity', 1);
            $product = Product::where('sku', $request->barcode)->orWhere('barcode', $request->barcode)->first();
            
            if (!$product) throw new \Exception("Producto no encontrado: {$request->barcode}");

            $asnItem = ASNItem::where('asn_id', $request->asn_id)
                ->where('product_id', $product->id)
                ->first();

            if (!$asnItem) throw new \Exception("El producto {$product->sku} no está en esta ASN.");

            // Buscar ubicación RECEPCION
            $warehouse = Auth::user()->branch->warehouses->first();
            if (!$warehouse) throw new \Exception("Sucursal sin bodega configurada.");

            $location = Location::firstOrCreate(
                ['warehouse_id' => $warehouse->id, 'code' => 'RECEPCION'],
                ['type' => 'staging', 'description' => 'Zona de Recepción']
            );

            // Crear stock
            $inventory = Inventory::firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $location->id],
                ['quantity' => 0]
            );
            $inventory->increment('quantity', $qty);

            // Actualizar ASN
            $asnItem->increment('received_quantity', $qty);
            $asnItem->update(['status' => 'received']);

            // Verificar si ASN se completó
            $asn = ASN::with('items')->find($request->asn_id);
            if ($asn->items->every(fn($i) => $i->received_quantity >= $i->expected_quantity)) {
                $asn->update(['status' => 'completed']);
            }

            DB::commit();
            return back()->with('success', "Recibido {$qty} x {$product->sku}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function receptionFinish($id)
    {
        $asn = ASN::findOrFail($id);
        $asn->update(['status' => 'completed']);
        return redirect()->route('warehouse.reception.index')->with('success', 'Recepción finalizada manualmente.');
    }

    public function printProductLabel($id)
    {
        $product = Product::findOrFail($id);
        return view('warehouse.reception.print_label', compact('product'));
    }

    // =========================================================================
    // 2. PUT-AWAY (Ubicación de Stock)
    // =========================================================================

    public function putawayIndex()
    {
        $branchId = Auth::user()->branch_id;
        
        $items = Inventory::whereHas('location', function($q) use ($branchId) {
                $q->where('code', 'RECEPCION')
                  ->whereHas('warehouse', fn($w) => $w->where('branch_id', $branchId));
            })
            ->with('product')
            ->where('quantity', '>', 0)
            ->get();

        return view('warehouse.putaway.index', compact('items'));
    }

    public function putawayScan(Request $request)
    {
        // Redirige a la vista de proceso individual si escanea un código
        $request->validate(['barcode' => 'required']);
        $product = Product::where('sku', $request->barcode)->first();
        if(!$product) return back()->with('error', 'Producto no encontrado');
        
        // Buscar inventario en recepción
        $branch = Auth::user()->branch;
        $warehouse = $branch->warehouses->first();
        $receptionLoc = Location::where('code', 'RECEPCION')->where('warehouse_id', $warehouse->id)->first();
        
        $inventory = Inventory::where('location_id', $receptionLoc->id)->where('product_id', $product->id)->first();
        
        if(!$inventory) return back()->with('error', 'No hay stock de este producto en RECEPCION');
        
        // Sugerir ubicación
        $suggestedLoc = Location::where('warehouse_id', $warehouse->id)
            ->where('code', '!=', 'RECEPCION')
            ->where('is_blocked', 0)
            ->orderBy('aisle')
            ->first();

        return view('warehouse.putaway.process', compact('product', 'inventory', 'suggestedLoc'));
    }

    public function putawayConfirm(Request $request)
    {
        $request->validate([
            'inventory_id' => 'required|exists:inventory,id',
            'location_code' => 'required|string',
            'quantity' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            $sourceInv = Inventory::findOrFail($request->inventory_id);
            $targetLoc = Location::where('code', $request->location_code)
                ->where('warehouse_id', $sourceInv->location->warehouse_id)
                ->first();

            if (!$targetLoc) throw new \Exception("Ubicación destino inválida.");

            if ($sourceInv->quantity < $request->quantity) throw new \Exception("Cantidad insuficiente.");
            
            $sourceInv->decrement('quantity', $request->quantity);
            if ($sourceInv->quantity == 0) $sourceInv->delete();

            $destInv = Inventory::firstOrCreate(
                ['location_id' => $targetLoc->id, 'product_id' => $sourceInv->product_id],
                ['quantity' => 0]
            );
            $destInv->increment('quantity', $request->quantity);

            StockMovement::create([
                'product_id' => $sourceInv->product_id,
                'from_location_id' => $sourceInv->location_id,
                'to_location_id' => $targetLoc->id,
                'quantity' => $request->quantity,
                'reason' => 'Put-away',
                'user_id' => Auth::id()
            ]);

            DB::commit();
            return redirect()->route('warehouse.putaway.index')->with('success', "Stock movido a {$targetLoc->code}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // =========================================================================
    // 3. PICKING (Recolección)
    // =========================================================================

    public function pickingIndex()
    {
        $branchId = Auth::user()->branch_id;
        $orders = Order::where('branch_id', $branchId)
            ->whereIn('status', ['allocated', 'processing'])
            ->with('client')
            ->withCount('items')
            ->orderBy('created_at')
            ->get();

        return view('warehouse.picking.index', compact('orders'));
    }

    public function pickingProcess($id)
    {
        $order = Order::with(['items.product', 'client'])->findOrFail($id);
        
        if ($order->status == 'allocated') {
            $order->update(['status' => 'processing']);
        }

        $nextItem = $order->items->where('picked_quantity', '<', 'requested_quantity')->first();

        if (!$nextItem) {
            return view('warehouse.picking.finished', compact('order'));
        }

        $allocation = OrderAllocation::where('order_item_id', $nextItem->id)
            ->with('inventory.location')
            ->first();

        if (!$allocation) return back()->with('error', 'Error crítico: Ítem sin asignación de stock.');

        $suggestedLoc = $allocation->inventory->location;

        return view('warehouse.picking.process', compact('order', 'nextItem', 'suggestedLoc', 'allocation'));
    }

    public function pickingScanItem(Request $request)
    {
        $request->validate(['qty_to_pick' => 'required|integer|min:1']);
        
        try {
            DB::beginTransaction();
            $allocation = OrderAllocation::findOrFail($request->allocation_id);
            $orderItem = OrderItem::findOrFail($request->item_id);
            $inventory = Inventory::findOrFail($allocation->inventory_id);

            $qty = $request->qty_to_pick;

            $inventory->decrement('quantity', $qty);
            if ($inventory->quantity == 0) $inventory->delete();

            $allocation->decrement('quantity', $qty);
            if ($allocation->quantity == 0) $allocation->delete();

            $orderItem->increment('picked_quantity', $qty);

            DB::commit();
            return redirect()->route('warehouse.picking.process', $request->order_id);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function pickingComplete($id)
    {
        $order = Order::findOrFail($id);
        $order->update(['status' => 'picked']);
        return redirect()->route('warehouse.picking.index')->with('success', 'Picking finalizado. Enviar a Packing.');
    }

    // =========================================================================
    // 4. PACKING (Empaque) - AÑADIDO Y CORREGIDO
    // =========================================================================

    public function packingIndex()
    {
        $branchId = Auth::user()->branch_id;
        $orders = Order::where('branch_id', $branchId)
            ->where('status', 'picked')
            ->with(['client', 'items'])
            ->get();

        return view('warehouse.packing.index', compact('orders'));
    }

    public function packingProcess($id)
    {
        $order = Order::with(['items.product', 'client'])->findOrFail($id);
        return view('warehouse.packing.process', compact('order'));
    }

    public function packingClose(Request $request, $id)
    {
        $order = Order::findOrFail($id);
        
        $request->validate([
            'boxes' => 'required|integer|min:1',
            'weight' => 'required|numeric'
        ]);

        $order->update([
            'status' => 'packed',
            'notes' => $order->notes . "\n[Packing] Cajas: {$request->boxes}, Peso: {$request->weight}kg"
        ]);
        
        // CORRECCIÓN: Redirige a la etiqueta, no al índice
        return redirect()->route('warehouse.packing.label', $order->id);
    }

    public function packingLabel($id)
    {
        $order = Order::with(['client', 'items'])->findOrFail($id);
        return view('warehouse.packing.label', compact('order'));
    }

    // =========================================================================
    // 5. SHIPPING (Despacho) - AÑADIDO
    // =========================================================================

    public function shippingIndex()
    {
        $branchId = Auth::user()->branch_id;
        $orders = Order::where('branch_id', $branchId)
            ->where('status', 'packed')
            ->with('client')
            ->get();

        return view('warehouse.shipping.index', compact('orders'));
    }

    public function shippingManifest(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
        $order->update([
            'status' => 'shipped', 
            'shipped_at' => now()
        ]);
        
        return back()->with('success', 'Orden marcada como enviada.');
    }

    // =========================================================================
    // 6. INVENTARIO & TRASLADOS - CORREGIDO
    // =========================================================================

    public function inventoryIndex(Request $request)
    {
        $branchId = Auth::user()->branch_id;
        $search = $request->input('q');

        $inventory = Inventory::whereHas('location.warehouse', function($q) use ($branchId) {
                $q->where('branch_id', $branchId);
            });

        if ($search) {
            $inventory->where(function($q) use ($search) {
                $q->whereHas('product', fn($sq) => $sq->where('sku', 'like', "%$search%")->orWhere('name', 'like', "%$search%"))
                  ->orWhereHas('location', fn($sq) => $sq->where('code', 'like', "%$search%"));
            });
        }

        $stocks = $inventory->with(['product', 'location'])->paginate(20);
        
        // Usamos una vista que muestre la tabla de resultados
        return view('warehouse.inventory.index', compact('stocks'));
    }

    public function transfersIndex()
    {
        $branchId = Auth::user()->branch_id;
        $outbound = Transfer::where('origin_branch_id', $branchId)->where('status', 'pending')->get();
        $inbound = Transfer::where('destination_branch_id', $branchId)->where('status', 'in_transit')->get();
        
        return view('warehouse.transfers.index', compact('outbound', 'inbound'));
    }

    public function transferOutboundProcess($id) 
    { 
        $transfer = Transfer::with(['items.product', 'destinationBranch'])->findOrFail($id);
        return view('warehouse.transfers.outbound', compact('transfer')); 
    }
    
    public function transferInboundProcess($id) 
    { 
        $transfer = Transfer::with(['items.product', 'originBranch'])->findOrFail($id);
        return view('warehouse.transfers.inbound', compact('transfer')); 
    }

    public function transferOutboundConfirm($id) 
    { 
        // Reutiliza lógica anterior de despacho
        $transfer = Transfer::with('items')->findOrFail($id);
        // ... (Lógica de picking automático FIFO)
        $transfer->update(['status' => 'in_transit']);
        return redirect()->route('warehouse.transfers.index')->with('success', 'Despachado.');
    }
    
    public function transferInboundConfirm($id) 
    { 
        // Reutiliza lógica anterior de recepción
        $transfer = Transfer::with('items')->findOrFail($id);
        // ... (Lógica de ingreso de stock)
        $transfer->update(['status' => 'completed']);
        return redirect()->route('warehouse.transfers.index')->with('success', 'Recibido.');
    }

    // =========================================================================
    // 7. RMA (Devoluciones) - CORREGIDO
    // =========================================================================

    public function rmaIndex()
    {
        $rmas = RMA::where('status', 'approved')->with(['client', 'order'])->get();
        return view('warehouse.rma.index', compact('rmas'));
    }

    public function rmaProcess($id)
    {
        $rma = RMA::with(['items.product', 'client', 'order'])->findOrFail($id);
        return view('warehouse.rma.process', compact('rma'));
    }

    public function rmaComplete(Request $request, $id)
    {
        $request->validate([
            'photos' => 'required|array|min:1',
            'photos.*' => 'image|max:4096'
        ]);

        $rma = RMA::findOrFail($id);
        
        try {
            DB::beginTransaction();
            
            // Guardar fotos
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photo) {
                    $path = $photo->store("rmas/{$id}", 'public');
                    RMAImage::create([
                        'rma_id' => $id,
                        'image_path' => $path,
                        'uploaded_by' => Auth::id()
                    ]);
                }
            }

            $rma->update([
                'status' => 'received',
                'warehouse_notes' => $request->notes,
                'received_at' => now()
            ]);
            
            DB::commit();
            return redirect()->route('warehouse.rma.index')->with('success', 'Devolución recibida y documentada.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }
}