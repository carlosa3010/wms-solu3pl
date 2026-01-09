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
        // Optimización: Usamos Product como base para el reporte consolidado
        $query = Product::with(['client', 'inventory.location', 'orderItems.order'])
                        ->whereHas('inventory'); // Solo productos con algún movimiento histórico o stock

        // Filtro de búsqueda general
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('sku', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhereHas('inventory', function($iq) use ($search) {
                      $iq->where('lpn', 'like', "%{$search}%")
                        ->orWhereHas('location', function($lq) use ($search) {
                            $lq->where('code', 'like', "%{$search}%");
                        });
                  });
            });
        }

        // Filtro por Cliente
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Paginación
        $inventory = $query->orderBy('name')->paginate(20);
        
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();

        return view('admin.inventory.stock', compact('inventory', 'clients'));
    }

    /**
     * Muestra el historial de movimientos (Kardex).
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
     * Vista para realizar ajustes manuales de inventario.
     * Ruta: admin.inventory.adjustments
     */
    public function adjustments(Request $request)
    {
        $products = Product::orderBy('sku')->get();
        // Limitamos para rendimiento, idealmente usar Select2 con AJAX
        $locations = Location::with('warehouse')->where('is_blocked', false)->orderBy('code')->take(500)->get();

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
                        throw new \Exception("Stock insuficiente en la ubicación {$location->code}.");
                    }
                    $inventory->decrement('quantity', $request->quantity);
                    $fromLoc = $location->id;
                    $toLoc = null;
                }

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

                // Alerta Stock Bajo
                if ($request->type === 'out') {
                    $product = Product::find($request->product_id);
                    if ($product->min_stock_level > 0) {
                        $currentTotal = Inventory::where('product_id', $product->id)->sum('quantity');
                        
                        if ($currentTotal <= $product->min_stock_level && $product->client && $product->client->email) {
                            try {
                                Mail::to($product->client->email)->send(new LowStockAlert($product, $currentTotal));
                            } catch (\Exception $e) {
                                Log::error("Error enviando alerta stock: " . $e->getMessage());
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
     * AJAX CRÍTICO: Obtener bines con stock (Origen para traslados).
     * Soluciona el problema de que no se mostraban ubicaciones en el select.
     */
    public function getSources(Request $request)
    {
        try {
            $productId = $request->product_id;
            
            if (!$productId) {
                return response()->json([]);
            }

            // Buscar ubicaciones con stock > 0 del producto
            $locations = Location::whereHas('inventory', function($q) use ($productId) {
                    $q->where('product_id', $productId)
                      ->where('quantity', '>', 0);
                })
                ->where('is_blocked', false)
                ->with(['inventory' => function($q) use ($productId) {
                    $q->where('product_id', $productId);
                }])
                ->get();

            $data = $locations->map(function($loc) {
                $qty = $loc->inventory->first()->quantity ?? 0;
                return [
                    'id' => $loc->id,
                    'code' => $loc->code,
                    'quantity' => $qty,
                    'text' => $loc->code . ' (Disp: ' . $qty . ')' // Formato Select2
                ];
            });

            return response()->json($data);

        } catch (\Exception $e) {
            Log::error("Error getSources: " . $e->getMessage());
            return response()->json(['error' => 'Error consultando stock'], 500);
        }
    }

    /**
     * AJAX: Obtener todos los bines destino.
     */
    public function getBins(Request $request)
    {
        try {
            // Si no se envía warehouse, usamos default
            $warehouseId = $request->warehouse_id; 
            
            $query = Location::where('is_blocked', false)->orderBy('code');

            if ($warehouseId) {
                $query->where('warehouse_id', $warehouseId);
            }
            
            // Excluir origen si se envía
            if($request->has('exclude_id')) {
                $query->where('id', '!=', $request->exclude_id);
            }

            return response()->json($query->select('id', 'code')->get());

        } catch (\Exception $e) {
            Log::error("Error getBins: " . $e->getMessage());
            return response()->json([], 500);
        }
    }

    /**
     * AJAX: Búsqueda general de ubicaciones (Select2).
     */
    public function searchLocations(Request $request)
    {
        $term = $request->get('q');
        $locations = Location::where('code', 'LIKE', "%{$term}%")
            ->where('is_blocked', false)
            ->select('id', 'code')
            ->limit(20)
            ->get();
        return response()->json($locations);
    }
}