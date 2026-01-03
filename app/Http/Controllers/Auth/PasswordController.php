<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class PasswordController extends Controller
{
    // Mostrar formulario para pedir email
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    // Enviar el enlace al correo
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        // Usamos el broker de contraseñas de Laravel para enviar el enlace
        // Esto usa la configuración de mail que ya inyectamos dinámicamente en AppServiceProvider
        $status = Password::broker()->sendResetLink(
            $request->only('email')
        );

        return $status == Password::RESET_LINK_SENT
                    ? back()->with(['status' => __($status)])
                    : back()->withErrors(['email' => __($status)]);
    }

    // Mostrar formulario para resetear contraseña (al hacer clic en el link del correo)
    public function showResetForm(Request $request)
    {
        $token = $request->route('token');
        return view('auth.reset-password', ['token' => $token, 'email' => $request->email]);
    }

    // Guardar nueva contraseña
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|confirmed|min:8',
        ]);

        // Intentar resetear la contraseña
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $status == Password::PASSWORD_RESET
                    ? redirect()->route('login')->with('status', '¡Tu contraseña ha sido restablecida exitosamente!')
                    : back()->withErrors(['email' => [__($status)]]);
    }
}