<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transfer extends Model
{
    use HasFactory;

    protected $fillable = [
        'origin_branch_id',
        'destination_branch_id',
        'transfer_number',
        'status', // pending, in_transit, completed, cancelled
        'notes',
        'created_by'
    ];

    public function items()
    {
        return $this->hasMany(TransferItem::class);
    }

    public function originBranch()
    {
        return $this->belongsTo(Branch::class, 'origin_branch_id');
    }

    public function destinationBranch()
    {
        return $this->belongsTo(Branch::class, 'destination_branch_id');
    }
}