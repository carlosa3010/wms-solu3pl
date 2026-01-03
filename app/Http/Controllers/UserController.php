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
    public function index()
    {
        $users = User::with('client')->latest()->paginate(15);
        $clients = Client::orderBy('company_name')->get();
        
        $availableModules = [
            'dashboard' => 'Dashboard',
            'clients'   => 'Clientes',
            'crm'       => 'CRM / Leads',
            'products'  => 'Catálogo',
            'inventory' => 'Inventario',
            'operations'=> 'Operaciones',
            'billing'   => 'Finanzas',
            'settings'  => 'Ajustes'
        ];

        return view('admin.users.index', compact('users', 'clients', 'availableModules'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required|in:admin,operator,manager,client',
            'client_id'=> 'required_if:role,client|nullable|exists:clients,id',
        ]);

        $password = Str::random(12);

        $user = User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($password),
            'role'        => $request->role,
            'client_id'   => $request->role === 'client' ? $request->client_id : null,
            'permissions' => $request->role === 'manager' ? $request->modules : null,
        ]);

        return redirect()->back()->with('success', "ACCESO CREADO -> Usuario: {$user->email} | Password: {$password}");
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'role'  => 'required|in:admin,operator,manager,client',
        ]);

        $user->update([
            'name'        => $request->name,
            'email'       => $request->email,
            'role'        => $request->role,
            'client_id'   => $request->role === 'client' ? $request->client_id : null,
            'permissions' => $request->role === 'manager' ? $request->modules : null,
        ]);

        return redirect()->back()->with('success', "Datos de {$user->name} actualizados.");
    }

    public function resetPassword(User $user)
    {
        $newPassword = Str::random(12);
        $user->update(['password' => Hash::make($newPassword)]);
        return redirect()->back()->with('success', "CLAVE RESTABLECIDA -> Usuario: {$user->email} | Nueva Clave: {$newPassword}");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) return back()->with('error', 'No puedes eliminarte a ti mismo.');
        $user->delete();
        return back()->with('success', 'Usuario eliminado.');
    }
}