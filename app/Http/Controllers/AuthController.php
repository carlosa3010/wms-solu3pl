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
            return $this->redirectBasedOnRole(Auth::user());
        }
        return view('auth.login');
    }

    /**
     * Procesa la autenticación.
     */
    public function login(Request $request)
    {
        // Validar datos
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Intentar autenticar
        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return $this->redirectBasedOnRole(Auth::user());
        }

        // Si falla
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
     * Lógica centralizada de redirección según el Rol.
     */
    protected function redirectBasedOnRole($user)
    {
        // 1. Personal Administrativo -> Panel Admin
        if (in_array($user->role, ['admin', 'manager', 'supervisor'])) {
            return redirect()->route('admin.dashboard');
        }
        
        // 2. Operario de Bodega -> Panel Warehouse (PDA)
        // Apuntamos a 'warehouse.index' que es el dashboard de botones grandes que creamos
        if (in_array($user->role, ['operator', 'warehouse'])) {
            return redirect()->route('warehouse.index');
        }

        // 3. Clientes -> Portal Cliente
        // Asumimos que 'client' o cualquier otro rol va al portal
        if ($user->role === 'client' || $user->role === 'user') {
            return redirect()->route('client.portal');
        }

        // Fallback por seguridad
        return redirect()->route('client.portal');
    }
}