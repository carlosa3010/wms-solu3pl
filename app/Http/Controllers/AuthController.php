<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Muestra el formulario de login
    public function showLoginForm()
    {
        return view('auth.login');
    }

    // Procesa el login
    public function login(Request $request)
    {
        // Validar datos
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Intentar autenticar (Auth::attempt hace el hash check automáticamente)
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            // --- REDIRECCIÓN INTELIGENTE POR ROL ---
            $user = Auth::user();

            // 1. Si es Administrador
            if ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            }
            
            // 2. Si es Operario de Bodega
            if ($user->role === 'operator') {
                return redirect()->route('warehouse.station');
            }

            // 3. Si es Cliente
            // (Asumiremos que cualquier otro rol o 'user' es cliente)
            return redirect()->route('client.portal');
        }

        // Si falla
        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    // Cerrar Sesión
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }
}