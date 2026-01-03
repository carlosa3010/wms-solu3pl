<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RMAItem extends Model
{
    use HasFactory;

    protected $table = 'rma_items';

    protected $fillable = [
        'rma_id',
        'product_id',
        'quantity',
        'condition', // new, damaged, open_box
        'action_taken' // restock, quarantine, discard
    ];

    /**
     * Relación: Pertenece a una cabecera de RMA.
     */
    public function rma()
    {
        return $this->belongsTo(RMA::class, 'rma_id');
    }

    /**
     * Relación: Referencia al producto del catálogo.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}