<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RMAImage extends Model
{
    use HasFactory;

    /**
     * Tabla asociada.
     */
    protected $table = 'rma_images';

    /**
     * Atributos asignables.
     */
    protected $fillable = [
        'rma_id',
        'image_path',
        'uploaded_by', // ID del usuario (operario) que subió la foto
    ];

    /**
     * Relación con el RMA.
     */
    public function rma()
    {
        return $this->belongsTo(RMA::class);
    }

    /**
     * Relación con el usuario que subió la imagen.
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}