<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class AuthApiController extends Controller
{
    /**
     * Login para la App Móvil (PDA)
     */
    public function login(Request $request)
    {
        // 1. Validar entrada
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required',
            'device_name' => 'nullable|string'
        ]);

        // 2. Intentar autenticar con Laravel
        // Auth::attempt encripta automáticamente la password entrante y la compara con el hash de la BD
        if (!Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            
            // Log para depuración (Revisa storage/logs/laravel.log si esto pasa)
            Log::warning('Fallo login API', ['email' => $request->email]);
            
            return response()->json([
                'message' => 'Credenciales inválidas. Verifique correo y contraseña.'
            ], 401);
        }

        // 3. Recuperar usuario
        $user = User::where('email', $request->email)->firstOrFail();

        // 4. Validaciones extra (Opcional: Verificar si está activo)
        if ($user->status !== 'active') { // Asumiendo que usas 'status' o 'is_active'
             // Auth::logout(); // Opcional
             // return response()->json(['message' => 'Usuario inactivo.'], 403);
        }

        // 5. Eliminar tokens viejos (Opcional, para mantener limpieza)
        // $user->tokens()->delete();

        // 6. Crear Token Sanctum
        $deviceName = $request->device_name ?? 'PDA_Device';
        $token = $user->createToken($deviceName)->plainTextToken;

        // 7. Respuesta exitosa
        return response()->json([
            'message' => 'Bienvenido ' . $user->name,
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'branch_id' => $user->branch_id
            ]
        ]);
    }

    /**
     * Logout / Revocar Token
     */
    public function logout(Request $request)
    {
        // Revoca el token actual que se usó para la petición
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente'
        ]);
    }
}