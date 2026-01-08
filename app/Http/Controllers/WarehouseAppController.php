<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Order;
use App\Models\PackageType;
use App\Models\ASN;
use App\Models\ASNItem;
use App\Models\RMA;
use App\Models\Product;
use App\Models\Location;
use App\Models\Inventory;

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
        // ASNs pendientes, enviadas o parciales
        $asns = ASN::whereIn('status', ['sent', 'partial', 'pending', 'draft', 'in_process'])
                    ->orderBy('expected_arrival_date', 'asc')
                    ->get();
        return view('warehouse.reception.index', compact('asns'));
    }

    public function receptionShow($id)
    {
        $asn = ASN::with(['client', 'items.product'])->findOrFail($id);
        
        // Calcular progreso
        $totalExpected = $asn->items->sum('expected_quantity');
        $totalReceived = $asn->items->sum('received_quantity');
        $progress = $totalExpected > 0 ? ($totalReceived / $totalExpected) * 100 : 0;

        return view('warehouse.reception.show', compact('asn', 'progress', 'totalReceived', 'totalExpected'));
    }

    /**
     * PASO 1: Validación de Bultos (Check-in)
     */
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

    /**
     * PASO 2: Procesa el escaneo de un producto (Normal, Serializado o Dañado)
     */
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

            // 1. Identificar Producto
            $product = Product::where('sku', $request->barcode)->first();
            if (!$product) return back()->with('error', 'Producto no encontrado en el maestro.');

            // 2. Validar que pertenece a la ASN
            $asnItem = ASNItem::where('asn_id', $request->asn_id)
                              ->where('product_id', $product->id)
                              ->first();

            if (!$asnItem) return back()->with('error', "El SKU {$product->sku} no pertenece a esta ASN.");

            // --- LÓGICA DE SERIALES ---
            if ($product->requires_serial_number && empty($request->serial_number)) {
                return back()->with('ask_serial', [
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'barcode' => $request->barcode
                ]);
            }

            if ($request->serial_number) {
                // Validar duplicados globales
                if (Inventory::where('product_id', $product->id)->where('lpn', $request->serial_number)->exists()) {
                    return back()->with('error', "El serial {$request->serial_number} ya existe.");
                }
            }
            // ---------------------------

            // 3. Validar cantidades (Opcional: permitir exceso con warning)
            if ($asnItem->received_quantity >= $asnItem->expected_quantity) {
                // return back()->with('warning', '¡Atención! Cantidad esperada ya completada.');
            }

            // 4. Actualizar conteo ASN
            $asnItem->increment('received_quantity');
            $asnItem->update(['status' => 'received']);

            // 5. DETERMINAR UBICACIÓN (Recepción vs Cuarentena)
            $warehouseId = Auth::user()->warehouse_id ?? 8; // Fallback
            $locationCode = $request->is_damaged ? 'CUARENTENA' : 'RECEPCION';
            $locationType = $request->is_damaged ? 'quarantine' : 'staging';

            $location = Location::firstOrCreate(
                ['code' => $locationCode, 'warehouse_id' => $warehouseId],
                ['type' => $locationType, 'status' => 'active', 'is_blocked' => $request->is_damaged]
            );

            // 6. CREAR INVENTARIO
            if ($request->serial_number) {
                // Producto Serializado: Línea única con LPN
                Inventory::create([
                    'product_id' => $product->id,
                    'location_id' => $location->id,
                    'quantity' => 1,
                    'lpn' => $request->serial_number 
                ]);
            } else {
                // Producto Normal: Agrupar cantidad
                $inventory = Inventory::where('product_id', $product->id)
                                      ->where('location_id', $location->id)
                                      ->whereNull('lpn') // No mezclar con los que tienen serial
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
            
            // 7. Actualizar Estado Global ASN
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
            return back()->with('error', 'Error crítico: ' . $e->getMessage());
        }
    }

    /**
     * DESHACER ESCANEO: Restar 1 unidad
     */
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
                // 1. Restar de la ASN
                $asnItem->decrement('received_quantity');

                // 2. Restar del Inventario (Busca en RECEPCION por defecto)
                $warehouseId = Auth::user()->warehouse_id ?? 8;
                $location = Location::where('code', 'RECEPCION')->where('warehouse_id', $warehouseId)->first();

                if ($location) {
                    // Intenta borrar primero uno sin serial (FIFO simple)
                    $inventory = Inventory::where('product_id', $request->product_id)
                                          ->where('location_id', $location->id)
                                          ->whereNull('lpn')
                                          ->first();
                    
                    // Si no hay sin serial, busca cualquiera (esto es riesgoso con seriales, idealmente se pide escanear qué serial borrar)
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
            return back()->with('error', 'No se pudo corregir: ' . $e->getMessage());
        }
    }

    /**
     * CIERRE FORZOSO
     */
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

    /**
     * Genera la vista de etiqueta
     */
    public function printProductLabel($id)
    {
        $product = Product::findOrFail($id);
        return view('warehouse.reception.print_label', compact('product'));
    }

    // ==========================================
    // 2. PICKING
    // ==========================================

    public function pickingIndex()
    {
        $orders = Order::where('status', 'pending')
                       ->orWhere('status', 'processing')
                       ->orderBy('created_at')
                       ->get();
        return view('warehouse.picking.index', compact('orders'));
    }

    public function pickingProcess($id)
    {
        $order = Order::with(['items.product', 'client'])->findOrFail($id);
        if ($order->status === 'pending') {
            $order->update(['status' => 'processing']);
        }
        return view('warehouse.picking.process', compact('order'));
    }

    // ==========================================
    // 3. PACKING
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
    // 4. SHIPPING
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
    // 5. INVENTARIO & RMA
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