<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Configuración Dinámica de Correo (SMTP) desde Base de Datos
        try {
            // Verificamos si la tabla existe para no romper comandos de artisan
            if (Schema::hasTable('settings')) {
                // Obtenemos todos los settings de correo de una sola vez
                $settings = Setting::where('key', 'like', 'mail_%')->pluck('value', 'key');

                // Si existe un host configurado en BD, sobrescribimos la configuración
                if (isset($settings['mail_host']) && !empty($settings['mail_host'])) {
                    
                    // PASO CRÍTICO: Forzar a Laravel a usar SMTP en lugar de lo que diga el .env (log/array)
                    Config::set('mail.default', 'smtp');
                    Config::set('mail.mailers.smtp.transport', 'smtp');

                    // Inyectar credenciales
                    Config::set('mail.mailers.smtp.host', $settings['mail_host']);
                    Config::set('mail.mailers.smtp.port', $settings['mail_port'] ?? 587);
                    Config::set('mail.mailers.smtp.username', $settings['mail_username'] ?? null);
                    Config::set('mail.mailers.smtp.password', $settings['mail_password'] ?? null);
                    Config::set('mail.mailers.smtp.encryption', $settings['mail_encryption'] ?? 'tls');
                    
                    // Configurar remitente global
                    if (isset($settings['mail_from_address'])) {
                        Config::set('mail.from.address', $settings['mail_from_address']);
                    }
                    if (isset($settings['mail_from_name'])) {
                        Config::set('mail.from.name', $settings['mail_from_name']);
                    }
                }
            }
        } catch (\Exception $e) {
            // Fallback silencioso: Si falla la BD, usa la config del .env
        }
    }
}