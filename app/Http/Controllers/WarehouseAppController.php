<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Order;
use App\Models\PackageType;
use App\Models\ASN;
use App\Models\ASNItem;
use App\Models\RMA;
use App\Models\Product;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\StockMovement;
use App\Models\OrderItem;
use App\Models\OrderAllocation; // Asegúrate de importar esto para el Picking

class WarehouseAppController extends Controller
{
    /**
     * Dashboard principal del operador
     */
    public function index()
    {
        return view('warehouse.dashboard');
    }

    /**
     * BUSCADOR GLOBAL: Escanea lo que sea y te dice qué es.
     */
    public function lookup(Request $request)
    {
        $q = $request->q;
        
        // 1. Buscar producto
        $product = Product::with('inventory.location')->where('sku', $q)->first();
        if($product) return view('warehouse.lookup.product', compact('product'));

        // 2. Buscar ubicación
        $location = Location::where('code', $q)->first();
        if($location) return view('warehouse.lookup.location', compact('location'));

        // 3. Buscar orden
        $order = Order::where('order_number', $q)->first();
        if($order) return redirect()->route('warehouse.picking.process', $order->id);

        return back()->with('error', 'Código no encontrado: ' . $q);
    }

    // ==========================================
    // 1. RECEPCIÓN (INBOUND)
    // ==========================================
    
    public function receptionIndex()
    {
        $asns = ASN::whereIn('status', ['sent', 'partial', 'pending', 'draft', 'in_process'])
                    ->orderBy('expected_arrival_date', 'asc')
                    ->get();
        return view('warehouse.reception.index', compact('asns'));
    }

    public function receptionShow($id)
    {
        $asn = ASN::with(['client', 'items.product'])->findOrFail($id);
        
        $totalExpected = $asn->items->sum('expected_quantity');
        $totalReceived = $asn->items->sum('received_quantity');
        $progress = $totalExpected > 0 ? ($totalReceived / $totalExpected) * 100 : 0;

        return view('warehouse.reception.show', compact('asn', 'progress', 'totalReceived', 'totalExpected'));
    }

    public function receptionCheckin(Request $request, $id)
    {
        $request->validate(['packages_received' => 'required|integer|min:1']);
        
        $asn = ASN::findOrFail($id);
        
        if ($request->packages_received != $asn->total_packages) {
            $asn->notes .= "\n[Check-in] Bultos declarados: {$asn->total_packages}, Recibidos: {$request->packages_received}";
        }

        $asn->update(['status' => 'in_process']);

        return back()->with('success', 'Check-in completado. Puede escanear productos.');
    }

