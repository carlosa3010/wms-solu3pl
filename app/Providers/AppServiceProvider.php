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
        // Configurar SMTP dinámicamente desde la BD
        try {
            // Verificamos si la tabla existe para evitar errores en migraciones frescas
            if (Schema::hasTable('settings')) {
                // Mapeo directo de claves de BD a configuración de Laravel
                $mailConfig = [
                    'mail_host'         => 'mail.mailers.smtp.host',
                    'mail_port'         => 'mail.mailers.smtp.port',
                    'mail_username'     => 'mail.mailers.smtp.username',
                    'mail_password'     => 'mail.mailers.smtp.password',
                    'mail_encryption'   => 'mail.mailers.smtp.encryption',
                    'mail_from_address' => 'mail.from.address',
                    'mail_from_name'    => 'mail.from.name',
                ];

                $settings = Setting::whereIn('key', array_keys($mailConfig))->pluck('value', 'key');

                foreach ($mailConfig as $dbKey => $configKey) {
                    if (isset($settings[$dbKey]) && !empty($settings[$dbKey])) {
                        Config::set($configKey, $settings[$dbKey]);
                    }
                }
            }
        } catch (\Exception $e) {
            // Fallback silencioso si no hay conexión a BD
        }
    }
}