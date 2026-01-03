<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'details',       // Datos cortos (Cuenta, Banco, Telefono)
        'instructions',  // Paso a paso para el cliente
        'is_active',
    ];
}