    public function receptionScan(Request $request)
    {
        $request->validate([
            'asn_id' => 'required|exists:asns,id',
            'barcode' => 'required|string',
            'serial_number' => 'nullable|string',
            'is_damaged' => 'nullable|boolean'
        ]);

        try {
            DB::beginTransaction();

            $product = Product::where('sku', $request->barcode)->first();
            if (!$product) return back()->with('error', 'Producto no encontrado en el maestro.');

            $asnItem = ASNItem::where('asn_id', $request->asn_id)
                              ->where('product_id', $product->id)
                              ->first();

            if (!$asnItem) return back()->with('error', "El SKU {$product->sku} no pertenece a esta ASN.");

            // Validar Serial
            if ($product->requires_serial_number && empty($request->serial_number)) {
                return back()->with('ask_serial', [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'barcode' => $request->barcode
                ]);
            }

            if ($request->serial_number) {
                if (Inventory::where('product_id', $product->id)->where('lpn', $request->serial_number)->exists()) {
                    return back()->with('error', "El serial {$request->serial_number} ya existe.");
                }
            }

            // Actualizar conteo ASN
            $asnItem->increment('received_quantity');
            $asnItem->update(['status' => 'received']);

            // Ubicación
            $warehouseId = Auth::user()->warehouse_id ?? 8;
            $locationCode = $request->is_damaged ? 'CUARENTENA' : 'RECEPCION';
            $locationType = $request->is_damaged ? 'quarantine' : 'staging';

            $location = Location::firstOrCreate(
                ['code' => $locationCode, 'warehouse_id' => $warehouseId],
                ['type' => $locationType, 'status' => 'active', 'is_blocked' => $request->is_damaged]
            );

            // Crear Inventario
            if ($request->serial_number) {
                Inventory::create([
                    'product_id' => $product->id,
                    'location_id' => $location->id,
                    'quantity' => 1,
                    'lpn' => $request->serial_number
                ]);
            } else {
                $inventory = Inventory::where('product_id', $product->id)
                                      ->where('location_id', $location->id)
                                      ->whereNull('lpn')
                                      ->first();

                if ($inventory) {
                    $inventory->increment('quantity');
                } else {
                    Inventory::create([
                        'product_id' => $product->id,
                        'location_id' => $location->id,
                        'quantity' => 1,
                    ]);
                }
            }
            
            // Estado ASN
            $asn = ASN::with('items')->find($request->asn_id);
            $allComplete = $asn->items->every(fn($i) => $i->received_quantity >= $i->expected_quantity);

            if ($allComplete) $asn->update(['status' => 'completed']);

            DB::commit();

            $msg = "Recibido: {$product->sku}";
            if($request->serial_number) $msg .= " (S/N: {$request->serial_number})";
            if($request->is_damaged) $msg .= " [DAÑADO]";

            return back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function receptionUndo(Request $request)
    {
        $request->validate([
            'asn_id' => 'required',
            'product_id' => 'required'
        ]);

        try {
            DB::beginTransaction();

            $asnItem = ASNItem::where('asn_id', $request->asn_id)
                              ->where('product_id', $request->product_id)
                              ->firstOrFail();

            if ($asnItem->received_quantity > 0) {
                $asnItem->decrement('received_quantity');

                $warehouseId = Auth::user()->warehouse_id ?? 8;
                $location = Location::where('code', 'RECEPCION')->where('warehouse_id', $warehouseId)->first();

                if ($location) {
                    $inventory = Inventory::where('product_id', $request->product_id)
                                          ->where('location_id', $location->id)
                                          ->whereNull('lpn')
                                          ->first();
                    
                    if(!$inventory) {
                         $inventory = Inventory::where('product_id', $request->product_id)
                                          ->where('location_id', $location->id)
                                          ->latest()
                                          ->first();
                    }

                    if ($inventory && $inventory->quantity > 0) {
                        $inventory->decrement('quantity');
                        if ($inventory->quantity == 0) $inventory->delete();
                    }
                }
                
                $asnItem->asn->update(['status' => 'in_process']);
            }

            DB::commit();
            return back()->with('success', 'Corrección realizada: -1 unidad.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function receptionFinish(Request $request, $id)
    {
        try {
            $asn = ASN::findOrFail($id);
            if ($asn->items->sum('received_quantity') == 0) {
                return back()->with('error', 'No se ha recibido nada.');
            }
            $asn->update(['status' => 'completed']);
            return redirect()->route('warehouse.reception.index')->with('success', 'Recepción finalizada con faltantes.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function printProductLabel($id)
    {
        $product = Product::findOrFail($id);
        return view('warehouse.reception.print_label', compact('product'));
    }

    // ==========================================
    // 2. PUT-AWAY (UBICACIÓN)
    // ==========================================

    public function putawayIndex()
    {
        $warehouseId = Auth::user()->warehouse_id ?? 8;
        $receptionLoc = Location::where('code', 'RECEPCION')->where('warehouse_id', $warehouseId)->first();

        if (!$receptionLoc) {
            return view('warehouse.putaway.index', ['items' => []])->withErrors(['error' => 'No se encontró la ubicación RECEPCION.']);
        }

        $items = Inventory::with('product')
                          ->where('location_id', $receptionLoc->id)
                          ->where('quantity', '>', 0)
                          ->get();

        return view('warehouse.putaway.index', compact('items'));
    }

    public function putawayScan(Request $request)
    {
        $request->validate(['barcode' => 'required']);

        $product = Product::where('sku', $request->barcode)->first();
        if (!$product) return back()->with('error', 'Producto no encontrado.');

        $warehouseId = Auth::user()->warehouse_id ?? 8;
        $receptionLoc = Location::where('code', 'RECEPCION')->where('warehouse_id', $warehouseId)->first();
        
        $inventory = Inventory::where('location_id', $receptionLoc->id)
                              ->where('product_id', $product->id)
                              ->where('quantity', '>', 0)
                              ->first();

        if (!$inventory) return back()->with('error', 'No hay stock en RECEPCION.');

        // Inteligencia de Sugerencia
        $suggestedLoc = Location::whereHas('inventory', function($q) use ($product) {
                                    $q->where('product_id', $product->id);
                                })
                                ->where('code', '!=', 'RECEPCION')
                                ->where('is_blocked', 0)
                                ->first();

        if (!$suggestedLoc) {
            $suggestedLoc = Location::whereDoesntHave('inventory', function($q) {
                                        $q->where('quantity', '>', 0);
                                    })
                                    ->where('type', 'storage')
                                    ->where('is_blocked', 0)
                                    ->orderBy('aisle')->orderBy('level')
                                    ->first();
        }

        return view('warehouse.putaway.process', [
            'product' => $product,
            'inventory' => $inventory,
            'suggestedLoc' => $suggestedLoc
        ]);
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

            $targetLoc = Location::where('code', $request->location_code)->first();
            if (!$targetLoc) throw new \Exception("Ubicación {$request->location_code} no existe.");
            if ($targetLoc->code === 'RECEPCION') throw new \Exception("Destino inválido.");

            $sourceInv = Inventory::findOrFail($request->inventory_id);
            
            if ($sourceInv->quantity < $request->quantity) {
                throw new \Exception("Cantidad insuficiente.");
            }

            // Movimiento
            $sourceInv->decrement('quantity', $request->quantity);
            if ($sourceInv->quantity == 0) $sourceInv->delete();

            $destInv = Inventory::where('location_id', $targetLoc->id)
                                ->where('product_id', $sourceInv->product_id)
                                ->where('lpn', $sourceInv->lpn)
                                ->first();

            if ($destInv) {
                $destInv->increment('quantity', $request->quantity);
            } else {
                Inventory::create([
                    'location_id' => $targetLoc->id,
                    'product_id' => $sourceInv->product_id,
                    'quantity' => $request->quantity,
                    'lpn' => $sourceInv->lpn
                ]);
            }

            if (class_exists(StockMovement::class)) {
                StockMovement::create([
                    'product_id' => $sourceInv->product_id,
                    'from_location_id' => $sourceInv->location_id,
                    'to_location_id' => $targetLoc->id,
                    'quantity' => $request->quantity,
                    'reason' => 'Put-away',
                    'user_id' => Auth::id()
                ]);
            }

            DB::commit();
            return redirect()->route('warehouse.putaway.index')->with('success', "Ubicado en {$targetLoc->code}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    // ==========================================
    // 3. PICKING (RECOLECCIÓN INTELIGENTE)
    // ==========================================

    public function pickingIndex()
    {
        // El operador ve Órdenes listas (Allocated) o que ya empezó (Processing)
        $orders = Order::with('client')
                       ->whereIn('status', ['allocated', 'processing']) 
                       ->orderBy('created_at', 'asc')
                       ->get();

        return view('warehouse.picking.index', compact('orders'));
    }

    public function pickingProcess($id)
    {
        $order = Order::with(['items.product', 'client'])->findOrFail($id);

        if ($order->status === 'allocated') {
            $order->update(['status' => 'processing']);
        }

        // 1. Buscar el siguiente ítem pendiente de pickear (Usando nombre de columna correcto)
        $nextItem = $order->items->where('picked_quantity', '<', 'requested_quantity')->first();

        if (!$nextItem) {
            return view('warehouse.picking.finished', compact('order'));
        }

        // 2. BUSCAR LA ASIGNACIÓN ESPECÍFICA (ALLOCATION)
        $allocation = OrderAllocation::where('order_item_id', $nextItem->id)
                        ->where('quantity', '>', 0) // Que todavía tenga saldo por sacar
                        ->with(['inventory.location', 'inventory.product'])
                        ->first();

        // Si no hay allocation (error de datos), intentamos fallback o error
        if (!$allocation) {
             return back()->with('error', 'Error crítico: Este ítem no tiene ubicación asignada. Contacte al supervisor.');
        }

        $suggestedLoc = $allocation->inventory->location;

        return view('warehouse.picking.process', compact('order', 'nextItem', 'suggestedLoc', 'allocation'));
    }

    public function pickingScanLocation(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
            'suggested_location_id' => 'required',
            'location_code' => 'required'
        ]);

        $targetLoc = Location::find($request->suggested_location_id);

        if (!$targetLoc || strtoupper($request->location_code) !== strtoupper($targetLoc->code)) {
            return back()->with('error', 'Ubicación incorrecta.');
        }

        session()->flash('location_verified', true);
        return back()->with('success', 'Ubicación confirmada.');
    }

    public function pickingScanItem(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
            'item_id' => 'required',
            'allocation_id' => 'required',
            'barcode' => 'required',
            'qty_to_pick' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            // Cargar Allocation
            $allocation = OrderAllocation::findOrFail($request->allocation_id);
            $inventory = Inventory::findOrFail($allocation->inventory_id);
            $orderItem = OrderItem::findOrFail($request->item_id);
            $product = Product::findOrFail($orderItem->product_id);

            // Validar Producto (Barcode)
            if ($product->sku !== $request->barcode && $product->upc !== $request->barcode) {
                session()->flash('location_verified', true); // Mantener el check de ubicación
                return back()->with('error', 'Producto incorrecto.');
            }

            // EJECUTAR MOVIMIENTO REAL
            $qty = $request->qty_to_pick;
            
            // 1. Restar del inventario físico
            if ($inventory->quantity < $qty) {
                throw new \Exception("Discrepancia de Stock: El sistema esperaba $qty pero no están en el bin.");
            }
            
            $inventory->decrement('quantity', $qty);
            if ($inventory->quantity == 0) $inventory->delete();

            // 2. Consumir la Allocation
            $allocation->decrement('quantity', $qty);
            if ($allocation->quantity == 0) $allocation->delete();

            // 3. Sumar al progreso del Picking (Usando nombre de columna correcto)
            $orderItem->increment('picked_quantity', $qty);

            DB::commit();
            return redirect()->route('warehouse.picking.process', $request->order_id)
                             ->with('success', 'Ítem recolectado.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', $e->getMessage());
        }
    }

    public function pickingComplete($id)
    {
        $order = Order::findOrFail($id);
        
        // Validar que todo esté pickeado
        $pending = $order->items->where('picked_quantity', '<', 'requested_quantity')->count();
        if ($pending > 0) return back()->with('error', 'Faltan productos.');

        $order->update(['status' => 'picked']); 

        return redirect()->route('warehouse.picking.index')->with('success', "Orden #{$order->order_number} lista para Packing.");
    }

    // ==========================================
    // 4. PACKING
    // ==========================================

    public function packingIndex()
    {
        $ordersReady = Order::whereIn('status', ['picked', 'processing'])->orderBy('created_at', 'asc')->get();
        return view('warehouse.packing.index', compact('ordersReady'));
    }

    public function packingProcess($orderId)
    {
        $order = Order::with(['items.product', 'client'])->findOrFail($orderId);
        $boxes = PackageType::where('is_active', true)
                    ->where(function($q) use ($order) {
                        $q->whereNull('client_id')->orWhere('client_id', $order->client_id);
                    })->orderBy('length')->get();

        return view('warehouse.packing.process', compact('order', 'boxes'));
    }

    public function packingClose(Request $request, $orderId)
    {
        $request->validate([
            'package_type_id' => 'required|exists:package_types,id',
            'weight' => 'nullable|numeric'
        ]);

        try {
            DB::beginTransaction();
            $order = Order::findOrFail($orderId);
            $box = PackageType::find($request->package_type_id);

            $order->update([
                'status' => 'packed',
                'notes' => $order->notes . "\n[Packing] Caja: {$box->name} | Peso: " . ($request->weight ?? 'N/A') . "kg",
            ]);

            DB::commit();
            return redirect()->route('warehouse.packing.index')->with('success', "Orden #{$order->order_number} empacada.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // ==========================================
    // 5. SHIPPING
    // ==========================================

    public function shippingIndex()
    {
        $orders = Order::with('client')->where('status', 'packed')->orderBy('created_at')->get();
        return view('warehouse.shipping.index', compact('orders'));
    }

    public function shippingManifest(Request $request)
    {
        $request->validate(['order_ids' => 'required|array']);
        try {
            Order::whereIn('id', $request->order_ids)->update(['status' => 'shipped', 'shipped_at' => now()]);
            return back()->with('success', count($request->order_ids) . ' órdenes despachadas.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // ==========================================
    // 6. INVENTARIO & RMA
    // ==========================================

    public function inventoryIndex()
    {
        $totalLocations = Location::count();
        $usedLocations = Location::has('inventory')->count();
        return view('warehouse.inventory.index', compact('totalLocations', 'usedLocations'));
    }

    public function rmaIndex()
    {
        $rmas = RMA::with('client')->where('status', 'approved')->get();
        return view('warehouse.rma.index', compact('rmas'));
    }

    public function rmaProcess($id)
    {
        $rma = RMA::with(['items.product', 'client'])->findOrFail($id);
        return view('warehouse.rma.process', compact('rma'));
    }
}