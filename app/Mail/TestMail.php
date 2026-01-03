<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TestMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        // No necesitamos datos dinámicos para esta prueba simple
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('✅ Éxito: Prueba de Conexión SMTP - Solu3PL')
                    ->view('emails.test');
    }
}