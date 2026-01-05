<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServicePlan extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'reception_cost_per_box',
        'picking_cost_per_order',
        'additional_item_cost',
        'premium_packing_cost',
        'return_cost',
        'storage_billing_type', // 'm3' o 'bins'
        'm3_price_monthly'
    ];

    public function binPrices()
    {
        return $this->hasMany(ServicePlanBinPrice::class);
    }
}