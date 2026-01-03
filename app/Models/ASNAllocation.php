<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ASNAllocation extends Model
{
    use HasFactory;

    // ESENCIAL: Definir la tabla explícitamente para evitar que Laravel busque 'a_s_n_allocations'
    protected $table = 'asn_allocations';

    protected $fillable = ['asn_item_id', 'location_id', 'quantity', 'status'];

    public function item()
    {
        return $this->belongsTo(ASNItem::class, 'asn_item_id');
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
}