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
     * Importante: 'branch_id' agregado para definir el destino.
     */
    protected $fillable = [
        'asn_number',
        'client_id',
        'branch_id', // <--- NUEVO: Destino físico de la carga
        'status',    // pending, in_transit, receiving, completed, cancelled
        'expected_arrival_date',
        'carrier_name',
        'tracking_number',
        'total_packages', // Cantidad de bultos para etiquetas master
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
        'branch_id' => 'integer',
        'client_id' => 'integer',
    ];

    // ==========================================
    // RELACIONES
    // ==========================================

    /**
     * Relación: Pertenece a un Cliente.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Relación: Pertenece a una Sucursal de Destino.
     * Permite saber a qué bodega física llegará el camión.
     */
    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Relación: Tiene muchos Items (Detalle de productos).
     */
    public function items()
    {
        return $this->hasMany(ASNItem::class, 'asn_id');
    }

    // ==========================================
    // HELPERS Y ACCESSORS
    // ==========================================

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
    
    /**
     * Helper: Porcentaje de completitud.
     */
    public function getProgressAttribute()
    {
        $expected = $this->total_expected;
        if ($expected <= 0) return 0;
        
        $received = $this->total_received;
        return min(100, round(($received / $expected) * 100));
    }
}