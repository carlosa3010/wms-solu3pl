<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_id',
        'amount',
        'payment_method',
        'payment_date',
        'reference',
        'proof_path',
        'status',
        'approved_at'
    ];

    protected $casts = [
        'payment_date' => 'date',
        'approved_at' => 'datetime',
        'amount' => 'decimal:2',
    ];

    /**
     * Relación con el cliente que realizó el pago.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}