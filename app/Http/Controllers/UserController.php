<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Muestra la lista de usuarios.
     */
    public function index()
    {
        // Traemos todos los usuarios con su cliente (si aplica)
        $users = User::with('client')->orderBy('created_at', 'desc')->paginate(10);
        $clients = Client::orderBy('company_name')->get();
        
        // Módulos disponibles para asignar permisos a Managers/Supervisores
        $availableModules = [
            'dashboard' => 'Panel de Control',
            'inventory' => 'Inventario',
            'orders'    => 'Pedidos',
            'receptions'=> 'Recepciones',
            'billing'   => 'Facturación',
            'settings'  => 'Configuración'
        ];

        return view('admin.users.index', compact('users', 'clients', 'availableModules'));
    }

    /**
     * Guarda un nuevo usuario y genera contraseña automática.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email',
            // CORRECCIÓN: Se cambió 'client' por 'user'
            'role'      => 'required|in:admin,manager,supervisor,operator,user',
            // CORRECCIÓN: El client_id es requerido si el rol es 'user'
            'client_id' => 'nullable|required_if:role,user|exists:clients,id',
            'status'    => 'required|in:active,inactive',
            'modules'   => 'nullable|array'
        ]);

        // 1. Generar contraseña aleatoria
        $generatedPassword = Str::random(12);

        // 2. Crear usuario
        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($generatedPassword),
            'role'        => $validated['role'],
            'client_id'   => $validated['role'] === 'user' ? $validated['client_id'] : null,
            'status'      => $validated['status'],
            'permissions' => $validated['modules'] ?? [], 
        ]);

        // 3. Retornar con mensaje Flash conteniendo la contraseña
        return redirect()->route('admin.users.index')
            ->with('success', "Usuario creado exitosamente.\n\nCREDENCIALES:\nEmail: {$validated['email']}\nContraseña: {$generatedPassword}");
    }

    /**
     * Actualiza un usuario existente.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            // CORRECCIÓN: Se cambió 'client' por 'user'
            'role'      => 'required|in:admin,manager,supervisor,operator,user',
            'client_id' => 'nullable|required_if:role,user|exists:clients,id',
            'status'    => 'required|in:active,inactive',
            'modules'   => 'nullable|array'
        ]);

        $user->update([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'role'        => $validated['role'],
            'client_id'   => $validated['role'] === 'user' ? $validated['client_id'] : null,
            'status'      => $validated['status'],
            'permissions' => $validated['modules'] ?? [],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Perfil de usuario actualizado correctamente.');
    }

    /**
     * Elimina un usuario.
     */
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado permanentemente.');
    }

    /**
     * Restablece la contraseña de un usuario y la muestra.
     */
    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        $newPassword = Str::random(12);
        
        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', "Contraseña restablecida para {$user->name}.\n\nNUEVA CONTRASEÑA: {$newPassword}");
    }
}