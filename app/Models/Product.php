<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atributos asignables de forma masiva.
     */
    protected $fillable = [
        'client_id',
        'category_id',
        'sku',
        'name',
        'description',
        'barcode',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'image_path',
        'min_stock_level',
        'requires_serial_number',
        // 'is_active', // COMENTADO: No existe en la base de datos actual para evitar Error 500
    ];

    protected $casts = [
        'requires_serial_number' => 'boolean',
        'weight_kg' => 'float',
        'length_cm' => 'float',
        'width_cm'  => 'float',
        'height_cm' => 'float',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación con el Inventario Físico.
     * Fundamental para saber en qué bins está el producto.
     */
    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Relación con los ítems de órdenes.
     * Vital para calcular el stock comprometido (reservado).
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // ==========================================
    // CÁLCULOS DE STOCK (LÓGICA WMS)
    // ==========================================

    /**
     * STOCK FÍSICO: 
     * Suma total de lo que existe en la tabla de inventario (estantes),
     * excluyendo ubicaciones bloqueadas (como Cuarentena o Dañados).
     */
    public function getPhysicalStockAttribute()
    {
        return $this->inventory()
            ->whereHas('location', function ($q) {
                $q->where('is_blocked', false);
            })
            ->sum('quantity') ?? 0;
    }

    /**
     * STOCK COMPROMETIDO (RESERVADO):
     * Suma de productos en órdenes activas (Pending/Allocated/Processing)
     * que aún NO han sido recolectados físicamente.
     */
    public function getCommittedStockAttribute()
    {
        // 1. Obtener ítems de este producto en órdenes vivas
        $items = $this->orderItems()
            ->whereHas('order', function ($q) {
                // Estados donde el stock está "prometido" pero no despachado
                $q->whereIn('status', ['pending', 'allocated', 'processing', 'backorder']);
            })
            ->get();

        // 2. Calcular cuánto falta por pickear
        // Fórmula: Solicitado (requested) - Ya Recolectado (picked)
        return $items->sum(function ($item) {
            $requested = $item->requested_quantity ?? 0;
            $picked = $item->picked_quantity ?? 0;
            
            return max(0, $requested - $picked);
        });
    }

    /**
     * STOCK DISPONIBLE:
     * Lo que realmente podemos vender o trasladar hoy.
     * Fórmula: Físico - Comprometido.
     */
    public function getAvailableStockAttribute()
    {
        $physical = $this->physical_stock;
        $committed = $this->committed_stock;

        return max(0, $physical - $committed);
    }

    // ==========================================
    // HELPERS Y ACCESSORS
    // ==========================================

    /**
     * Volumen en cm3 (Útil para algoritmos de empaque)
     */
    public function getVolumeAttribute()
    {
        return $this->length_cm * $this->width_cm * $this->height_cm;
    }

    /**
     * Alias para compatibilidad con vistas anteriores.
     */
    public function getTotalStockAttribute()
    {
        return $this->physical_stock;
    }
}