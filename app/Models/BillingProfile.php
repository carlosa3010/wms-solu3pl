<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingProfile extends Model
{
    use HasFactory;

    // Tabla definida en tu SQL
    protected $table = 'billing_profiles';

    // Desactivamos updated_at si no existe en tu tabla
    public $timestamps = false;

    protected $fillable = [
        'name',
        'currency',
        'storage_fee_per_bin_daily',
        'storage_fee_per_cbm_daily',
        'picking_fee_base',
        'picking_fee_extra',
        'inbound_fee_per_unit',
        'created_at'
    ];

    /**
     * Relación: Un perfil tarifario puede estar en muchos acuerdos con clientes.
     */
    public function agreements()
    {
        return $this->hasMany(ClientBillingAgreement::class, 'billing_profile_id');
    }
}