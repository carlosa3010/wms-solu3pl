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
     * @var array
     */
    protected $fillable = [
        'client_id',      // Dueño del producto
        'category_id',    // Categoría logística (opcional)
        'sku',            // Stock Keeping Unit (Código único)
        'name',           // Nombre descriptivo
        'description',    // Detalles adicionales
        'barcode',        // Código de barras (EAN/UPC)
        'weight_kg',      // Peso para cálculos de transporte
        'length_cm',      // Largo para inteligencia de bines
        'width_cm',       // Ancho para inteligencia de bines
        'height_cm',      // Alto para inteligencia de bines
        'image_url',      // Foto del producto
        'is_active'       // Estado en el catálogo
    ];

    /**
     * Conversión de tipos.
     * @var array
     */
    protected $casts = [
        'is_active' => 'boolean',
        'weight_kg' => 'float',
        'length_cm' => 'float',
        'width_cm'  => 'float',
        'height_cm' => 'float',
    ];

    /**
     * Relación: El producto pertenece a un Cliente (Dueño de la carga).
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación: El producto pertenece a una Categoría logística.
     */
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relación: Existencias físicas en diferentes bines/bodegas.
     */
    public function inventory()
    {
        return $this->hasMany(Inventory::class);
    }

    /**
     * Relación: Historial de movimientos en el Kardex.
     */
    public function movements()
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Relación: Participación en ítems de pedidos (Salidas).
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Relación: Participación en ítems de recepciones (Entradas).
     */
    public function asnItems()
    {
        return $this->hasMany(ASNItem::class);
    }

    /**
     * Helper: Calcula el volumen cúbico del producto (cm3).
     * Vital para la lógica de asignación automática de bines.
     */
    public function getVolumeAttribute()
    {
        return $this->length_cm * $this->width_cm * $this->height_cm;
    }

    /**
     * Helper: Obtiene el stock total sumando todas las ubicaciones.
     */
    public function getTotalStockAttribute()
    {
        return $this->inventory()->sum('quantity');
    }

    /**
     * Scope para buscar por SKU o Nombre.
     */
    public function scopeSearch($query, $term)
    {
        return $query->where('sku', 'like', "%{$term}%")
                     ->orWhere('name', 'like', "%{$term}%");
    }
}