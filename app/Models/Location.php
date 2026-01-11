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
        'warehouse_id', // <--- IMPORTANTE: Faltaba este vínculo
        'code',
        'aisle',
        'rack',
        'shelf',
        'position',
        'level',        // Asegúrate de tener este si usas niveles
        'type',         // <--- Necesario para 'staging' (recepcion/despacho)
        'status',
        'bin_type_id',
        'is_blocked',   // <--- Necesario para la lógica de creación
        'description'
    ];

    /**
     * Boot function para lógica automática al guardar.
     * Genera la nomenclatura automáticamente: Sucursal-Bodega-Pasillo-Lado-Rack-Nivel-Bin
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($location) {
            // Solo regenera si tenemos los componentes jerárquicos completos
            if ($location->aisle && $location->rack && $location->level && $location->position) {
                
                // Cargar relaciones si no están cargadas para obtener códigos padres
                if (!$location->relationLoaded('warehouse')) {
                    $location->load('warehouse.branch');
                }

                $branchCode = $location->warehouse->branch->code ?? 'GEN';
                $whCode = $location->warehouse->code ?? 'WH';
                
                // Lógica de construcción del código
                $parts = [
                    $branchCode,
                    $whCode,
                    $location->aisle,
                ];

                if ($location->side) {
                    $parts[] = $location->side;
                }

                $parts[] = $location->rack;
                $parts[] = $location->level;
                $parts[] = $location->position;

                // Unir con guiones
                $location->code = implode('-', $parts);
            }
        });
    }

    /* -------------------------------------------------------------------------- */
    /* RELACIONES                                 */
    /* -------------------------------------------------------------------------- */

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
     * (Mantenemos 'inventory' como estándar de Laravel y 'stock' como alias por compatibilidad)
     */
    public function inventory()
    {
        return $this->hasMany(Inventory::class, 'location_id');
    }

    public function stock()
    {
        return $this->inventory(); // Alias para compatibilidad con tu código existente
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

    /* -------------------------------------------------------------------------- */
    /* HELPERS                                   */
    /* -------------------------------------------------------------------------- */

    /**
     * Helper: Calcula el stock total en esta ubicación.
     */
    public function getTotalQuantityAttribute()
    {
        // Usamos stock() o inventory() indistintamente
        return $this->stock()->sum('quantity');
    }

    /**
     * Helper: Determina si la ubicación está vacía.
     */
    public function getIsEmptyAttribute()
    {
        return $this->total_quantity <= 0;
    }

    /**
     * Helper: Obtiene dimensiones para el Mapa 3D/2D
     */
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