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
     * Muestra la gestión de tarifas y acuerdos.
     * Retorna la vista directamente para evitar bucles de redirección.
     */
    public function index()
    {
        // 1. Obtener planes con sus precios de bines cargados (Eager Loading)
        $plans = ServicePlan::with('binPrices.binType')->get();
        
        // 2. Obtener tipos de bines para los modales
        $binTypes = BinType::all();
        
        // 3. Obtener clientes activos (usando company_name para el ordenamiento)
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        
        // 4. Obtener acuerdos comerciales vigentes con relaciones cargadas
        $agreements = ClientBillingAgreement::with(['client', 'servicePlan'])->get();

        return view('admin.billing.rates', compact('plans', 'binTypes', 'clients', 'agreements'));
    }

    /**
     * Guarda un nuevo plan de tarifas o actualiza uno existente.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'plan_id' => 'nullable|exists:service_plans,id',
            'name' => 'required|string|max:255',
            'reception_cost_per_box' => 'required|numeric|min:0',
            'picking_cost_per_order' => 'required|numeric|min:0',
            'additional_item_cost' => 'required|numeric|min:0',
            'premium_packing_cost' => 'required|numeric|min:0',
            'return_cost' => 'required|numeric|min:0',
            'storage_billing_type' => 'required|in:m3,bins',
            'm3_price_monthly' => 'nullable|required_if:storage_billing_type,m3|numeric',
            'bin_prices' => 'nullable|array'
        ]);

        DB::transaction(function () use ($request, $data) {
            // updateOrCreate para permitir edición desde el mismo modal
            $plan = ServicePlan::updateOrCreate(
                ['id' => $request->plan_id],
                $data
            );

            // Si el esquema es bines, actualizamos los precios específicos
            if ($request->storage_billing_type === 'bins' && $request->has('bin_prices')) {
                // Sincronizamos precios de bines (eliminamos anteriores y creamos nuevos)
                $plan->binPrices()->delete();
                foreach ($request->bin_prices as $binTypeId => $price) {
                    if ($price !== null && $price > 0) {
                        $plan->binPrices()->create([
                            'bin_type_id' => $binTypeId,
                            'price_per_day' => $price
                        ]);
                    }
                }
            } else {
                // Si cambió a m3, nos aseguramos de limpiar los precios de bines
                $plan->binPrices()->delete();
            }
        });

        $message = $request->plan_id ? 'Plan actualizado correctamente.' : 'Plan de tarifas creado correctamente.';
        return back()->with('success', $message);
    }

    /**
     * Asigna un plan a un cliente (Acuerdo comercial).
     * Ajustado: M3 es null si el plan es por bines.
     */
    public function assignPlan(Request $request)
    {
        $request->validate([
            'client_id' => 'required|exists:clients,id',
            'service_plan_id' => 'required|exists:service_plans,id',
            'agreed_m3_volume' => 'nullable|numeric|min:0',
            'has_premium_packing' => 'nullable'
        ]);

        $plan = ServicePlan::findOrFail($request->service_plan_id);

        // Lógica de volumen contratado solicitada:
        // Si el plan es m3, usamos el valor enviado (o 0 si está vacío).
        // Si el plan es por bines, forzamos el valor a null en la base de datos.
        $m3Volume = ($plan->storage_billing_type === 'm3') 
            ? ($request->agreed_m3_volume ?? 0) 
            : null;

        ClientBillingAgreement::updateOrCreate(
            ['client_id' => $request->client_id],
            [
                'service_plan_id' => $request->service_plan_id,
                'agreed_m3_volume' => $m3Volume,
                'has_premium_packing' => $request->has('has_premium_packing'),
                'start_date' => now(),
                'status' => 'active'
            ]
        );

        return back()->with('success', 'Acuerdo comercial guardado correctamente.');
    }

    /**
     * Elimina un acuerdo comercial (Revoca el plan del cliente).
     */
    public function destroyAgreement($id)
    {
        $agreement = ClientBillingAgreement::findOrFail($id);
        $agreement->delete();

        return back()->with('success', 'Acuerdo comercial eliminado.');
    }

    /**
     * Elimina un plan de tarifas completo.
     * Con SoftDeletes activo en la base de datos (según el Canvas), esto no romperá la integridad.
     */
    public function destroyPlan($id)
    {
        $plan = ServicePlan::findOrFail($id);
        
        // Seguridad: No borrar planes que tengan acuerdos activos (no borrados lógicamente)
        if ($plan->agreements()->count() > 0) {
            return back()->with('error', 'No se puede eliminar un plan que tiene clientes activos vinculados.');
        }

        $plan->delete();
        return back()->with('success', 'Plan de tarifas eliminado con éxito.');
    }
}