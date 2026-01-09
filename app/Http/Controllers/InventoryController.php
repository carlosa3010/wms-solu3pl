<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\Client;
use App\Models\Location;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\LowStockAlert;

class InventoryController extends Controller
{
    /**
     * Muestra el stock actual global con filtros.
     * Ruta: admin.inventory.stock
     */
    public function stock(Request $request)
    {
        // NOTA IMPORTANTE: Para la vista que modificamos (con stock físico, reservado, disponible),
        // es mejor iterar sobre PRODUCTOS, no sobre Inventory (que son líneas de bines).
        // Sin embargo, si quieres mantener la vista detallada por bin, usamos Inventory.
        // Aquí mantengo la lógica original pero optimizada.
        
        $query = Inventory::with(['product.client', 'location.warehouse']);

        // Filtro de búsqueda general (SKU, Nombre, LPN, Ubicación)
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

        // Filtro por Cliente
        if ($request->filled('client_id')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('client_id', $request->client_id);
            });
        }

        // Ordenamiento y paginación
        $inventory = $query->orderBy('updated_at', 'desc')->paginate(20);
        
        // Lista de clientes para el filtro
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();

        // Para la vista "resumida" que te di antes (Físico/Reservado/Disponible), 
        // deberíamos pasar también una colección de productos si esa es la vista principal.
        // Si usas la vista detallada por ubicación, $inventory es correcto.
        
        return view('admin.inventory.stock', compact('inventory', 'clients'));
    }

    /**
     * Muestra el historial de movimientos (Kardex).
     * Ruta: admin.inventory.movements
     */
    public function movements(Request $request)
    {
        $query = StockMovement::with(['product', 'fromLocation', 'toLocation', 'user']);

        // Filtro por SKU
        if ($request->filled('sku')) {
            $query->whereHas('product', function($q) use ($request) {
                $q->where('sku', 'like', "%{$request->sku}%");
            });
        }

        // Filtro por Tipo de Movimiento
        if ($request->filled('type')) {
            $query->where('reason', 'like', "%{$request->type}%");
        }

        $movements = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.inventory.movements', compact('movements'));
    }

    /**
     * Vista para realizar ajustes manuales de inventario.
     * Ruta: admin.inventory.adjustments
     */
    public function adjustments(Request $request)
    {
        $products = Product::orderBy('sku')->get();
        // Limitamos la carga de ubicaciones para no saturar la vista si hay miles
        $locations = Location::with('warehouse')->orderBy('code')->take(1000)->get();

        return view('admin.inventory.adjustments', compact('products', 'locations'));
    }

    /**
     * Procesa el guardado de un ajuste manual.
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
                
                // Buscar o inicializar inventario en esa ubicación
                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $request->product_id, 'location_id' => $location->id],
                    ['quantity' => 0]
                );

                if ($request->type === 'in') {
                    // Entrada
                    $inventory->increment('quantity', $request->quantity);
                    $fromLoc = null;
                    $toLoc = $location->id;
                } else {
                    // Salida
                    if ($inventory->quantity < $request->quantity) {
                        throw new \Exception("Stock insuficiente en la ubicación para realizar la salida.");
                    }
                    $inventory->decrement('quantity', $request->quantity);
                    $fromLoc = $location->id;
                    $toLoc = null;
                }

                // Registrar en Kardex
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

                // Verificar alertas de stock bajo si es una salida
                if ($request->type === 'out') {
                    $product = Product::find($request->product_id);
                    if ($product->min_stock > 0) {
                        $currentTotalStock = Inventory::where('product_id', $product->id)->sum('quantity');
                        
                        if ($currentTotalStock <= $product->min_stock && $product->client && $product->client->email) {
                            try {
                                Mail::to($product->client->email)->send(new LowStockAlert($product, $currentTotalStock));
                            } catch (\Exception $e) {
                                Log::error("Error enviando alerta stock bajo: " . $e->getMessage());
                            }
                        }
                    }
                }
            });

            return redirect()->route('admin.inventory.stock')->with('success', 'Ajuste procesado correctamente.');

        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * AJAX: Obtener bines con stock (Origen para traslados).
     */
    public function getSources(Request $request)
    {
        try {
            $productId = $request->product_id;
            $warehouseId = $request->warehouse_id;

            if (!$productId || !$warehouseId) {
                return response()->json([]);
            }

            $locations = Location::where('warehouse_id', $warehouseId)
                ->whereHas('inventory', function($q) use ($productId) {
                    $q->where('product_id', $productId)->where('quantity', '>', 0);
                })
                ->with(['inventory' => function($q) use ($productId) {
                    $q->where('product_id', $productId);
                }])
                ->get();

            // Formatear respuesta para el select del frontend
            $data = $locations->map(function($loc) {
                return [
                    'id' => $loc->id,
                    'code' => $loc->code,
                    'quantity' => $loc->inventory->first()->quantity ?? 0
                ];
            });

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error("Error en getSources: " . $e->getMessage());
            return response()->json(['error' => 'Error al consultar bines de origen'], 500);
        }
    }

    /**
     * AJAX: Obtener todos los bines de una bodega (Destino para traslados).
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
                ->select('id', 'code')
                ->get();

            return response()->json($locations);

        } catch (\Exception $e) {
            Log::error("Error en getBins: " . $e->getMessage());
            return response()->json(['error' => 'Error al consultar bines de destino'], 500);
        }
    }

    /**
     * AJAX: Búsqueda general de ubicaciones (Select2).
     */
    public function searchLocations(Request $request)
    {
        $term = $request->get('q');
        $locations = Location::where('code', 'LIKE', "%{$term}%")
            ->select('id', 'code')
            ->limit(20)
            ->get();
        return response()->json($locations);
    }
}