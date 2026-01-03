<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
// use Laravel\Sanctum\HasApiTokens; // <--- ESTA LINEA CAUSABA EL ERROR

class User extends Authenticatable
{
    use HasFactory, Notifiable; // <--- AQUI TAMBIEN QUITAMOS HasApiTokens

    /**
     * Los atributos que son asignables masivamente.
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'client_id',
        'role',
        'can_manage_billing',
        'can_manage_users',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'can_manage_billing' => 'boolean',
        'can_manage_users' => 'boolean',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
    
    public function isAdmin()
    {
        return $this->role === 'admin';
    }
}
