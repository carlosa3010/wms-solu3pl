<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthApiController extends Controller
{
    /**
     * Login para PDAs. Retorna un Token de Sanctum.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
            'device_name' => 'required', // Ej: "PDA-01"
        ]);

        // 1. Intentar Autenticación
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // 2. Validar Estado (Si usas soft deletes o campo status)
        if ($user->status !== 'active') { // Asumiendo que 'active' es el valor para usuarios habilitados
             return response()->json([
                'success' => false,
                'message' => 'Usuario inactivo o suspendido'
            ], 403);
        }

        // 3. Validar ROLES permitidos en la PDA
        // Se elimina 'warehouse' y se agregan los roles reales de operación
        $allowedRoles = ['admin', 'manager', 'supervisor', 'operator']; // Agregué 'operator' u 'operario' según uses en BD

        if (!in_array($user->role, $allowedRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Acceso denegado: Tu rol (' . $user->role . ') no tiene permiso para usar la PDA.'
            ], 403);
        }

        // 4. Crear Token (Elimina tokens anteriores para mantener sesión única por dispositivo si deseas)
        // $user->tokens()->delete(); 
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'role' => $user->role,
                'branch_id' => $user->branch_id // Útil para que la App sepa en qué bodega está
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true, 'message' => 'Sesión cerrada']);
    }
}