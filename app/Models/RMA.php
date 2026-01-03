<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RMA extends Model
{
    use HasFactory;

    /**
     * Vinculación explícita a la tabla 'rmas'.
     */
    protected $table = 'rmas';

    /**
     * Atributos asignables de forma masiva.
     */
    protected $fillable = [
        'rma_number',
        'order_id',
        'client_id',
        'customer_name',
        'reason',
        'status',
        'internal_notes'
    ];

    /**
     * Relación: La devolución pertenece a un Cliente (Dueño de la mercancía).
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * Relación: Vínculo opcional con la orden de salida original.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Relación: Contiene varios productos devueltos.
     */
    public function items()
    {
        return $this->hasMany(RMAItem::class, 'rma_id');
    }

    /**
     * Accessor: Determina el color del badge en la vista según el estado.
     * Corregido para compatibilidad con versiones anteriores a PHP 8.0.
     */
    public function getStatusColorAttribute()
    {
        $statusColors = [
            'pending'    => 'yellow',
            'received'   => 'blue',
            'inspecting' => 'purple',
            'completed'  => 'green',
            'cancelled'  => 'red',
        ];

        return isset($statusColors[$this->status]) ? $statusColors[$this->status] : 'slate';
    }
}