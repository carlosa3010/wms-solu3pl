<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * Los atributos que son asignables masivamente.
     * Se incluye 'permissions' para el manejo de módulos del Supervisor.
     * Se incluye 'status' para manejar el estado activo/inactivo del usuario.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'client_id',
        'role',
        'status', // AGREGADO: Importante para que el update/create funcione
        'permissions',
        'can_manage_billing',
        'can_manage_users',
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     * 'permissions' debe ser 'array' para que Laravel convierta el JSON automáticamente.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'permissions' => 'array', 
        'can_manage_billing' => 'boolean',
        'can_manage_users' => 'boolean',
    ];

    /**
     * Relación con el cliente vinculado.
     */
    public function client()
    {
        return $this->belongsTo(Client::class, 'client_id');
    }
    
    /**
     * Helper para verificar si el usuario es administrador.
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * Helper para verificar acceso a módulos específicos (Supervisor).
     */
    public function hasModuleAccess($module)
    {
        if ($this->isAdmin()) return true;
        
        return is_array($this->permissions) && in_array($module, $this->permissions);
    }
}