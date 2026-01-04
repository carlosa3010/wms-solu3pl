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
        'client_id',
        'branch_id',
        'order_number',
        'reference_number',
        'customer_name',
        'customer_address',
        'customer_city',
        'customer_state',
        'customer_zip',
        'customer_country',
        'customer_phone',
        'customer_email',
        'shipping_method',
        'is_premium_packing', // Identificador de servicio extra
        'notes',
        'status',
        'shipped_at'
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