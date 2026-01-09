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
        'requires_serial_number', // Vital para el módulo de recepción
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'requires_serial_number' => 'boolean',
        'weight_kg' => 'float',
        'length_cm' => 'float',
        'width_cm'  => 'float',
        'height_cm' => 'float',
    ];

    // --- RELACIONES ---

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Relación necesaria para calcular el stock comprometido en órdenes pendientes.
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    // --- CÁLCULOS DE LÓGICA DE STOCK (WMS CORE) ---

    /**
     * STOCK FÍSICO: Cantidad total real en la bodega.
     * Excluye ubicaciones bloqueadas (como Cuarentena o Dañados).
     */
    public function getPhysicalStockAttribute()
    {
        return $this->inventory()
            ->whereHas('location', function ($q) {
                $q->where('is_blocked', false);
            })
            ->sum('quantity');
    }

    /**
     * STOCK COMPROMETIDO (RESERVADO):
     * Suma de productos en órdenes que han sido creadas (Pending/Allocated/Processing)
     * pero que aún no han sido despachadas.
     *
     * CORRECCIÓN: Se usan las columnas 'requested_quantity' y 'picked_quantity'
     * para coincidir con la migración de base de datos.
     */
    public function getCommittedStockAttribute()
    {
        // Buscamos ítems de este producto en órdenes "vivas"
        $items = $this->orderItems()
            ->whereHas('order', function ($q) {
                $q->whereIn('status', ['pending', 'allocated', 'processing', 'backorder']);
            })
            ->get();

        // Calculamos cuánto falta por pickear de esas órdenes
        return $items->sum(function ($item) {
            // Si pidieron 10 y ya pickearon 4, quedan 6 "lógicamente reservadas"
            return max(0, $item->requested_quantity - $item->picked_quantity);
        });
    }

    /**
     * STOCK DISPONIBLE:
     * Lo que realmente podemos vender hoy.
     * Físico - Comprometido.
     */
    public function getAvailableStockAttribute()
    {
        return max(0, $this->physical_stock - $this->committed_stock);
    }

    // --- OTROS ACCESSORS ---

    /**
     * Volumen en cm3
     */
    public function getVolumeAttribute()
    {
        return $this->length_cm * $this->width_cm * $this->height_cm;
    }

    /**
     * Alias para compatibilidad con vistas anteriores.
     * Muestra el físico (lo que hay en estantes).
     */
    public function getTotalStockAttribute()
    {
        return $this->physical_stock;
    }
}