<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Atributos asignables.
     * Se incluye is_premium_packing para disparar la lógica de cobro extra en el ShippingController.
     */
    protected $fillable = [
    'order_number',
    'external_ref', // Añadir este
    'client_id',
    'branch_id',
    'customer_name',
    'customer_id_number', // Añadir este
    'customer_email',
    'customer_phone',
    'shipping_address',
    'city',
    'state',
    'customer_zip',
    'country',
    'shipping_method',
    'status',
    'notes',
    ];

    protected $casts = [
        'is_premium_packing' => 'boolean',
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
}