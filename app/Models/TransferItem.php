<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    /**
     * Atributos asignables de forma masiva.
     * Se incluye target_location_id para permitir que la inteligencia de bines
     * guarde la ubicación de destino sugerida desde la creación de la orden.
     */
    protected $fillable = [
        'transfer_id',
        'product_id',
        'quantity',
        'target_location_id'
    ];

    /**
     * Relación: El ítem pertenece a una orden de traslado (Cabecera).
     */
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    /**
     * Relación: El ítem está vinculado a un producto específico del catálogo.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación: Ubicación de destino (Bin) sugerida o pre-asignada.
     * Esta relación es clave para el funcionamiento del BinAllocator en el controlador.
     */
    public function targetLocation()
    {
        return $this->belongsTo(Location::class, 'target_location_id');
    }
}