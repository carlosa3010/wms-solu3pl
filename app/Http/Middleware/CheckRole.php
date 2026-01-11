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

        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // REDIRECCIONES CORRECTAS
        if ($user->role === 'operator') {
            return redirect()->route('warehouse.dashboard'); // <--- AQUI
        }
        
        if ($user->role === 'user') {
            return redirect()->route('client.portal');
        }

        if (in_array($user->role, ['admin', 'manager', 'supervisor'])) {
            return redirect()->route('admin.dashboard');
        }

        abort(403, 'Acceso denegado.');
    }
}