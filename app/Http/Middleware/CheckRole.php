<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        // Si el usuario tiene el rol permitido, adelante
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // --- LÓGICA DE REDIRECCIÓN SI NO TIENE PERMISO ---
        
        // 1. Si es Operador -> Panel Warehouse
        if ($user->role === 'operator') {
            return redirect()->route('warehouse.index'); // Esta ruta ya existe en web.php
        }
        
        // 2. Si es Cliente (User) -> Portal
        if ($user->role === 'user') {
            return redirect()->route('client.portal');
        }

        // 3. Si es Admin/Manager -> Panel Admin
        if (in_array($user->role, ['admin', 'manager', 'supervisor'])) {
            return redirect()->route('admin.dashboard');
        }

        return abort(403);
    }
}