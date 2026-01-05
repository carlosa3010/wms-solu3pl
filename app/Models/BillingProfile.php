<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @deprecated Use ServicePlan instead for new logic.
 * Mantenido para compatibilidad con registros históricos.
 */
class BillingProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'picking_fee_base',
        'picking_fee_extra_item',
        'packing_fee_premium',
        'returns_processing_fee',
        'storage_fee_per_cbm_day', // Legacy
        'currency'
    ];

    public function agreements()
    {
        return $this->hasMany(ClientBillingAgreement::class);
    }
}