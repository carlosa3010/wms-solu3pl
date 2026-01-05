<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServicePlanBinPrice extends Model
{
    protected $fillable = ['service_plan_id', 'bin_type_id', 'price_per_day'];

    public function binType()
    {
        return $this->belongsTo(BinType::class);
    }
}