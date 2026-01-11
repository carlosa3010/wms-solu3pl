<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    // Constantes para los tipos de traslado (Mejora la legibilidad y evita errores)
    const TYPE_INTER_BRANCH = 'inter_branch';   // Logística entre sucursales distintas
    const TYPE_INTERNAL = 'internal';           // Movimiento interno (Bin a Bin en la misma bodega)
    const TYPE_CROSS_DOCKING = 'cross_docking'; // Traslado automático por falta de stock

    protected $fillable = [
        'origin_branch_id',
        'destination_branch_id',
        'transfer_number',
        'type',   // NUEVO: Define si es interno, logística o cross-docking
        'status', // pending, in_transit, completed, cancelled
        'notes',
        'created_by'
    ];

    /**
     * Relación con los items del traslado.
     */
    public function items()
    {
        return $this->hasMany(TransferItem::class);
    }

    /**
     * Sucursal de Origen.
     */
    public function originBranch()
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    /**
     * Sucursal de Destino.
     */
    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }

    /**
     * Usuario que creó el traslado.
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Órdenes que dependen de este traslado (Backorders).
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Helper para saber si es un movimiento interno (misma sucursal).
     */
    public function isInternal()
    {
        return $this->type === self::TYPE_INTERNAL;
    }
}