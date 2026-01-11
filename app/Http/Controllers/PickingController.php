<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\BinAllocator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

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
        $user = Auth::user();
        
        // Solo buscamos órdenes que requieren acción inmediata (pending o backorder)
        // EXCLUIMOS: 'waiting_transfer' (porque el stock no ha llegado)
        $query = Order::with(['client', 'items.product', 'branch'])
            ->whereIn('status', ['pending', 'backorder']) 
            ->orderBy('created_at', 'asc'); // FIFO

        // Filtro: Si es operario de almacén (limitado a su sucursal)
        if ($user->branch_id) {
            $query->where('branch_id', $user->branch_id);
        }

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
     */
    public function allocateSingle($id)
    {
        try {
            $order = Order::findOrFail($id);
            $user = Auth::user();

            // Validar permiso de sucursal
            if ($user->branch_id && $order->branch_id !== $user->branch_id) {
                return back()->with('error', 'No tienes permiso para procesar órdenes de otra sucursal.');
            }

            // Validar estado (No permitir picking si espera traslado)
            if ($order->status === 'waiting_transfer') {
                return back()->with('warning', 'Esta orden espera stock de otra sucursal. Debe recibir el traslado primero.');
            }

            if (!in_array($order->status, ['pending', 'backorder'])) {
                return back()->with('error', 'La orden ya fue procesada o no está pendiente.');
            }

            DB::beginTransaction();
            
            // Llamamos al Servicio de Inteligencia (BinAllocator)
            // Este servicio validará el stock LOCAL de la sucursal de la orden
            $result = $this->allocator->allocateOrder($order);
            
            DB::commit();

            if ($result) {
                return back()->with('success', "Orden #{$order->order_number} asignada correctamente. Stock reservado.");
            } else {
                return back()->with('warning', "Orden #{$order->order_number} no pudo completarse. Stock insuficiente en esta bodega.");
            }

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en Picking Allocate Single: " . $e->getMessage());
            return back()->with('error', 'Error crítico al asignar: ' . $e->getMessage());
        }
    }

    /**
     * Asignación por Ola (Wave Picking).
     */
    public function createWave(Request $request)
    {
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'exists:orders,id'
        ]);

        try {
            $user = Auth::user();
            $processedCount = 0;
            $failedCount = 0;
            
            // Cargamos las órdenes
            $query = Order::whereIn('id', $request->order_ids)
                          ->whereIn('status', ['pending', 'backorder']); // Excluir waiting_transfer

            // Filtro de seguridad por sucursal
            if ($user->branch_id) {
                $query->where('branch_id', $user->branch_id);
            }

            $orders = $query->get();

            if ($orders->isEmpty()) {
                return back()->with('error', 'Ninguna de las órdenes seleccionadas es válida para Picking en tu sucursal.');
            }

            DB::beginTransaction();

            foreach ($orders as $order) {
                try {
                    if ($this->allocator->allocateOrder($order)) {
                        $processedCount++;
                    } else {
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
                $msg .= " {$failedCount} órdenes no pudieron asignarse por falta de stock local.";
                return back()->with('warning', $msg);
            }

            return back()->with('success', $msg);

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error general al crear la Ola: ' . $e->getMessage());
        }
    }
}