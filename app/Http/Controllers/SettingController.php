<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use Illuminate\Support\Facades\Storage;

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
                if ($setting) {
                    // Opcional: Eliminar el archivo físico del storage
                    $oldPath = str_replace('/storage/', '', $setting->value);
                    Storage::disk('public')->delete($oldPath);
                    
                    $setting->update(['value' => null]);
                }
            }
        }

        // 2. Manejar actualizaciones y nuevos archivos
        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'clear_')) continue; // Ignorar flags de borrado

            if ($request->hasFile($key)) {
                $path = $request->file($key)->store('branding', 'public');
                $value = '/storage/' . $path;
            }

            if ($value !== null) {
                Setting::updateOrCreate(
                    ['key' => $key],
                    ['value' => $value]
                );
            }
        }

        return back()->with('success', 'Configuración actualizada exitosamente.');
    }
}