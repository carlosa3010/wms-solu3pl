<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     * Agregamos 'bin_size' y 'levels' para soportar la nueva configuración.
     */
    protected $fillable = [
        'branch_id', 
        'name', 
        'code', 
        'rows', 
        'cols',
        'bin_size', // Nuevo
        'levels'    // Nuevo
    ];

    /**
     * Pertenece a una Sucursal física
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Tiene muchas ubicaciones (Racks/Celdas) generadas
     */
    public function locations()
    {
        return $this->hasMany(Location::class);
    }
    
    /**
     * Acceso directo al inventario en esta bodega
     */
    public function inventory()
    {
        return $this->hasManyThrough(Inventory::class, Location::class);
    }
}