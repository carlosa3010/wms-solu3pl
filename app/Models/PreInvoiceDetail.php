<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreInvoiceDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'pre_invoice_id',
        'activity_date',
        'concept',
        'quantity',
        'unit_price',
        'total_price',
        'reference_type', // order, reception, storage, etc.
        'reference_id'
    ];

    protected $casts = [
        'activity_date' => 'date',
    ];

    public function preInvoice()
    {
        return $this->belongsTo(PreInvoice::class);
    }
}