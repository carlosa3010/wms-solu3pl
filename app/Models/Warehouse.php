<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Recomendado si usas SoftDeletes en migraciones

class Warehouse extends Model
{
    use HasFactory;
    // use SoftDeletes; // Descomenta esto solo si tu tabla 'warehouses' tiene columna 'deleted_at'

    /**
     * Los atributos que se pueden asignar masivamente.
     */
    protected $fillable = [
        'branch_id', 
        'name', 
        'code', 
        'rows', 
        'cols',
        'levels',
        'bin_size',  // Opcional: asegúrate que esta columna exista en tu BD
         // <--- AGREGADO: Útil para el status
    ];

    /**
     * Relación: Pertenece a una Sucursal física
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relación: Tiene muchas ubicaciones (Racks, Celdas y Zonas de carga)
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
    
    /**
     * Relación: Acceso directo al inventario en esta bodega a través de sus ubicaciones.
     * Permite hacer: $warehouse->inventory->sum('quantity')
     */
    public function inventory()
    {
        return $this->hasManyThrough(Inventory::class, Location::class);
    }
}