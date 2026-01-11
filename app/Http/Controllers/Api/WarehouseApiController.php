<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

// Modelos
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Location;
use App\Models\Inventory;
use App\Models\ASN;
use App\Models\ASNItem;
use App\Models\StockMovement;
use App\Models\RMA;
use App\Models\PackageType;

class WarehouseApiController extends Controller
{
    /**
     * Helper para obtener la sucursal del operario actual.
     */
    private function getBranchId()
    {
        // Asumiendo que el user tiene branch_id. Si es admin (null), podrías requerir enviarlo en el header.
        return Auth::user()->branch_id; 
    }

    // ========================================================================
    // 1. DASHBOARD & GLOBAL SCAN
    // ========================================================================

    public function dashboardStats()
    {
        $branchId = $this->getBranchId();

        return response()->json([
            'picking_pending' => Order::where('status', 'picking')->where('branch_id', $branchId)->count(),
            'packing_pending' => Order::where('status', 'packing')->where('branch_id', $branchId)->count(),
            'receiving_pending' => ASN::whereIn('status', ['in_transit', 'receiving'])->where('branch_id', $branchId)->count(),
            'returns_pending' => RMA::where('status', 'approved')->where('branch_id', $branchId)->count(),
        ]);
    }

    public function getProductByBarcode($barcode)
    {
        // Busca por SKU o UPC
        $product = Product::where('sku', $barcode)->orWhere('upc', $barcode)->first();

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado'], 404);
        }

        // Obtener stock actual desglosado por ubicación
        $inventory = Inventory::with('location')
            ->where('product_id', $product->id)
            ->where('quantity', '>', 0)
            ->get()
            ->map(function ($item) {
                return [
                    'location' => $item->location->code,
                    'quantity' => $item->quantity,
                    'batch' => $item->batch_number,
                    'expiry' => $item->expiry_date
                ];
            });

