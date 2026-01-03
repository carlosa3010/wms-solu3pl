<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Hash;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atributos asignables de forma masiva.
     */
    protected $fillable = [
        'company_name',
        'tax_id',
        'contact_name',
        'email',
        // 'password' removido de aquí ya que la columna no existe en la tabla 'clients'
        'phone',
        'address',
        'is_active',
        'logo_url',
        'billing_type'
    ];

    /**
     * Conversión de tipos.
     */
    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * --- MÉTODOS DE ESTADO ---
     */

    /**
     * Verifica si el cliente está activo.
     */
    public function isActive()
    {
        return $this->is_active;
    }

    /**
     * Suspende al cliente.
     */
    public function suspend()
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Reactiva al cliente.
     */
    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    /**
     * --- SEGURIDAD Y ACCESO ---
     */

    /**
     * Resetea la contraseña.
     * La contraseña se actualiza únicamente en los registros de la tabla 'users'
     * vinculados a este cliente, ya que la tabla 'clients' no maneja credenciales.
     */
    public function resetPassword($newPassword = 'password123')
    {
        // 1. No intentamos actualizar la tabla 'clients' porque no tiene columna 'password'
        
        // 2. Actualizar todos los usuarios vinculados (Acceso al Portal)
        // Recorremos los usuarios para asegurar que los Mutators del modelo User
        // se ejecuten (encriptación) y se guarde el cambio correctamente.
        foreach ($this->users as $user) {
            $user->password = $user->hasAttribute('password') ? $newPassword : Hash::make($newPassword);
            
            // Si el modelo User tiene un mutator para 'password', simplemente asignamos:
            $user->password = $newPassword; 
            $user->save();
        }
    }

    /**
     * --- RELACIONES ---
     */

    /**
     * Usuarios con acceso al portal vinculados a este cliente.
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Acuerdo de facturación vigente.
     */
    public function billingAgreement()
    {
        return $this->hasOne(ClientBillingAgreement::class, 'client_id');
    }

    /**
     * Historial de facturas.
     */
    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Cargos por servicios pendientes de facturar.
     */
    public function serviceCharges()
    {
        return $this->hasMany(ServiceCharge::class);
    }

    /**
     * Relaciones Operativas WMS.
     */
    public function products() { return $this->hasMany(Product::class); }
    public function orders()   { return $this->hasMany(Order::class); }
    public function asns()     { return $this->hasMany(ASN::class); }

    /**
     * --- ATRIBUTOS DINÁMICOS (ACCESSORS) ---
     */

    /**
     * Deuda Total Pendiente (Facturada)
     */
    public function getPendingBalanceAttribute()
    {
        return $this->invoices()->where('status', 'unpaid')->sum('total_amount');
    }

    /**
     * Monto Acumulado para la siguiente factura (No facturado aún)
     */
    public function getAccumulatedChargesAttribute()
    {
        return $this->serviceCharges()->where('is_invoiced', false)->sum('amount');
    }

    /**
     * Nombre amigable para mostrar.
     */
    public function getDisplayNameAttribute()
    {
        return "{$this->company_name} ({$this->tax_id})";
    }

    /**
     * --- SCOPES ---
     */

    /**
     * Filtrar solo clientes activos.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}