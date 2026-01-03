<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Http\Controllers\BillingController;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Console Routes - Solu3PL WMS
|--------------------------------------------------------------------------
|
| Este archivo permite definir comandos de Artisan basados en cierres y
| configurar la programación de tareas (Scheduling) del sistema.
|
*/

/**
 * Comando de ejemplo: Muestra una frase inspiradora.
 * Ejecución: php artisan inspire
 */
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
|--------------------------------------------------------------------------
| PROGRAMACIÓN DE TAREAS (SCHEDULING)
|--------------------------------------------------------------------------
| Definimos los procesos automáticos que el servidor ejecutará sin
| intervención humana (requiere configurar el Cron Job del servidor).
*/

// TAREA: Proceso de Facturación y Cargos por Almacenamiento
// Se ejecuta automáticamente cada medianoche (00:00)
Schedule::call(function () {
    try {
        Log::info('Iniciando proceso automático de facturación diaria...');
        
        // Instanciamos el controlador y ejecutamos la lógica de cobro de bines
        app(BillingController::class)->runDailyBilling();
        
        Log::info('Proceso de facturación diaria completado con éxito.');
    } catch (\Exception $e) {
        Log::error('Error en el proceso automático de facturación: ' . $e->getMessage());
    }
})->dailyAt('00:00');

/**
 * TAREA OPCIONAL: Limpieza de logs antiguos (Mantenimiento)
 * Ayuda a mantener el servidor optimizado.
 */
Schedule::command('logs:clear')->weekly();