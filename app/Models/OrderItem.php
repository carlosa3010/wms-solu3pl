<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    /**
     * Campos asignables masivamente.
     * Coinciden estrictamente con la migración de base de datos.
     */
    protected $fillable = [
        'order_id', 
        'product_id', 
        'requested_quantity', // Cantidad solicitada por el cliente
        'allocated_quantity', // Cantidad reservada físicamente (hard reserve)
        'picked_quantity'     // Cantidad ya recolectada por el operario
    ];

    /**
     * Casting de tipos para asegurar que siempre sean números.
     */
    protected $casts = [
        'requested_quantity' => 'integer',
        'allocated_quantity' => 'integer',
        'picked_quantity' => 'integer',
    ];

    // --- RELACIONES ---

    /**
     * Relación: Un ítem pertenece a una orden de pedido.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relación: El ítem representa a un producto específico.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Un ítem de pedido puede tener varias ubicaciones 
     * asignadas (ej: sacar 10 unidades del Bin A y 5 del Bin B).
     * Esto conecta con la tabla 'order_allocations'.
     */
    public function allocations()
    {
        return $this->hasMany(OrderAllocation::class, 'order_item_id');
    }

    // --- ACCESSORS / HELPERS ---

    /**
     * Accessor Mágico: 'quantity'
     * Permite acceder a $item->quantity aunque la columna en BD sea 'requested_quantity'.
     * Vital para compatibilidad con código antiguo o vistas genéricas.
     */
    public function getQuantityAttribute()
    {
        return $this->requested_quantity;
    }

    /**
     * Calcula cuántas unidades faltan por recolectar.
     */
    public function getPendingQuantityAttribute()
    {
        return max(0, $this->requested_quantity - $this->picked_quantity);
    }
}