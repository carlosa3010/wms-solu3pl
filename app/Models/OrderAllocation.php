<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderAllocation extends Model
{
    use HasFactory;

    protected $fillable = ['order_item_id', 'location_id', 'quantity'];

    public function item()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * CORRECCIÓN: Alias 'bin' para mantener compatibilidad 
     * con el controlador que llama a 'allocations.bin'
     */
    public function bin()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}