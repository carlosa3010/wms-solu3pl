<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePlan extends Model
{
    // Añadimos SoftDeletes para evitar errores de integridad con acuerdos históricos
    use SoftDeletes;

    protected $fillable = [
        'name', 'reception_cost_per_box', 'picking_cost_per_order', 
        'additional_item_cost', 'premium_packing_cost', 'return_cost', 
        'storage_billing_type', 'm3_price_monthly'
    ];

    /**
     * Relación con los precios por bines.
     */
    public function binPrices()
    {
        return $this->hasMany(ServicePlanBinPrice::class);
    }

    /**
     * Relación con los acuerdos comerciales.
     * Importante: Eloquent filtrará los acuerdos soft-deleted automáticamente
     * a menos que usemos withTrashed().
     */
    public function agreements()
    {
        return $this->hasMany(ClientBillingAgreement::class);
    }
}