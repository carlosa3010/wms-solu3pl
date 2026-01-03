<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class ClientController extends Controller
{
    /**
     * Muestra la lista de clientes.
     */
    public function index()
    {
        $clients = Client::latest()->paginate(10);
        return view('admin.clients.index', compact('clients'));
    }

    /**
     * Muestra el formulario para crear un nuevo cliente.
     */
    public function create()
    {
        return view('admin.clients.create');
    }

    /**
     * Almacena un nuevo cliente y su usuario de acceso al portal.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'tax_id'       => 'required|string|unique:clients,tax_id',
            'contact_name' => 'required|string|max:255',
            'email'        => 'required|email|unique:clients,email|unique:users,email',
            'password'     => 'required|min:8',
            'phone'        => 'nullable|string',
            'address'      => 'nullable|string',
            'billing_type' => 'required|in:prepaid,postpaid',
            'logo'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            // 1. Manejo del Logo (si existe)
            $logoUrl = null;
            if ($request->hasFile('logo')) {
                $logoUrl = $request->file('logo')->store('logos', 'public');
            }

            // 2. Crear el Cliente (La tabla 'clients' NO tiene columna 'password')
            $client = Client::create([
                'company_name' => $validated['company_name'],
                'tax_id'       => $validated['tax_id'],
                'contact_name' => $validated['contact_name'],
                'email'        => $validated['email'],
                'phone'        => $validated['phone'],
                'address'      => $validated['address'],
                'billing_type' => $validated['billing_type'],
                'logo_url'     => $logoUrl,
                'is_active'    => true,
            ]);

            // 3. Crear el Usuario de acceso vinculado con el rol 'user'
            User::create([
                'name'      => $validated['contact_name'],
                'email'     => $validated['email'],
                'password'  => Hash::make($validated['password']),
                'client_id' => $client->id,
                'role'      => 'user', // Rol programado para acceso de clientes
            ]);

            return redirect()->route('admin.clients.index')
                ->with('success', 'Cliente y usuario de acceso creados correctamente.');
        });
    }

    /**
     * Muestra el formulario de edición.
     */
    public function edit(Client $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    /**
     * Actualiza la información del cliente.
     */
    public function update(Request $request, Client $client)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255',
            'tax_id'       => 'required|string|unique:clients,tax_id,' . $client->id,
            'contact_name' => 'required|string|max:255',
            'email'        => 'required|email|unique:clients,email,' . $client->id,
            'phone'        => 'nullable|string',
            'address'      => 'nullable|string',
            'billing_type' => 'required|in:prepaid,postpaid',
            'logo'         => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($request->hasFile('logo')) {
            if ($client->logo_url) {
                Storage::disk('public')->delete($client->logo_url);
            }
            $validated['logo_url'] = $request->file('logo')->store('logos', 'public');
        }

        $client->update($validated);

        // Opcional: Sincronizar email/nombre en la tabla users si cambiaron
        $client->users()->update([
            'email' => $validated['email'],
            'name'  => $validated['contact_name']
        ]);

        return redirect()->route('admin.clients.index')->with('success', 'Cliente actualizado correctamente.');
    }

    /**
     * Elimina al cliente (Soft Delete).
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('admin.clients.index')->with('success', 'Cliente eliminado correctamente.');
    }

    /**
     * Reiniciar contraseña de los usuarios vinculados al cliente.
     */
    public function resetPassword(Client $client)
    {
        $newPass = 'cliente' . date('Y'); 
        
        // El método resetPassword está definido en el modelo Client del Canvas
        $client->resetPassword($newPass);

        return redirect()->back()->with('success', "Contraseña de {$client->company_name} restablecida a: {$newPass}");
    }

    /**
     * Cambiar el estado de suspensión del cliente.
     */
    public function toggleStatus(Client $client)
    {
        if ($client->isActive()) {
            $client->suspend();
            $statusMsg = "Cliente {$client->company_name} suspendido correctamente.";
        } else {
            $client->activate();
            $statusMsg = "Cliente {$client->company_name} reactivado correctamente.";
        }

        return redirect()->back()->with('info', $statusMsg);
    }
}