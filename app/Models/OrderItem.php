<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 
        'product_id', 
        'requested_quantity', 
        'allocated_quantity', 
        'picked_quantity'
    ];

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
     * NUEVA RELACIÓN: Un ítem de pedido puede tener varias ubicaciones 
     * asignadas (ej: sacar 10 unidades del Bin A y 5 del Bin B).
     */
    public function allocations()
    {
        return $this->hasMany(OrderAllocation::class, 'order_item_id');
    }
}