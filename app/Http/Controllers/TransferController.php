<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TransferController extends Controller
{
    /**
     * Muestra el historial de traslados internos registrados en el sistema.
     */
    public function index()
    {
        // Obtenemos los movimientos que tienen origen y destino (traslados)
        // Se cargan las relaciones de infraestructura para mostrar nombres de bodegas y sucursales
        $transfers = StockMovement::with(['product', 'fromLocation.warehouse', 'toLocation.warehouse', 'user'])
            ->whereNotNull('from_location_id')
            ->whereNotNull('to_location_id')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.operations.transfers.index', compact('transfers'));
    }

    /**
     * Muestra el formulario para crear una nueva orden de traslado multiproducto.
     */
    public function create()
    {
        // Cargamos sedes con sus bodegas para los selectores de cabecera
        $branches = Branch::with('warehouses')->where('is_active', true)->get();
        
        // Cargamos todos los productos para el catálogo de selección en las filas dinámicas
        $products = Product::orderBy('sku')->get();

        return view('admin.operations.transfers.create', compact('branches', 'products'));
    }

    /**
     * Procesa la orden de traslado. 
     * Implementa una transacción DB para asegurar que se muevan todos los SKUs o ninguno.
     */
    public function store(Request $request)
    {
        $request->validate([
            'src_warehouse_id' => 'required|exists:warehouses,id',
            'dest_warehouse_id' => 'required|exists:warehouses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.from_location_id' => 'required|exists:locations,id',
            'items.*.to_location_id' => 'required|exists:locations,id',
            'items.*.quantity' => 'required|integer|min:1',
            'reason' => 'nullable|string'
        ]);

        // Generamos un código de referencia único para agrupar los movimientos de esta orden
        $reference = 'TRF-' . strtoupper(bin2hex(random_bytes(3)));

        try {
            DB::transaction(function () use ($request, $reference) {
                foreach ($request->items as $item) {
                    
                    // 1. VALIDACIÓN DE DISPONIBILIDAD
                    // Buscamos el registro de inventario en el bin de origen exacto
                    $source = Inventory::where('product_id', $item['product_id'])
                        ->where('location_id', $item['from_location_id'])
                        ->first();

                    if (!$source || $source->quantity < $item['quantity']) {
                        $prod = Product::find($item['product_id']);
                        throw new \Exception("Stock insuficiente para SKU: {$prod->sku} en la ubicación de origen.");
                    }

                    // 2. ACTUALIZACIÓN DE STOCK (Lógica WMS)
                    
                    // Descontar de Origen
                    $source->decrement('quantity', $item['quantity']);

                    // Aumentar o Crear en Destino
                    $dest = Inventory::firstOrCreate(
                        ['product_id' => $item['product_id'], 'location_id' => $item['to_location_id']],
                        ['quantity' => 0]
                    );
                    $dest->increment('quantity', $item['quantity']);

                    // 3. REGISTRO EN KARDEX (Stock Movements)
                    // Registramos cada línea bajo la misma referencia para trazabilidad consolidada
                    StockMovement::create([
                        'product_id' => $item['product_id'],
                        'from_location_id' => $item['from_location_id'],
                        'to_location_id' => $item['to_location_id'],
                        'quantity' => $item['quantity'],
                        'reason' => $request->reason ?? 'Traslado Interno Multiproducto',
                        'reference_number' => $reference,
                        'user_id' => Auth::id(),
                        'created_at' => now()
                    ]);
                }
            });

            return redirect()->route('admin.transfers.index')
                ->with('success', "Orden de traslado {$reference} procesada exitosamente. El stock ha sido reubicado.");

        } catch (\Exception $e) {
            // Si algo falla, Laravel hace Rollback automático de la transacción
            return back()->withInput()->withErrors(['error' => 'Error al procesar traslado: ' . $e->getMessage()]);
        }
    }

    /**
     * Muestra la vista del Manifiesto de Carga / Guía de Transferencia Interna.
     */
    public function printManifest($reference)
    {
        // Recuperamos todos los movimientos asociados a la referencia
        $movements = StockMovement::with([
                'product', 
                'fromLocation.warehouse.branch', 
                'toLocation.warehouse.branch', 
                'user'
            ])
            ->where('reference_number', $reference)
            ->get();
            
        if ($movements->isEmpty()) {
            abort(404, 'La referencia de traslado solicitada no existe.');
        }

        return view('admin.operations.transfers.manifest', compact('movements', 'reference'));
    }
}