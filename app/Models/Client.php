<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * IMPORTANTE: 'company_name' y 'contact_name' deben estar aquí 
     * para evitar el error "Field doesn't have a default value".
     * Se agrega 'name' para compatibilidad con el nuevo sistema de facturación.
     */
    protected $fillable = [
        'name',           // Nuevo campo estándar
        'company_name',
        'tax_id',
        'contact_name',
        'email',
        'phone',
        'address',
        'billing_address',
        'city',           // Agregado si se usa en BillingController
        'state',          // Agregado si se usa en BillingController
        'country',        // Agregado si se usa en BillingController
        'zip_code',       // Agregado si se usa en BillingController
        'is_active',
        'user_id'         // Relación con el usuario del sistema
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Lógica Automática (Booted)
     * Maneja el borrado en cascada de los usuarios vinculados.
     */
    protected static function booted()
    {
        static::deleted(function ($client) {
            // Borramos los usuarios para que pierdan acceso al portal inmediatamente
            $client->users()->delete();
        });

        static::restored(function ($client) {
            // Restauramos usuarios si es necesario
            if (class_exists(User::class) && method_exists(User::class, 'restore')) {
                $client->users()->withTrashed()->restore();
            }
        });
    }

    /**
     * RELACIONES (Vitales para todos los módulos del sistema)
     */

    public function users() {
        return $this->hasMany(User::class, 'client_id');
    }

    // Requerido por el módulo de Tarifas de Servicio (Nuevo)
    public function billingAgreement() {
        return $this->hasOne(ClientBillingAgreement::class, 'client_id');
    }
    
    // Relación directa con el Plan de Servicio a través del Acuerdo
    public function servicePlan()
    {
        return $this->hasOneThrough(ServicePlan::class, ClientBillingAgreement::class, 'client_id', 'id', 'id', 'service_plan_id');
    }

    // Billetera Virtual (Nuevo)
    public function wallet()
    {
        return $this->hasOne(Wallet::class, 'client_id');
    }

    // Pre-Facturas (Nuevo)
    public function preInvoices()
    {
        return $this->hasMany(PreInvoice::class, 'client_id');
    }
    
    // Cargos de Servicio (Nuevo)
    public function serviceCharges()
    {
        return $this->hasMany(ServiceCharge::class, 'client_id');
    }

    public function products() {
        return $this->hasMany(Product::class, 'client_id');
    }

    public function asns() {
        return $this->hasMany(ASN::class, 'client_id');
    }

    public function orders() {
        return $this->hasMany(Order::class, 'client_id');
    }

    public function rmas() {
        return $this->hasMany(RMA::class, 'client_id');
    }

    public function invoices() {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    /**
     * ACCESORES FINANCIEROS (Usados por el módulo Billing)
     */

    public function getPendingBalanceAttribute() {
        // Suma de facturas emitidas pero no pagadas (estado 'sent' o 'overdue')
        // Ajusta los estados según tu lógica de Invoice
        return $this->invoices()->whereIn('status', ['sent', 'overdue'])->sum('total');
    }

    public function getAccumulatedChargesAttribute() {
        // Suma cargos de servicio que aún no están en una factura final (is_invoiced = false)
        return $this->serviceCharges()->where('is_invoiced', false)->sum('amount');
    }
}