<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAllocation extends Model
{
    use HasFactory;

    /**
     * Campos que se pueden asignar masivamente.
     * 'inventory_id' es clave para vincular con el stock físico específico.
     */
    protected $fillable = [
        'order_id',
        'order_item_id',
        'inventory_id', 
        'quantity'
    ];

    /**
     * Relación con el Inventario Físico.
     * Esta es la relación que faltaba y causaba el error en el Picking.
     * Permite acceder a $allocation->inventory->location->code.
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    /**
     * Relación con la Orden Padre.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relación con el Ítem de la Orden (Línea de producto).
     */
    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    // --- Helpers / Alias para compatibilidad con vistas ---

    /**
     * Alias para acceder al item (compatibilidad).
     */
    public function item()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    /**
     * Acceso directo a la ubicación a través del inventario.
     * Útil si en alguna vista llamas a $allocation->bin
     */
    public function getBinAttribute()
    {
        return $this->inventory ? $this->inventory->location : null;
    }
}