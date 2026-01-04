<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RMA extends Model
{
    use HasFactory;

    protected $table = 'rmas';

    protected $fillable = [
        'rma_number',
        'client_id',
        'order_id',
        'customer_name',
        'reason',
        'status',
        'admin_notes',
        'internal_notes',
        'processed_at'
    ];

    protected $casts = [
        'processed_at' => 'datetime'
    ];

    /**
     * Relación: El RMA pertenece a un cliente.
     * Crucial para buscar su acuerdo tarifario al procesar el cobro.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(RMAItem::class, 'rma_id');
    }

    public function images()
    {
        return $this->hasMany(RMAImage::class, 'rma_id');
    }
}