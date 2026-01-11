<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    // DEFINICIÓN CRÍTICA: La tabla se llama 'inventory' en singular en la BD provista
    protected $table = 'inventory';

    protected $fillable = [
        'product_id',
        'location_id',
        'quantity',
        'lpn',
        'batch_number',
        'expiry_date',
        'branch_id' // Si existe en la tabla para acceso rápido
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }
    
    public function branch()
    {
        // Relación a través de Location si branch_id no está directo en inventory
        return $this->hasOneThrough(Branch::class, Location::class, 'id', 'id', 'location_id', 'warehouse_id')
                    ->join('warehouses', 'locations.warehouse_id', '=', 'warehouses.id')
                    ->select('branches.*');
    }
}