<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect('login');
        }

        $user = Auth::user();

        // 1. Si el usuario tiene uno de los roles permitidos, pasa.
        if (in_array($user->role, $roles)) {
            return $next($request);
        }

        // 2. Si no tiene permiso, lo redirigimos a SU panel correspondiente (evita error 403 feo)
        if ($user->role === 'operator') {
            return redirect()->route('warehouse.dashboard');
        }
        
        if ($user->role === 'user') {
            return redirect()->route('client.portal');
        }

        if (in_array($user->role, ['admin', 'manager', 'supervisor'])) {
            return redirect()->route('admin.dashboard');
        }

        return abort(403, 'Acceso no autorizado.');
    }
}