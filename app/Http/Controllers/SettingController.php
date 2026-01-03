<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail; // Importante
use Illuminate\Support\Facades\Config; // Importante
use App\Mail\TestMail; // Importante para usar el Mailable

class SettingController extends Controller
{
    public function index()
    {
        $settings = Setting::all()->pluck('value', 'key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request)
    {
        $data = $request->except('_token', '_method');

        // 1. Manejar eliminaciones de logos/favicon
        $toClear = ['site_logo', 'report_logo', 'site_favicon'];
        foreach ($toClear as $field) {
            if ($request->input('clear_' . $field) == "1") {
                $setting = Setting::where('key', $field)->first();
                if ($setting && $setting->value) {
                    // Opcional: Eliminar el archivo físico del storage
                    // Convertimos la URL pública (/storage/...) a ruta relativa del disco 'public'
                    $oldPath = str_replace('/storage/', '', $setting->value);
                    if (Storage::disk('public')->exists($oldPath)) {
                        Storage::disk('public')->delete($oldPath);
                    }
                    
                    $setting->update(['value' => null]);
                }
            }
        }

        // 2. Manejar actualizaciones y nuevos archivos
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'clear_')) continue; // Ignorar flags de borrado

            // IMPORTANTE: No sobrescribir la contraseña SMTP si viene vacía
            if ($key === 'mail_password' && empty($value)) {
                continue;
            }

            if ($request->hasFile($key)) {
                // Guardar en disco 'public', carpeta 'branding'
                $path = $request->file($key)->store('branding', 'public');
                // Generar URL accesible
                $value = '/storage/' . $path;
            }

            // Solo actualizamos si el valor no es null (o si es un archivo recién subido)
            if ($value !== null) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        }

        return back()->with('success', 'Configuración actualizada exitosamente.');
    }

    // NUEVO MÉTODO: Enviar correo de prueba
    public function sendTestMail(Request $request)
    {
        $request->validate([
            'test_email' => 'required|email'
        ]);

        // Asegurarnos de que la configuración en tiempo de ejecución esté actualizada con la BD
        // Aunque AppServiceProvider lo hace al inicio, si acabamos de guardar cambios, recargamos.
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

        try {
            // Enviamos usando el Mailable creado
            Mail::to($request->test_email)->send(new TestMail());

            return back()->with('success', 'Correo de prueba enviado correctamente a ' . $request->test_email);
        } catch (\Exception $e) {
            return back()->with('error', 'Fallo al enviar correo: ' . $e->getMessage());
        }
    }
}