<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sku;
use App\Models\Stock;
use App\Models\Asn;
use App\Models\Order;
use App\Models\Rma;
use App\Models\Billing;
use Illuminate\Support\Facades\Auth;

class ClientPortalController extends Controller
{
    /**
     * Dashboard: Resumen financiero y operativo filtrado por cliente.
     */
    public function dashboard()
    {
        $clientId = auth()->id();

        $data = [
            'skusCount' => Sku::where('client_id', $clientId)->count(),
            'pendingRmas' => Rma::where('client_id', $clientId)->where('status', 'Pendiente')->count(),
            'activeAsns' => Asn::where('client_id', $clientId)->whereIn('status', ['Creado', 'En Tránsito'])->count(),
            // Cálculo del corte de cuenta: solo registros de este cliente en el ciclo actual
            'corteCuenta' => Billing::where('client_id', $clientId)->where('cycle_status', 'open')->sum('amount'),
        ];

        return view('client.portal', $data);
    }

    /**
     * Catálogo: Solo SKUs creados por o para este cliente.
     */
    public function catalog()
    {
        $skus = Sku::where('client_id', auth()->id())->latest()->get();
        return view('client.catalog', compact('skus'));
    }

    /**
     * Stock Actual: Filtra la tabla global de stock basándose en la relación con sus SKUs.
     */
    public function stock()
    {
        $clientId = auth()->id();

        // Esta es la parte clave: segmentar el stock global
        $stocks = Stock::whereHas('sku', function ($query) use ($clientId) {
            $query->where('client_id', $clientId);
        })->with(['sku', 'branch', 'warehouse'])->get();

        return view('client.stock', compact('stocks'));
    }

    /**
     * ASN: Crear avisos de arribo vinculando solo sus propios productos.
     */
    public function asnIndex()
    {
        $asns = Asn::where('client_id', auth()->id())->latest()->get();
        $mySkus = Sku::where('client_id', auth()->id())->where('status', 'Activo')->get();
        
        return view('client.asn', compact('asns', 'mySkus'));
    }

    public function storeAsn(Request $request)
    {
        $request->validate([
            'carrier' => 'required',
            'estimated_arrival' => 'required|date',
            'items' => 'required|array',
        ]);

        $asn = Asn::create([
            'client_id' => auth()->id(),
            'carrier' => $request->carrier,
            'tracking_number' => $request->tracking,
            'estimated_arrival' => $request->estimated_arrival,
            'status' => 'Creado'
        ]);

        // Lógica para guardar los items del ASN (si tienes tabla pivot)
        // ...

        return back()->with('success', 'ASN Notificado con éxito');
    }

    /**
     * Pedidos (Orders): Crear despachos manuales.
     */
    public function createOrder()
    {
        $mySkus = Sku::where('client_id', auth()->id())->where('status', 'Activo')->get();
        return view('client.orders_create', compact('mySkus'));
    }

    /**
     * RMA: Gestión de devoluciones segmentada.
     */
    public function rmaIndex()
    {
        $rmas = Rma::where('client_id', auth()->id())->with('sku')->latest()->get();
        return view('client.rma', compact('rmas'));
    }

    public function updateRmaStatus(Request $request, $id)
    {
        // El failOrFail asegura que el RMA pertenezca al cliente
        $rma = Rma::where('client_id', auth()->id())->findOrFail($id);
        
        $rma->update([
            'status' => $request->status, // Autorizado / Rechazado
            'client_notes' => $request->notes
        ]);

        return back()->with('success', 'RMA actualizado correctamente');
    }

    /**
     * Finanzas: Descarga de prefacturas.
     */
    public function downloadPreInvoice()
    {
        // Lógica de generación de PDF filtrando por client_id
        // ...
    }
}