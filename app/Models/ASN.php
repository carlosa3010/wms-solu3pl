<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ASN extends Model
{
    use HasFactory;

    /**
     * Especificamos la tabla porque Laravel intentaría buscar 'a_s_ns' 
     * debido a la convención de pluralización con mayúsculas.
     */
    protected $table = 'asns';

    /**
     * Atributos asignables de forma masiva.
     * Se incluye 'total_packages' para gestionar las copias de etiquetas de bultos.
     */
    protected $fillable = [
        'asn_number',
        'client_id',
        'status', // sent, in_transit, receiving, completed, cancelled
        'expected_arrival_date',
        'carrier_name',
        'tracking_number',
        'total_packages', // Cantidad de bultos/cajas para impresión de etiquetas
        'document_ref',
        'reference_number',
        'notes'
    ];

    /**
     * Conversión de tipos.
     */
    protected $casts = [
        'expected_arrival_date' => 'date',
        'total_packages' => 'integer',
    ];

    /**
     * Relación: Pertenece a un Cliente.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación: Tiene muchos Items (Detalle de productos).
     */
    public function items()
    {
        return $this->hasMany(ASNItem::class, 'asn_id');
    }

    /**
     * Helper: Total de unidades físicas esperadas en el envío.
     * Uso: $asn->total_expected
     */
    public function getTotalExpectedAttribute()
    {
        return $this->items->sum('expected_quantity');
    }

    /**
     * Helper: Total de unidades físicas ya procesadas en bodega.
     * Uso: $asn->total_received
     */
    public function getTotalReceivedAttribute()
    {
        return $this->items->sum('received_quantity');
    }
}