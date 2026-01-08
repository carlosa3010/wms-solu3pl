<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\BinAllocator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PickingController extends Controller
{
    protected $allocator;

    public function __construct(BinAllocator $allocator)
    {
        $this->allocator = $allocator;
    }

    /**
     * Dashboard de Picking.
     * Muestra órdenes pendientes de asignar.
     */
    public function index(Request $request)
    {
        $query = Order::with(['client', 'items'])
            ->whereIn('status', ['pending', 'backorder']) // Solo lo que falta procesar
            ->orderBy('created_at', 'asc');

        if ($request->has('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $pendingOrders = $query->paginate(20);

        return view('admin.operations.picking.index', compact('pendingOrders'));
    }

    /**
     * Asignación Individual (Single Picking)
     */
    public function allocateSingle($id)
    {
        try {
            DB::beginTransaction();
            
            $order = Order::with('items')->findOrFail($id);
            $this->allocator->allocateOrder($order);
            
            DB::commit();

            return back()->with('success', "Orden #{$order->order_number} procesada. Estado: {$order->status}");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error al asignar: ' . $e->getMessage());
        }
    }

    /**
     * Asignación por Ola (Wave Picking)
     * Recibe un array de IDs de órdenes seleccionadas.
     */
    public function createWave(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id'
        ]);

        try {
            DB::beginTransaction();

            $processedCount = 0;
            $orders = Order::with('items')->whereIn('id', $request->order_ids)->get();

            foreach ($orders as $order) {
                if ($this->allocator->allocateOrder($order)) {
                    $processedCount++;
                }
            }

            DB::commit();

            return back()->with('success', "Ola procesada exitosamente. {$processedCount} órdenes asignadas.");

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error en la Ola de Picking: ' . $e->getMessage());
        }
    }
}