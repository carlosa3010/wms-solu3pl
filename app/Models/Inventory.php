<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    /**
     * Vinculación explícita a la tabla 'inventory'.
     * Según el volcado SQL, la tabla se llama 'inventory' en minúsculas.
     * * @var string
     */
    protected $table = 'inventory';

    /**
     * Atributos asignables masivamente.
     * * location_id: Vínculo con el bin físico.
     * product_id: Vínculo con el SKU.
     * quantity: Stock actual disponible.
     * lpn: License Plate Number para trazabilidad de bultos/pallets.
     * * @var array
     */
    protected $fillable = [
        'location_id',
        'product_id',
        'quantity',
        'lpn'
    ];

    /**
     * Relación: El registro de inventario pertenece a un Producto (SKU).
     * Permite acceder a datos como nombre, SKU y cliente dueño.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relación: El registro de inventario está asociado a una Ubicación física.
     * Permite saber en qué bodega, pasillo y rack está la mercancía.
     */
    public function location()
    {
        return $this->belongsTo(Location::class, 'location_id');
    }
}