<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return $this->redirectUser(Auth::user());
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();
            return $this->redirectUser(Auth::user());
        }

        return back()->withErrors([
            'email' => 'Las credenciales no coinciden con nuestros registros.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    /**
     * Redirección inteligente basada en el rol
     */
    protected function redirectUser($user)
    {
        if ($user->status !== 'active') {
            Auth::logout();
            return redirect('/login')->withErrors(['email' => 'Tu cuenta está inactiva.']);
        }

        // PANEL WAREHOUSE
        if ($user->role === 'operator') {
            return redirect()->route('warehouse.dashboard');
        }

        // PANEL CLIENTE
        if ($user->role === 'user') {
            return redirect()->route('client.portal');
        }

        // PANEL ADMIN (Admin, Manager, Supervisor)
        if (in_array($user->role, ['admin', 'manager', 'supervisor'])) {
            return redirect()->route('admin.dashboard');
        }

        return redirect('/');
    }
}