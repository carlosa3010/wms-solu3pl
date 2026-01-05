<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'invoice_number',
        'client_id',
        'pre_invoice_id', // Relación con la pre-factura origen
        'period_start',
        'period_end',
        'issue_date',
        'due_date',
        'subtotal',
        'tax',
        'total',
        'status', // draft, sent, paid, overdue, cancelled
        'notes',
        'pdf_path'
    ];

    protected $casts = [
        'issue_date' => 'date',
        'due_date' => 'date',
        'period_start' => 'date',
        'period_end' => 'date',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function preInvoice()
    {
        return $this->belongsTo(PreInvoice::class);
    }

    public function charges()
    {
        return $this->hasMany(ServiceCharge::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}