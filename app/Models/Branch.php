<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    /**
     * Atributos asignables de forma masiva.
     * Se han incluido los campos geográficos y de inteligencia de cobertura.
     * * @var array
     */
    protected $fillable = [
        'name', 
        'address', 
        'city', 
        'state', 
        'country',        // País de ubicación de la sede
        'zip',            // Código Postal
        'code', 
        'is_active',
        'can_export',     // Permiso para envíos internacionales
        'covered_countries', // Países que esta sede atiende (Array/JSON)
        'covered_states'    // Estados/Provincias que esta sede atiende (Array/JSON)
    ];

    /**
     * Conversión de tipos de atributos.
     * Los campos JSON se convierten automáticamente en arreglos de PHP.
     * * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'can_export' => 'boolean',
        'covered_countries' => 'array', 
        'covered_states' => 'array',
    ];

    /**
     * Relación: Una Sucursal tiene muchas Bodegas (Warehouses).
     */
    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    /**
     * Relación: Una Sucursal tiene muchos Pedidos asignados.
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Scope para filtrar sucursales con capacidad de exportación.
     */
    public function scopeCanExport($query)
    {
        return $query->where('can_export', true);
    }

    /**
     * Scope para filtrar solo sucursales operativas.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}