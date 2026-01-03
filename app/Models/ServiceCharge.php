<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceCharge extends Model
{
    protected $table = 'service_charges';

    public $timestamps = false;

    protected $fillable = [
        'client_id',
        'type', // storage, picking, inbound, manual
        'description',
        'amount',
        'reference_id',
        'charge_date',
        'is_invoiced',
        'created_at'
    ];

    protected $casts = [
        'charge_date' => 'date',
        'amount' => 'decimal:2',
        'is_invoiced' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}