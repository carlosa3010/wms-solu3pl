<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserCredentials extends Mailable
{
    use Queueable, SerializesModels;

    public $user;
    public $password;
    public $isClient;

    public function __construct(User $user, string $password, bool $isClient = false)
    {
        $this->user = $user;
        $this->password = $password;
        $this->isClient = $isClient;
    }

    public function build()
    {
        $subject = $this->isClient 
            ? 'Bienvenido al Portal de Clientes - Solu3PL' 
            : 'Credenciales de Acceso - Solu3PL WMS';

        return $this->subject($subject)->view('emails.credentials');
    }
}