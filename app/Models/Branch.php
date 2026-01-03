<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    /**
     * Atributos asignables de forma masiva.
     * Incluye campos para la inteligencia geográfica y operativa.
     * * @var array
     */
    protected $fillable = [
        'name', 
        'address', 
        'city', 
        'state', 
        'code', 
        'is_active',
        'can_export',     // Define si la sede puede realizar envíos internacionales
        'covered_states'  // Almacena los estados de Venezuela que esta sede atiende
    ];

    /**
     * Conversión de tipos de atributos.
     * * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'can_export' => 'boolean',
        'covered_states' => 'array', // Crucial para manejar el JSON como un array de PHP
    ];

    /**
     * Relación: Una Sucursal tiene muchas Bodegas (Warehouses).
     * Ej: La Sucursal "Occidente" tiene "Bodega A" y "Bodega B".
     */
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    /**
     * Relación: Una Sucursal tiene muchos Pedidos asignados.
     * Estos pedidos son asignados automáticamente según el estado de destino.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope para filtrar solo sucursales que tengan capacidad de exportación.
     */
    public function scopeCanExport($query)
    {
        return $query->where('can_export', true);
    }

    /**
     * Scope para filtrar sucursales activas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}