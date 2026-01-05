<?php

namespace App\Http\Controllers;

use App\Models\ServicePlan;
use App\Models\BinType;
use App\Models\Client;
use App\Models\ClientBillingAgreement;
use Illuminate\Http\Request;

class ServicePlanController extends Controller
{
    public function index()
    {
        $plans = ServicePlan::all();
        $binTypes = BinType::all();
        return view('admin.billing.plans.index', compact('plans', 'binTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'reception_cost_per_box' => 'required|numeric',
            'picking_cost_per_order' => 'required|numeric',
            'additional_item_cost' => 'required|numeric',
            'premium_packing_cost' => 'required|numeric',
            'return_cost' => 'required|numeric',
            'storage_billing_type' => 'required|in:m3,bins',
            'm3_price_monthly' => 'nullable|required_if:storage_billing_type,m3|numeric',
            'bin_prices' => 'nullable|required_if:storage_billing_type,bins|array'
        ]);

        $plan = ServicePlan::create($data);

        if ($request->storage_billing_type === 'bins' && $request->bin_prices) {
            foreach ($request->bin_prices as $binTypeId => $price) {
                $plan->binPrices()->create([
                    'bin_type_id' => $binTypeId,
                    'price_per_day' => $price
                ]);
            }
        }

        return back()->with('success', 'Plan creado correctamente.');
    }

    public function assignPlan(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_plan_id' => 'required|exists:service_plans,id',
            'agreed_m3_volume' => 'nullable|numeric',
            'has_premium_packing' => 'nullable'
        ]);

        ClientBillingAgreement::updateOrCreate(
            ['client_id' => $request->client_id],
            [
                'service_plan_id' => $request->service_plan_id,
                'agreed_m3_volume' => $request->agreed_m3_volume ?? 0,
                'has_premium_packing' => $request->has('has_premium_packing'),
                'start_date' => now()
            ]
        );

        return back()->with('success', 'Acuerdo actualizado.');
    }
    
    // ... update, destroy methods
}