        return response()->json([
            'product' => $product,
            'total_stock' => $inventory->sum('quantity'),
            'locations' => $inventory
        ]);
    }

    public function getLocationContent($code)
    {
        $location = Location::where('code', $code)->first();
        if (!$location) {
            return response()->json(['message' => 'Ubicación no encontrada'], 404);
        }

        $items = Inventory::with('product')
            ->where('location_id', $location->id)
            ->where('quantity', '>', 0)
            ->get();

        return response()->json([
            'location' => $location,
            'items' => $items
        ]);
    }

    // ========================================================================
    // 2. RECEPCIONES (INBOUND / ASN)
    // ========================================================================

    public function getPendingASNs()
    {
        $asns = ASN::with('client')
            ->whereIn('status', ['in_transit', 'receiving'])
            ->where('branch_id', $this->getBranchId())
            ->orderBy('expected_arrival_date', 'asc')
            ->get();

        return response()->json($asns);
    }

    public function getASNDetails($id)
    {
        $asn = ASN::with(['items.product', 'client'])->findOrFail($id);
        return response()->json($asn);
    }

    public function processReceptionScan(Request $request)
    {
        $request->validate([
            'asn_id' => 'required',
            'barcode' => 'required',
            'quantity' => 'required|integer|min:1',
            'location_code' => 'required' // Ubicación de recepción (ej. DOCK-01)
        ]);

        // 1. Validar Ubicación
        $location = Location::where('code', $request->location_code)->firstOrFail();

        // 2. Buscar Item en la ASN
        $product = Product::where('sku', $request->barcode)->orWhere('upc', $request->barcode)->firstOrFail();
        
        $asnItem = ASNItem::where('asn_id', $request->asn_id)
            ->where('product_id', $product->id)
            ->first();

        if (!$asnItem) {
            return response()->json(['message' => 'Este producto no pertenece a esta ASN (Recepcion Ciega no habilitada)'], 400);
        }

        if (($asnItem->received_quantity + $request->quantity) > $asnItem->expected_quantity) {
             return response()->json(['message' => 'Cantidad excede lo esperado en la ASN'], 400);
        }

        DB::transaction(function () use ($asnItem, $request, $location, $product) {
            // Actualizar contador en ASN Item
            $asnItem->increment('received_quantity', $request->quantity);

            // Crear/Actualizar Inventario
            $inventory = Inventory::firstOrCreate(
                [
                    'product_id' => $product->id,
                    'location_id' => $location->id,
                    'batch_number' => $request->batch ?? null
                ],
                ['quantity' => 0]
            );
            $inventory->increment('quantity', $request->quantity);

            // Registrar Movimiento
            StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'quantity' => $request->quantity,
                'type' => 'reception',
                'reference_id' => $request->asn_id,
                'user_id' => Auth::id(),
                'description' => 'Recepción ASN #' . $request->asn_id
            ]);

            // Actualizar estado ASN a 'receiving' si estaba en 'in_transit'
            $asn = ASN::find($request->asn_id);
            if ($asn->status == 'in_transit') {
                $asn->status = 'receiving';
                $asn->save();
            }
        });

        return response()->json(['success' => true, 'message' => 'Item recibido correctamente']);
    }

    public function finalizeReception($id)
    {
        $asn = ASN::findOrFail($id);
        $asn->status = 'completed'; // O 'received'
        $asn->save();
        return response()->json(['success' => true, 'message' => 'Recepción finalizada']);
    }

    // ========================================================================
    // 3. PICKING (OUTBOUND)
    // ========================================================================

    public function getPendingPickingOrders()
    {
        $orders = Order::where('status', 'picking')
            ->where('branch_id', $this->getBranchId())
            ->withCount('items')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($orders);
    }

    public function getPickingOrderDetails($id)
    {
        // Traemos la orden con sus items y la ubicación SUGERIDA (donde hay stock)
        // Esto es una simplificación. En un WMS real, hay lógica de asignación (Allocation).
        $order = Order::with(['items.product', 'client'])->findOrFail($id);

        // Agregamos info de dónde buscar el producto
        foreach ($order->items as $item) {
            $stockLocation = Inventory::where('product_id', $item->product_id)
                ->where('quantity', '>', 0)
                ->orderBy('quantity', 'desc') // Sugerir ubicación con más stock
                ->with('location')
                ->first();
            
            $item->suggested_location = $stockLocation ? $stockLocation->location->code : 'SIN STOCK';
            $item->picked_qty = 0; // Para control visual en la app (debería venir de una tabla intermedia real)
        }

        return response()->json($order);
    }

    public function processPickingScan(Request $request)
    {
        $request->validate([
            'order_id' => 'required',
            'barcode' => 'required',
            'quantity' => 'required|integer|min:1',
            'location_code' => 'required' // De donde lo sacó el operario
        ]);

        $location = Location::where('code', $request->location_code)->firstOrFail();
        $product = Product::where('sku', $request->barcode)->orWhere('upc', $request->barcode)->firstOrFail();
        
        // Verificar que el item esté en la orden
        $orderItem = OrderItem::where('order_id', $request->order_id)
            ->where('product_id', $product->id)
            ->first();

        if (!$orderItem) {
            return response()->json(['message' => 'Producto no corresponde a esta orden'], 400);
        }

        // Verificar Stock Físico
        $inventory = Inventory::where('product_id', $product->id)
            ->where('location_id', $location->id)
            ->first();

        if (!$inventory || $inventory->quantity < $request->quantity) {
            return response()->json(['message' => 'Stock insuficiente en esa ubicación'], 400);
        }

        DB::transaction(function () use ($inventory, $request, $product, $location) {
            // Descontar Inventario
            $inventory->decrement('quantity', $request->quantity);

            // Registrar Movimiento
            StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'quantity' => -$request->quantity, // Negativo por salida
                'type' => 'picking',
                'reference_id' => $request->order_id,
                'user_id' => Auth::id(),
                'description' => 'Picking Orden #' . $request->order_id
            ]);

            // Aquí se debería actualizar una tabla 'order_pick_progress' si quieres tracking en tiempo real
        });

        return response()->json(['success' => true, 'message' => 'Item pickeado']);
    }

    public function finalizePicking($id)
    {
        $order = Order::findOrFail($id);
        
        // Validar que todo se haya pickeado (simplificado)
        // ... Logica de validación ...

        $order->status = 'packing';
        $order->save();

        return response()->json(['success' => true, 'message' => 'Picking finalizado. Orden enviada a Packing.']);
    }

    // ========================================================================
    // 4. INVENTARIO (MOVIMIENTOS Y CONSULTAS)
    // ========================================================================

    public function moveInventory(Request $request)
    {
        $request->validate([
            'product_sku' => 'required',
            'from_location' => 'required',
            'to_location' => 'required',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::where('sku', $request->product_sku)->firstOrFail();
        $fromLoc = Location::where('code', $request->from_location)->firstOrFail();
        $toLoc = Location::where('code', $request->to_location)->firstOrFail();

        $sourceInv = Inventory::where('product_id', $product->id)
            ->where('location_id', $fromLoc->id)
            ->first();

        if (!$sourceInv || $sourceInv->quantity < $request->quantity) {
            return response()->json(['message' => 'No hay suficiente stock en origen'], 400);
        }

        DB::transaction(function () use ($sourceInv, $toLoc, $request, $product, $fromLoc) {
            // Restar origen
            $sourceInv->decrement('quantity', $request->quantity);

            // Sumar destino
            $destInv = Inventory::firstOrCreate(
                ['product_id' => $product->id, 'location_id' => $toLoc->id],
                ['quantity' => 0]
            );
            $destInv->increment('quantity', $request->quantity);

            // Log
            StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $fromLoc->id, // Referencia origen
                'quantity' => 0, // Es un movimiento neutro globalmente, pero interno
                'type' => 'transfer_internal',
                'user_id' => Auth::id(),
                'description' => "Traslado PDA de {$fromLoc->code} a {$toLoc->code}"
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Movimiento realizado']);
    }

    public function adjustStock(Request $request)
    {
        // Para "Conteo Cíclico" o ajustes rápidos
        $request->validate([
            'location_code' => 'required',
            'product_sku' => 'required',
            'real_quantity' => 'required|integer|min:0',
            'reason' => 'required'
        ]);

        $location = Location::where('code', $request->location_code)->firstOrFail();
        $product = Product::where('sku', $request->product_sku)->firstOrFail();

        $inventory = Inventory::firstOrCreate(
            ['product_id' => $product->id, 'location_id' => $location->id],
            ['quantity' => 0]
        );

        $diff = $request->real_quantity - $inventory->quantity;

        if ($diff == 0) return response()->json(['message' => 'Sin cambios']);

        DB::transaction(function () use ($inventory, $diff, $request, $product, $location) {
            $inventory->quantity = $request->real_quantity;
            $inventory->save();

            StockMovement::create([
                'product_id' => $product->id,
                'location_id' => $location->id,
                'quantity' => $diff,
                'type' => 'adjustment',
                'user_id' => Auth::id(),
                'description' => "Ajuste PDA: " . $request->reason
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Inventario ajustado']);
    }
}