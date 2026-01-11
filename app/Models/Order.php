<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'external_ref',
        'client_id',
        'branch_id',
        'transfer_id', // NUEVO: Vinculación con traslado automático
        'customer_name',
        'customer_id_number',
        'customer_email',
        'customer_phone',
        'shipping_address',
        'city',
        'state',
        'customer_zip',
        'country',
        'shipping_method',
        'status',
        'is_backorder', // NUEVO: Bandera de "Sin Stock Local"
        'notes',
    ];

    protected $casts = [
        'is_premium_packing' => 'boolean',
        'is_backorder' => 'boolean', // Casting automático
        'shipped_at' => 'datetime'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relación con el Traslado Automático (Logística de Reabastecimiento).
     * Si la orden requiere stock de otra sucursal, aquí se vincula el movimiento.
     */
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    /**
     * Relación para acceder a las reservaciones de stock (Allocation)
     * a través de los items de la orden.
     */
    public function allocations()
    {
        return $this->hasManyThrough(OrderAllocation::class, OrderItem::class);
    }
}