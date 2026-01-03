<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * Los atributos que se pueden asignar masivamente.
     * * @var array
     */
    protected $fillable = [
        'order_number',       // Identificador único interno
        'client_id',          // Propietario de la mercancía
        'branch_id',          // Sede asignada por inteligencia geográfica
        'customer_name',      // Nombre del destinatario
        'customer_id_number', // Cédula o RIF (Requerido para Courier en Venezuela)
        'customer_email',     // Correo del destinatario
        'shipping_address',   // Dirección completa
        'city',               // Ciudad de destino
        'state',              // Estado/Provincia (Clave para asignación)
        'country',            // País (Default: Venezuela)
        'phone',              // Teléfono de contacto
        'status',             // Estado del flujo (pending, picking, etc.)
        'shipping_method',    // Zoom, Tealca, MRW, etc.
        'external_ref',       // ID de venta externa (Shopify, WooCommerce)
        'notes'               // Instrucciones especiales de empaque o entrega
    ];

    /**
     * Relación con el Cliente (Dueño de la mercancía).
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación con la Sucursal (Sede responsable de procesar el pedido).
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relación con los productos/ítems detallados en el pedido.
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Accessor para obtener la etiqueta del estado en lenguaje humano.
     * * @return string
     */
    public function getStatusLabelAttribute()
    {
        $statusMap = [
            'pending'   => 'Pendiente',
            'allocated' => 'Stock Reservado',
            'picking'   => 'En Picking',
            'packing'   => 'En Empaque',
            'shipped'   => 'Despachado',
            'cancelled' => 'Cancelado',
        ];

        return $statusMap[$this->status] ?? ucfirst($this->status);
    }

    /**
     * Accessor para obtener el color del badge según el estado.
     * * @return string
     */
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending'   => 'yellow',
            'allocated' => 'blue',
            'picking'   => 'indigo',
            'packing'   => 'purple',
            'shipped'   => 'green',
            'cancelled' => 'red',
            default     => 'slate',
        };
    }
}