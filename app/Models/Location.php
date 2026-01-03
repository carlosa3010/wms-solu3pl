<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;

    /**
     * Atributos asignables masivamente.
     */
    protected $fillable = [
        'warehouse_id', 
        'bin_type_id', // Relación con las dimensiones físicas
        'code',        // Código único de ubicación (Ej: B1-P01-A-R01-N1-B1)
        'type',        // rack, floor, reception, etc.
        'aisle',       // Pasillo
        'rack',        // Columna de Rack
        'shelf'        // Nivel / Altura
    ];

    /**
     * Relación: La ubicación pertenece a una Bodega específica.
     */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * Relación: Define las dimensiones y capacidades de esta ubicación.
     */
    public function binType()
    {
        return $this->belongsTo(BinType::class, 'bin_type_id');
    }

    /**
     * Relación: Stock físico real almacenado actualmente en este bin.
     */
    public function stock()
    {
        return $this->hasMany(Inventory::class, 'location_id');
    }

    /**
     * Relación: Planificación de Salidas (Picking).
     * Permite saber qué productos de pedidos pendientes deben retirarse de aquí.
     */
    public function orderAllocations()
    {
        return $this->hasMany(OrderAllocation::class, 'location_id');
    }

    /**
     * Relación: Planificación de Entradas (Put-away).
     * Permite saber qué productos de ASNs pendientes deben guardarse aquí.
     */
    public function asnAllocations()
    {
        return $this->hasMany(ASNAllocation::class, 'location_id');
    }

    /**
     * Helper: Calcula el stock total en esta ubicación.
     */
    public function getTotalQuantityAttribute()
    {
        return $this->stock()->sum('quantity');
    }

    /**
     * Helper: Determina si la ubicación está vacía.
     */
    public function getIsEmptyAttribute()
    {
        return $this->total_quantity <= 0;
    }
}