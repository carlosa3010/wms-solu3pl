<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Muestra el formulario de login.
     * Si ya está logueado, redirige según su rol.
     */
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectUser(Auth::user());
        }
        return view('auth.login');
    }

    /**
     * Procesa la autenticación.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Intentar login
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            
            // Redirigir según rol
            return $this->redirectUser(Auth::user());
        }

        // Error de credenciales
        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    /**
     * Cierra la sesión.
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    /**
     * Lógica centralizada de redirección por Rol.
     * CRÍTICO: Debe coincidir con los nombres de rutas en web.php
     */
    protected function redirectUser($user)
    {
        // 1. Verificar si el usuario está activo
        if ($user->status !== 'active') {
            Auth::logout();
            return redirect()->route('login')->withErrors(['email' => 'Tu cuenta se encuentra inactiva. Contacta a soporte.']);
        }

        // 2. Redirecciones por Rol
        switch ($user->role) {
            case 'operator':
                // Ruta corregida: warehouse.dashboard
                return redirect()->route('warehouse.dashboard');
            
            case 'user':
                // Portal de Clientes
                return redirect()->route('client.portal');

            case 'admin':
            case 'manager':
            case 'supervisor':
                // Panel Administrativo
                return redirect()->route('admin.dashboard');

            default:
                // Si el rol no existe o es erróneo, desconectar por seguridad
                Auth::logout();
                return redirect()->route('login')->withErrors(['email' => 'Rol de usuario no asignado correctamente.']);
        }
    }
}