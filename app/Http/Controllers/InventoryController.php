<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// Importaciones críticas de modelos con namespaces correctos
use App\Models\Inventory; 
use App\Models\Product;
use App\Models\Client;
use App\Models\Location;
use App\Models\StockMovement;
// Soporte de Laravel
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class InventoryController extends Controller
{
    /**
     * Muestra el stock actual global con filtros de búsqueda y cliente.
     * Ruta: admin.inventory.stock
     */
    public function index(Request $request)
    {
        // Cargamos relaciones para evitar el problema N+1 y mejorar el rendimiento
        $query = Inventory::with(['product.client', 'location.warehouse']);

        // Filtro por búsqueda (SKU, Nombre Producto, LPN o Código de Ubicación)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('product', function($pq) use ($search) {
                    $pq->where('sku', 'like', "%{$search}%")
                       ->orWhere('name', 'like', "%{$search}%");
                })
                ->orWhere('lpn', 'like', "%{$search}%")
                ->orWhereHas('location', function($lq) use ($search) {
                    $lq->where('code', 'like', "%{$search}%");
                });
            });
        }

        // Filtro por Cliente (Propietario de la mercancía)
        if ($request->filled('client_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('client_id', $request->client_id);
            });
        }

        // Ordenamos por la actualización más reciente (según estructura SQL subida)
        $inventory = $query->orderBy('updated_at', 'desc')->paginate(20);
        
        // Obtenemos clientes activos para el filtro
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();

        return view('admin.inventory.stock', compact('inventory', 'clients'));
    }

    /**
     * Muestra el Kardex (Historial de Movimientos) del sistema.
     * Ruta: admin.inventory.movements
     */
    public function movements(Request $request)
    {
        $query = StockMovement::with(['product', 'fromLocation', 'toLocation', 'user']);

        if ($request->filled('sku')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('sku', 'like', "%{$request->sku}%");
            });
        }

        if ($request->filled('type')) {
            $query->where('reason', 'like', "%{$request->type}%");
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.inventory.movements', compact('movements'));
    }

    /**
     * Formulario para realizar ajustes manuales de stock.
     * Ruta: admin.inventory.adjustments
     */
    public function adjustments(Request $request)
    {
        // Se cargan todos los productos (la tabla no tiene is_active según SQL)
        $products = Product::orderBy('sku')->get();
        $locations = Location::with('warehouse')->orderBy('code')->get();

        return view('admin.inventory.adjustments', compact('products', 'locations'));
    }

    /**
     * Procesa y registra un ajuste manual en el inventario y el Kardex.
     */
    public function storeAdjustment(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'location_code' => 'required|exists:locations,code',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:255'
        ]);

        try {
            DB::transaction(function () use ($request) {
                $location = Location::where('code', $request->location_code)->first();
                
                // Buscar o crear registro de inventario en esa posición
                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $request->product_id, 'location_id' => $location->id],
                    ['quantity' => 0]
                );

                if ($request->type === 'in') {
                    $inventory->increment('quantity', $request->quantity);
                    $fromLoc = null;
                    $toLoc = $location->id;
                } else {
                    if ($inventory->quantity < $request->quantity) {
                        throw new \Exception("Stock insuficiente en la ubicación para realizar la salida por ajuste.");
                    }
                    $inventory->decrement('quantity', $request->quantity);
                    $fromLoc = $location->id;
                    $toLoc = null;
                }

                // Registro histórico en Kardex (stock_movements)
                StockMovement::create([
                    'product_id' => $request->product_id,
                    'from_location_id' => $fromLoc,
                    'to_location_id' => $toLoc,
                    'quantity' => $request->quantity,
                    'reason' => 'Ajuste Manual: ' . $request->reason,
                    'reference_number' => 'ADJ-' . time(),
                    'user_id' => Auth::id(),
                    'created_at' => now()
                ]);
            });

            return redirect()->route('admin.inventory.stock')->with('success', 'Ajuste de inventario procesado correctamente.');

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Retorna bines de una bodega que contienen un SKU específico con stock.
     * Vital para la funcionalidad de bines de origen en Traslados.
     */
    public function getSources(Request $request)
    {
        try {
            $productId = $request->product_id;
            $warehouseId = $request->warehouse_id;

            if (!$productId || !$warehouseId) {
                return response()->json([]);
            }

            // Consultamos ubicaciones que tengan stock del producto solicitado (> 0)
            $locations = Location::where('warehouse_id', $warehouseId)
                ->whereHas('stock', function($q) use ($productId) {
                    $q->where('product_id', $productId)->where('quantity', '>', 0);
                })
                ->with(['stock' => function($q) use ($productId) {
                    $q->where('product_id', $productId);
                }])
                ->get();

            return response()->json($locations);

        } catch (\Exception $e) {
            Log::error("Error en getSources: " . $e->getMessage());
            return response()->json(['error' => 'Error al consultar bines de origen'], 500);
        }
    }

    /**
     * AJAX: Retorna todos los bines de una bodega específica.
     * Vital para la funcionalidad de bines de destino en Traslados.
     */
    public function getBins(Request $request)
    {
        try {
            $warehouseId = $request->warehouse_id;

            if (!$warehouseId) {
                return response()->json([]);
            }
            
            $locations = Location::where('warehouse_id', $warehouseId)
                ->orderBy('code')
                ->get(['id', 'code']);

            return response()->json($locations);

        } catch (\Exception $e) {
            Log::error("Error en getBins: " . $e->getMessage());
            return response()->json(['error' => 'Error al consultar bines de destino'], 500);
        }
    }
}