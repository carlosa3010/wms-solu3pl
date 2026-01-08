<?php

// app/Models/RMAItem.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RMAItem extends Model
{
    protected $table = 'rma_items';
    
    protected $fillable = [
        'rma_id', 
        'product_id', 
        'qty', 
        'condition', 
        'resolution', 
        'notes',
        'inspection_notes', // Nuevo
        'reception_photos'  // Nuevo
    ];

    // Cast automático de JSON a Array
    protected $casts = [
        'reception_photos' => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}