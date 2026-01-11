<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Client;
use App\Models\Branch; // IMPORTANTE: Importar el modelo Branch
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserCredentials;

class UserController extends Controller
{
    /**
     * Muestra la lista de usuarios.
     */
    public function index()
    {
        // Traemos todos los usuarios con su cliente y sucursal
        $users = User::with(['client', 'branch'])->orderBy('created_at', 'desc')->paginate(10);
        
        // Datos para los selectores del modal
        $clients = Client::orderBy('company_name')->get();
        $branches = Branch::where('is_active', true)->orderBy('name')->get(); // NUEVO: Sucursales activas
        
        // LISTA COMPLETA DE MÓDULOS DEL SISTEMA
        $availableModules = [
            'dashboard'      => 'Panel de Control',
            'crm'            => 'CRM & Clientes',
            'products'       => 'Catálogo de Productos',
            'inventory'      => 'Inventario (Stock/Movimientos)',
            'infrastructure' => 'Infraestructura (Bodegas/Sucursales)',
            'maps'           => 'Infraestructura: Mapa y Cobertura',
            'receptions'     => 'Operaciones: Recepciones',
            'orders'         => 'Operaciones: Pedidos',
            'shipping'       => 'Operaciones: Envíos',
            'transfers'      => 'Operaciones: Transferencias',
            'rma'            => 'Operaciones: Devoluciones (RMA)',
            'billing'        => 'Facturación',
            'users'          => 'Seguridad y Usuarios',
            'settings'       => 'Configuración del Sistema'
        ];

        return view('admin.users.index', compact('users', 'clients', 'branches', 'availableModules'));
    }

    /**
     * Guarda un nuevo usuario.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|max:255|unique:users,email',
            'role'      => 'required|in:admin,manager,supervisor,operator,user',
            'client_id' => 'nullable|required_if:role,user|exists:clients,id',
            'branch_id' => 'nullable|exists:branches,id', // NUEVO: Validación de sucursal
            'status'    => 'required|in:active,inactive',
            'modules'   => 'nullable|array'
        ]);

        $generatedPassword = Str::random(12);

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($generatedPassword),
            'role'        => $validated['role'],
            'client_id'   => $validated['role'] === 'user' ? $validated['client_id'] : null,
            'branch_id'   => $validated['branch_id'] ?? null, // NUEVO: Asignar sucursal
            'status'      => $validated['status'],
            'permissions' => $validated['modules'] ?? [], 
        ]);

        // Enviar Correo con Credenciales
        try {
            $isClient = in_array($user->role, ['user', 'client']);
            Mail::to($user->email)->send(new UserCredentials($user, $generatedPassword, $isClient));
        } catch (\Exception $e) {
            \Log::error('SMTP Error al crear usuario: ' . $e->getMessage());
        }

        return redirect()->route('admin.users.index')
            ->with('success', "Usuario creado exitosamente.\n\nCREDENCIALES:\nEmail: {$validated['email']}\nContraseña: {$generatedPassword} (Enviada por correo)");
    }

    /**
     * Actualiza un usuario.
     */
    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role'      => 'required|in:admin,manager,supervisor,operator,user',
            'client_id' => 'nullable|required_if:role,user|exists:clients,id',
            'branch_id' => 'nullable|exists:branches,id', // NUEVO
            'status'    => 'required|in:active,inactive',
            'modules'   => 'nullable|array'
        ]);

        $user->update([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'role'        => $validated['role'],
            'client_id'   => $validated['role'] === 'user' ? $validated['client_id'] : null,
            'branch_id'   => $validated['branch_id'] ?? null, // NUEVO
            'status'      => $validated['status'],
            'permissions' => $validated['modules'] ?? [],
        ]);

        return redirect()->route('admin.users.index')->with('success', 'Perfil actualizado correctamente.');
    }

    /**
     * Elimina un usuario.
     */
    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }
        
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'Usuario eliminado.');
    }

    /**
     * Restablece contraseña.
     */
    public function resetPassword($id)
    {
        $user = User::findOrFail($id);
        $newPassword = Str::random(12);
        
        $user->update([
            'password' => Hash::make($newPassword)
        ]);

        try {
            $isClient = in_array($user->role, ['user', 'client']);
            Mail::to($user->email)->send(new UserCredentials($user, $newPassword, $isClient));
        } catch (\Exception $e) {
            \Log::error('SMTP Error al resetear password: ' . $e->getMessage());
            return redirect()->route('admin.users.index')
                ->with('warning', "Nueva contraseña generada: {$newPassword}, pero falló el envío del correo.");
        }

        return redirect()->route('admin.users.index')
            ->with('success', "Nueva contraseña generada para {$user->name}.\n\nCLAVE: {$newPassword} (Enviada por correo)");
    }
}