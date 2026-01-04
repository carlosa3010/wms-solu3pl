<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BillingProfile extends Model
{
    use HasFactory;

    /**
     * Atributos asignables de forma masiva.
     * Se añaden premium_packing_fee y rma_handling_fee para soportar servicios extra.
     */
    protected $fillable = [
        'name',
        'currency',
        'storage_fee_per_bin_daily',
        'picking_fee_base',
        'inbound_fee_per_unit',
        'premium_packing_fee', // Tarifa por empaque especial
        'rma_handling_fee'     // Tarifa por procesamiento de devolución
    ];

    /**
     * Relación: Un perfil tarifario puede estar en muchos acuerdos con clientes.
     */
    public function agreements()
    {
        return $this->hasMany(ClientBillingAgreement::class, 'billing_profile_id');
    }
}