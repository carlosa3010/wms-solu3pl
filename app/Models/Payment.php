<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'client_id',
        'amount',
        'payment_method', // Texto o ID
        'payment_date',
        'reference',
        'proof_path',
        'status', // pending, approved, rejected
        'notes',  // JSON con metadatos (ej: type: wallet, invoice_id: 5)
        'approved_at'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        // Esto es vital para que json_decode/json_encode sea automático
        'notes' => 'array', 
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}