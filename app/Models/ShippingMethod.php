<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',             // Ej: Estándar, Express
        'code',             // Ej: standard, express, pickup (útil para lógica interna)
        'description',      // Ej: Entrega en 3-5 días hábiles
        'cost',             // Costo base (opcional)
        'is_active',
    ];
    
    public function rates()
    {
        return $this->hasMany(ShippingRate::class);
    }
}