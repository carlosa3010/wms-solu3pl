<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Atributos asignables de forma masiva.
     * Se han incluido los campos de dimensiones con sufijo _cm para coincidir con la DB.
     */
    protected $fillable = [
        'client_id',
        'category_id',
        'sku',
        'name',
        'description',
        'barcode',
        'weight_kg',
        'length_cm',
        'width_cm',
        'height_cm',
        'image_path',
        'min_stock_level',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'weight_kg' => 'float',
        'length_cm' => 'float',
        'width_cm'  => 'float',
        'height_cm' => 'float',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Accesor para calcular el volumen.
     */
    public function getVolumeAttribute()
    {
        return $this->length_cm * $this->width_cm * $this->height_cm;
    }

    /**
     * Accesor para el stock total (usado en la vista de catálogo).
     */
    public function getTotalStockAttribute()
    {
        return $this->inventory()->sum('quantity');
    }
}