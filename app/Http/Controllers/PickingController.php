<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\BinAllocator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PickingController extends Controller
{
    protected $allocator;

    public function __construct(BinAllocator $allocator)
    {
        $this->allocator = $allocator;
    }

    /**
     * Dashboard de Picking (Admin).
     * Muestra todas las órdenes que están en 'pending' esperando asignación de stock.
     */
    public function index(Request $request)
    {
        // Solo buscamos órdenes que requieren acción (pending o backorder)
        $query = Order::with(['client', 'items.product', 'branch'])
            ->whereIn('status', ['pending', 'backorder']) 
            ->orderBy('created_at', 'asc'); // FIFO: Los pedidos más viejos primero

        // Filtro por Cliente
        if ($request->has('client_id') && !empty($request->client_id)) {
            $query->where('client_id', $request->client_id);
        }

        // Filtro por Número de Orden
        if ($request->filled('search')) {
            $query->where('order_number', 'like', '%' . $request->search . '%');
        }

        $pendingOrders = $query->paginate(20);

        return view('admin.operations.picking.index', compact('pendingOrders'));
    }

    /**
     * Asignación Individual (Single Picking).
     * Ejecuta la lógica de "Hard Allocation": Busca bines y reserva stock físico.
     */
    public function allocateSingle($id)
    {
        try {
            $order = Order::findOrFail($id);

            // Validar estado antes de procesar
            if (!in_array($order->status, ['pending', 'backorder'])) {
                return back()->with('error', 'La orden ya fue procesada o no está pendiente.');
            }

            DB::beginTransaction();
            
            // Llamamos al Servicio de Inteligencia (BinAllocator)
            // Este servicio debe:
            // 1. Buscar inventario FIFO.
            // 2. Crear registros en 'order_allocations'.
            // 3. Actualizar estado a 'allocated'.
            $result = $this->allocator->allocateOrder($order);
            
            DB::commit();

            if ($result) {
                return back()->with('success', "Orden #{$order->order_number} asignada correctamente. Lista para el operador.");
            } else {
                return back()->with('warning', "Orden #{$order->order_number} procesada parcialmente (Backorder) o sin stock suficiente.");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en Picking Allocate Single: " . $e->getMessage());
            return back()->with('error', 'Error crítico al asignar: ' . $e->getMessage());
        }
    }

    /**
     * Asignación por Ola (Wave Picking).
     * Procesa múltiples órdenes en lote para eficiencia.
     */
    public function createWave(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id'
        ]);

        try {
            $processedCount = 0;
            $failedCount = 0;
            
            // Cargamos las órdenes seleccionadas
            $orders = Order::whereIn('id', $request->order_ids)
                           ->whereIn('status', ['pending', 'backorder']) // Doble check de seguridad
                           ->get();

            if ($orders->isEmpty()) {
                return back()->with('error', 'Ninguna de las órdenes seleccionadas es válida para Picking.');
            }

            DB::beginTransaction();

            foreach ($orders as $order) {
                try {
                    if ($this->allocator->allocateOrder($order)) {
                        $processedCount++;
                    } else {
                        // Si el allocator devuelve false, es porque faltó stock (Backorder)
                        $failedCount++;
                    }
                } catch (\Exception $innerEx) {
                    Log::error("Error asignando orden {$order->order_number} en ola: " . $innerEx->getMessage());
                    $failedCount++;
                }
            }

            DB::commit();

            $msg = "Ola completada. {$processedCount} órdenes asignadas exitosamente.";
            if ($failedCount > 0) {
                $msg .= " {$failedCount} órdenes quedaron en espera por falta de stock.";
                return back()->with('warning', $msg);
            }

            return back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error general al crear la Ola: ' . $e->getMessage());
        }
    }
}