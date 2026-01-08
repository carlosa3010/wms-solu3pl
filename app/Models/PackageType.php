<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PackageType extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id',
        'name',
        'length',
        'width',
        'height',
        'max_weight',
        'empty_weight',
        'is_active'
    ];

    // Accesor para mostrar dimensiones formateadas
    public function getDimensionsAttribute()
    {
        return "{$this->length} x {$this->width} x {$this->height}";
    }

    // Calcular volumen (útil para el algoritmo de packing)
    public function getVolumeAttribute()
    {
        return $this->length * $this->width * $this->height;
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}