<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientBillingAgreement extends Model
{
    // Tabla que vincula Clientes con Perfiles de Cobro
    protected $table = 'client_billing_agreements';

    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'billing_profile_id',
        'start_date',
        'end_date'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Relación: El acuerdo pertenece a un Cliente.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación: El acuerdo utiliza un Perfil Tarifario específico.
     */
    public function profile()
    {
        return $this->belongsTo(BillingProfile::class, 'billing_profile_id');
    }
}