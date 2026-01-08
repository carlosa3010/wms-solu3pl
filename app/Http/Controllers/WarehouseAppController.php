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

class WarehouseAppController extends Controller
{
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
        // Lógica simple: buscar producto o ubicación
        $product = Product::with('inventory.location')->where('sku', $q)->first();
        if($product) return view('warehouse.lookup.product', compact('product'));

        $location = Location::where('code', $q)->first();
        if($location) return view('warehouse.lookup.location', compact('location'));

        $order = Order::where('order_number', $q)->first();
        if($order) return redirect()->route('warehouse.picking.process', $order->id);

        return back()->with('error', 'Código no encontrado: ' . $q);
    }

    // ==========================================
    // 1. RECEPCIÓN (INBOUND)
    // ==========================================
    
    public function receptionIndex()
    {
        // ASNs que están esperando llegada o parcialmente recibidas
        $asns = ASN::whereIn('status', ['sent', 'partial', 'pending'])->orderBy('expected_arrival_date')->get();
        return view('warehouse.reception.index', compact('asns'));
    }

    /**
     * Procesa el escaneo de un producto dentro de una ASN
     */
    public function receptionScan(Request $request)
    {
        $request->validate([
            'asn_id' => 'required|exists:asns,id',
            'barcode' => 'required|string'
        ]);

        try {
            DB::beginTransaction();

            // 1. Buscar el producto por SKU (Barcode)
            $product = Product::where('sku', $request->barcode)->first();

            if (!$product) {
                return back()->with('error', 'Producto no encontrado en el maestro.');
            }

            // 2. Buscar si este producto pertenece a la ASN
            $asnItem = ASNItem::where('asn_id', $request->asn_id)
                              ->where('product_id', $product->id)
                              ->first();

            if (!$asnItem) {
                return back()->with('error', 'Este producto NO pertenece a esta ASN (Ciego).');
            }

            // 3. Validar cantidades (Opcional: permitir sobre-recibo con advertencia)
            if ($asnItem->received_quantity >= $asnItem->expected_quantity) {
                return back()->with('warning', '¡Atención! Ya se ha recibido la cantidad esperada de este ítem.');
            }

            // 4. Actualizar conteo
            $asnItem->increment('received_quantity');
            
            // 5. Actualizar estado de la ASN a "partial" si estaba "pending"
            $asn = ASN::find($request->asn_id);
            if ($asn->status === 'pending') {
                $asn->update(['status' => 'partial']);
            }

            DB::commit();

            return back()->with('success', "Recibido: {$product->name} (Total: {$asnItem->received_quantity})");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al procesar: ' . $e->getMessage());
        }
    }

    // ==========================================
    // 2. PICKING (RECOLECCIÓN)
    // ==========================================

    public function pickingIndex()
    {
        // Órdenes listas para picking
        $orders = Order::where('status', 'pending')
                       ->orWhere('status', 'processing')
                       ->orderBy('created_at')
                       ->get();
        return view('warehouse.picking.index', compact('orders'));
    }

    public function pickingProcess($id)
    {
        $order = Order::with(['items.product', 'client'])->findOrFail($id);
        
        // Si es la primera vez que se abre, cambiamos estado a "picking" (en proceso)
        if ($order->status === 'pending') {
            $order->update(['status' => 'processing']);
        }

        return view('warehouse.picking.process', compact('order'));
    }

    // ==========================================
    // 3. PACKING (EMPAQUE)
    // ==========================================

    public function packingIndex()
    {
        // Órdenes que ya pasaron picking y necesitan empaque
        // Asumiendo que al terminar picking quedan en 'picked' o 'processing'
        $ordersReady = Order::whereIn('status', ['picked', 'processing']) 
                            ->orderBy('created_at', 'asc')
                            ->get();

        return view('warehouse.packing.index', compact('ordersReady'));
    }

    public function packingProcess($orderId)
    {
        $order = Order::with(['items.product', 'client'])->findOrFail($orderId);
        
        // Cargar cajas sugeridas (Globales + Cliente)
        $boxes = PackageType::where('is_active', true)
                    ->where(function($q) use ($order) {
                        $q->whereNull('client_id')
                          ->orWhere('client_id', $order->client_id);
                    })
                    ->orderBy('length')
                    ->get();

        return view('warehouse.packing.process', compact('order', 'boxes'));
    }

    /**
     * Cierra la orden, asigna caja y genera "etiqueta" (simulada)
     */
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

            // Actualizamos la orden con los datos finales del paquete
            // Nota: Asumiendo que tienes campos json o notas para guardar detalles del paquete si no hay tabla orders_packages
            $order->update([
                'status' => 'packed',
                'notes' => $order->notes . "\n[Packing] Caja: {$box->name} | Peso: " . ($request->weight ?? 'N/A') . "kg",
                // Si tuvieras campos de tracking o shipping_cost, aquí se podrían pre-calcular
            ]);

            DB::commit();

            return redirect()->route('warehouse.packing.index')
                ->with('success', "Orden #{$order->order_number} empacada y lista para despacho.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al cerrar empaque: ' . $e->getMessage());
        }
    }

    // ==========================================
    // 4. SHIPPING (DESPACHO)
    // ==========================================

    public function shippingIndex()
    {
        // Órdenes 'packed' listas para salir
        $orders = Order::with('client')
                       ->where('status', 'packed')
                       ->orderBy('created_at')
                       ->get();
                       
        return view('warehouse.shipping.index', compact('orders'));
    }

    /**
     * Marca las órdenes como enviadas (Shipped)
     */
    public function shippingManifest(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'exists:orders,id'
        ]);

        try {
            // Actualizar masivamente a 'shipped'
            Order::whereIn('id', $request->order_ids)->update([
                'status' => 'shipped',
                'shipped_at' => now()
            ]);

            return back()->with('success', count($request->order_ids) . ' órdenes marcadas como ENVIADAS. Manifiesto generado.');

        } catch (\Exception $e) {
            return back()->with('error', 'Error al generar despacho: ' . $e->getMessage());
        }
    }

    // ==========================================
    // 5. INVENTARIO & 6. RMA
    // ==========================================

    public function inventoryIndex()
    {
        // Resumen simple de ocupación por ahora
        $totalLocations = Location::count();
        $usedLocations = Location::has('inventory')->count();
        
        return view('warehouse.inventory.index', compact('totalLocations', 'usedLocations'));
    }

    public function rmaIndex()
    {
        // Solo las aprobadas ('approved') llegan físicamente a bodega para inspección final o reingreso
        $rmas = RMA::with('client')->where('status', 'approved')->get();
        return view('warehouse.rma.index', compact('rmas'));
    }

    public function rmaProcess($id)
    {
        $rma = RMA::with(['items.product', 'client'])->findOrFail($id);
        return view('warehouse.rma.process', compact('rma'));
    }
}