<?php

namespace App\Http\Controllers;

use App\Models\ServicePlan;
use App\Models\BinType;
use App\Models\Client;
use App\Models\ClientBillingAgreement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicePlanController extends Controller
{
    /**
     * Muestra la gestión de tarifas, planes y acuerdos.
     * Se usa la vista 'admin.billing.rates' que es la que integra toda la lógica.
     */
    public function index()
    {
        $plans = ServicePlan::with('binPrices.binType')->get();
        $binTypes = BinType::all();
        $clients = Client::where('is_active', true)->get();
        $agreements = ClientBillingAgreement::with(['client', 'servicePlan'])->get();

        return view('admin.billing.rates', compact('plans', 'binTypes', 'clients', 'agreements'));
    }

    /**
     * Almacena un nuevo plan de servicios y sus precios por bin si aplica.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:150',
            'reception_cost_per_box' => 'required|numeric|min:0',
            'picking_cost_per_order' => 'required|numeric|min:0',
            'additional_item_cost' => 'required|numeric|min:0',
            'premium_packing_cost' => 'required|numeric|min:0',
            'return_cost' => 'required|numeric|min:0',
            'storage_billing_type' => 'required|in:m3,bins',
            'm3_price_monthly' => 'nullable|required_if:storage_billing_type,m3|numeric|min:0',
            'bin_prices' => 'nullable|required_if:storage_billing_type,bins|array'
        ]);

        DB::transaction(function () use ($request, $data) {
            $plan = ServicePlan::create($data);

            if ($request->storage_billing_type === 'bins' && $request->bin_prices) {
                foreach ($request->bin_prices as $binTypeId => $price) {
                    if ($price !== null) {
                        $plan->binPrices()->create([
                            'bin_type_id' => $binTypeId,
                            'price_per_day' => $price
                        ]);
                    }
                }
            }
        });

        return back()->with('success', 'Plan de tarifas creado exitosamente.');
    }

    /**
     * Asigna o actualiza el acuerdo comercial de un cliente.
     */
    public function assignPlan(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_plan_id' => 'required|exists:service_plans,id',
            'agreed_m3_volume' => 'nullable|numeric|min:0',
            'has_premium_packing' => 'nullable'
        ]);

        ClientBillingAgreement::updateOrCreate(
            ['client_id' => $request->client_id],
            [
                'service_plan_id' => $request->service_plan_id,
                'agreed_m3_volume' => $request->agreed_m3_volume ?? 0,
                'has_premium_packing' => $request->has('has_premium_packing'),
                'start_date' => now(),
                'status' => 'active'
            ]
        );

        return back()->with('success', 'Acuerdo comercial actualizado correctamente.');
    }

    /**
     * Elimina un plan si no tiene acuerdos vinculados.
     */
    public function destroy($id)
    {
        $plan = ServicePlan::findOrFail($id);
        
        if ($plan->agreements()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un plan que tiene clientes asignados.');
        }

        $plan->delete();
        return back()->with('success', 'Plan eliminado correctamente.');
    }
}