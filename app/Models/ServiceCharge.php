<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCharge extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'pre_invoice_id', // Vinculación a la pre-factura mensual
        'invoice_id',     // Vinculación a la factura final (si ya se emitió)
        'type',           // picking, storage, reception, rma, picking_extra, premium_packing
        'description',
        'amount',
        'quantity',       // Cantidad (ej. número de cajas, bines, m3)
        'unit_price',     // Precio unitario aplicado
        'charge_date',
        'is_invoiced',    // Booleano para saber si ya pasó a factura final
        'reference_type', // order, asn, rma
        'reference_id'
    ];

    protected $casts = [
        'charge_date' => 'datetime',
        'is_invoiced' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function preInvoice()
    {
        return $this->belongsTo(PreInvoice::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}