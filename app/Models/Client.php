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
     */
    protected $fillable = [
        'company_name',
        'tax_id',
        'contact_name',
        'email',
        'phone',
        'address',
        'billing_address',
        'is_active',
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
            if (method_exists(User::class, 'restore')) {
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

    // Requerido por el módulo de Tarifas de Servicio
    public function billingAgreement() {
        return $this->hasOne(ClientBillingAgreement::class, 'client_id');
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

    public function serviceCharges() {
        return $this->hasMany(ServiceCharge::class, 'client_id');
    }

    public function invoices() {
        return $this->hasMany(Invoice::class, 'client_id');
    }

    /**
     * ACCESORES FINANCIEROS (Usados por el módulo Billing)
     */

    public function getPendingBalanceAttribute() {
        return $this->invoices()->where('status', 'unpaid')->sum('total_amount');
    }

    public function getAccumulatedChargesAttribute() {
        // Suma cargos de servicio sin factura vinculada
        return $this->serviceCharges()->whereNull('invoice_id')->sum('amount');
    }
}