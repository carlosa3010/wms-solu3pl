<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Location extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atributos asignables masivamente.
     */
    protected $fillable = [
        'warehouse_id', // CRÍTICO: Permite vincular la ubicación a la bodega
        'code',
        'aisle',
        'side',         // <--- AGREGADO: Necesario para diferenciar Lado A/B en el código
        'rack',
        'shelf',
        'position',
        'level',
        'type',         // CRÍTICO: Necesario para 'staging'
        'status',
        'bin_type_id',
        'is_blocked',   // CRÍTICO: Para bloquear ubicaciones en mantenimiento
        
    ];

    /**
     * Boot function para lógica automática al guardar.
     * Genera la nomenclatura: Sucursal-Bodega-Pasillo-[Lado]-Rack-Nivel-Bin
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($location) {
            // 1. Si es una zona especial (RECEPCION/DESPACHO) no sobreescribimos el código
            if ($location->type === 'staging') {
                return;
            }

            // 2. Generación automática solo si tenemos coordenadas físicas
            if ($location->aisle && $location->rack && $location->level && $location->position) {
                
                // Cargar relaciones para obtener prefijos (Sucursal y Bodega)
                if (!$location->relationLoaded('warehouse')) {
                    $location->load('warehouse.branch');
                }

                $branchCode = $location->warehouse->branch->code ?? 'GEN';
                $whCode = $location->warehouse->code ?? 'WH';
                
                // Construcción: CAB-B01-P01-[A]-R01-L01-B01
                $parts = [
                    $branchCode,
                    $whCode,
                    $location->aisle, // Ej: P01
                ];

                if (!empty($location->side)) {
                    $parts[] = $location->side; // Ej: A
                }

                $parts[] = $location->rack;     // Ej: R01
                $parts[] = $location->level;    // Ej: L01
                $parts[] = $location->position; // Ej: B01

                // Unir con guiones
                $location->code = implode('-', $parts);
            }
        });
    }

    /* -------------------------------------------------------------------------- */
    /* RELACIONES                                                                 */
    /* -------------------------------------------------------------------------- */

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function binType()
    {
        return $this->belongsTo(BinType::class, 'bin_type_id');
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class, 'location_id');
    }

    // Alias para compatibilidad con código legacy
    public function stock()
    {
        return $this->inventory(); 
    }

    public function orderAllocations()
    {
        return $this->hasMany(OrderAllocation::class, 'location_id');
    }

    public function asnAllocations()
    {
        return $this->hasMany(ASNAllocation::class, 'location_id');
    }

    /* -------------------------------------------------------------------------- */
    /* HELPERS                                                                    */
    /* -------------------------------------------------------------------------- */

    public function getTotalQuantityAttribute()
    {
        return $this->stock()->sum('quantity');
    }

    public function getIsEmptyAttribute()
    {
        return $this->total_quantity <= 0;
    }

    public function getDimensionsAttribute()
    {
        return $this->binType ? [
            'width' => $this->binType->width,
            'height' => $this->binType->height,
            'depth' => $this->binType->depth,
            'weight_capacity' => $this->binType->weight_capacity
        ] : null;
    }
}