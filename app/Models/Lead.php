<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Lead extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name',
        'contact_name',
        'email',
        'phone',
        'source',
        'status', // new, contacted, interested, converted, rejected
        'notes'
    ];

    /**
     * Scope para filtrar prospectos por estado.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}