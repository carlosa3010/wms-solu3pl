<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ASNItem extends Model
{
    use HasFactory;

    // Especificamos la tabla para evitar problemas de pluralización automática
    protected $table = 'asn_items';

    protected $fillable = [
        'asn_id',
        'product_id',
        'expected_quantity',
        'received_quantity',
        'status' // pending, received, discrepancy
    ];

    /**
     * Relación: Pertenece a una ASN.
     */
    public function asn()
    {
        return $this->belongsTo(ASN::class);
    }

    /**
     * Relación: Es un Producto específico.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Tiene muchas asignaciones de bines (Allocations).
     * Esta es la relación que faltaba y causaba el error.
     */
    public function allocations()
    {
        return $this->hasMany(ASNAllocation::class, 'asn_item_id');
    }
}