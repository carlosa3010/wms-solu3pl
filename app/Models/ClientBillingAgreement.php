<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClientBillingAgreement extends Model
{
    use HasFactory;

    /**
     * Atributos asignables de forma masiva.
     */
    protected $fillable = [
        'client_id',
        'billing_profile_id',
        'start_date',
        'is_active'
    ];

    /**
     * Conversión de tipos.
     */
    protected $casts = [
        'start_date' => 'date',
        'is_active' => 'boolean'
    ];

    /**
     * Relación: El acuerdo pertenece a un cliente.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación: El acuerdo utiliza un perfil tarifario específico.
     */
    public function billingProfile()
    {
        return $this->belongsTo(BillingProfile::class, 'billing_profile_id');
    }
}