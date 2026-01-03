<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    // Tabla existente en tu DB
    protected $table = 'stock_movements';

    // Desactivamos updated_at ya que los movimientos son históricos/inmutables
    public $timestamps = false;

    protected $fillable = [
        'product_id',
        'from_location_id',
        'to_location_id',
        'quantity',
        'reason', // 'Compra', 'Venta', 'Ajuste', 'Merma'
        'reference_number', // Número de orden, o 'AJUSTE-001'
        'user_id',
        'created_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relaciones
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function fromLocation()
    {
        return $this->belongsTo(Location::class, 'from_location_id');
    }

    public function toLocation()
    {
        return $this->belongsTo(Location::class, 'to_location_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}