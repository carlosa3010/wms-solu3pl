<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClientBillingAgreement extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'service_plan_id',     // Vinculación al nuevo sistema de planes
        'billing_profile_id',  // Mantenemos por compatibilidad legacy si es necesario
        'start_date',
        'end_date',
        'status',              // active, inactive
        'agreed_m3_volume',    // Volumen m3 contratado (si el plan es por m3)
        'has_premium_packing', // Si contrató servicio de empaque premium
        'payment_term_days',   // Días de crédito
        'notes'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'has_premium_packing' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación con el Nuevo Plan de Servicios
     */
    public function servicePlan()
    {
        return $this->belongsTo(ServicePlan::class);
    }

    /**
     * Relación Legacy (Perfil antiguo)
     */
    public function billingProfile()
    {
        return $this->belongsTo(BillingProfile::class);
    }
}