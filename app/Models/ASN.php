<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ASN extends Model
{
    use HasFactory;

    // Especificamos la tabla porque Laravel intentaría buscar 'a_s_ns' por la mayúscula
    protected $table = 'asns';

    protected $fillable = [
        'asn_number',
        'client_id',
        'status', // draft, pending, receiving, completed, cancelled
        'expected_arrival_date',
        'carrier_name',
        'tracking_number',
        'document_ref',
        'notes'
    ];

    protected $casts = [
        'expected_arrival_date' => 'date',
    ];

    /**
     * Relación: Pertenece a un Cliente.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación: Tiene muchos Items (Detalle).
     */
    public function items()
    {
        return $this->hasMany(ASNItem::class, 'asn_id');
    }
    
    // Helper: Total de unidades esperadas
    public function getTotalExpectedAttribute()
    {
        return $this->items->sum('expected_quantity');
    }
    
    // Helper: Total de unidades recibidas
    public function getTotalReceivedAttribute()
    {
        return $this->items->sum('received_quantity');
    }
}