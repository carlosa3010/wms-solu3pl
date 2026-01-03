<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BinType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'code', 'length', 'width', 'height', 'max_weight'
    ];

    /**
     * Helper para mostrar dimensiones formateadas
     */
    public function getDimensionsAttribute()
    {
        return "{$this->length}x{$this->width}x{$this->height} cm";
    }
}