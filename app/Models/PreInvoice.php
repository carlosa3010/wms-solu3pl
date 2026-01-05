<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'period_month', // Formato YYYY-MM
        'status',       // open, closed, invoiced
        'total_amount'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function details()
    {
        return $this->hasMany(PreInvoiceDetail::class);
    }
    
    // Relación opcional si una pre-factura se convierte en una factura fiscal real
    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }
}