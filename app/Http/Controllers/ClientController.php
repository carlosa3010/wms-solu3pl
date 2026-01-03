<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\UserCredentials;

class ClientController extends Controller
{
    /**
     * Muestra la lista de clientes con sus usuarios vinculados.
     */
    public function index(Request $request)
    {
        $query = Client::with('users'); // Eager loading

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('company_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('contact_name', 'like', "%{$search}%");
            });
        }

        $clients = $query->latest()->paginate(10);
        return view('admin.clients.index', compact('clients'));
    }

    /**
     * Formulario de creación.
     */
    public function create()
    {
        return view('admin.clients.create');
    }

    /**
     * Almacena el cliente y genera acceso automático al portal.
     */
    public function store(Request $request)
    {
        // Validamos solo los campos operativos necesarios
        $request->validate([
            'company_name' => 'required|string|max:255',
            'tax_id'       => 'required|string|unique:clients,tax_id',
            'contact_name' => 'required|string|max:255',
            'email'        => 'required|email|unique:clients,email|unique:users,email',
            'phone'        => 'nullable|string',
            'address'      => 'nullable|string',
        ]);

        $randomPassword = Str::random(12);

        return DB::transaction(function () use ($request, $randomPassword) {
            
            // 1. Crear el Cliente (Nombres coincidentes con el Canvas)
            $client = Client::create([
                'company_name' => $request->company_name,
                'tax_id'       => $request->tax_id,
                'contact_name' => $request->contact_name,
                'email'        => $request->email,
                'phone'        => $request->phone,
                'address'      => $request->address,
                'is_active'    => true,
            ]);

            // 2. Crear el Usuario de acceso al Portal (Login con Email)
            $user = User::create([
                'name'      => $request->contact_name,
                'email'     => $request->email,
                'password'  => Hash::make($randomPassword),
                'client_id' => $client->id,
                'role'      => 'client',
            ]);

            // 3. Enviar Correo con Credenciales
            try {
                Mail::to($client->email)->send(new UserCredentials($user, $randomPassword, true));
            } catch (\Exception $e) {
                // Si falla el correo, no detenemos el proceso, solo logueamos el error
                \Log::error('Error enviando credenciales a cliente: ' . $e->getMessage());
            }

            // Mensaje de éxito formateado para el cuadro verde del listado
            return redirect()->route('admin.clients.index')
                ->with('success', "CLIENTE REGISTRADO -> Usuario: {$request->email} | Contraseña: {$randomPassword}");
        });
    }

    /**
     * Formulario de edición.
     */
    public function edit(Client $client)
    {
        return view('admin.clients.edit', compact('client'));
    }

    /**
     * Actualiza la información operativa del cliente.
     */
    public function update(Request $request, Client $client)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'tax_id'       => 'required|string|unique:clients,tax_id,' . $client->id,
            'contact_name' => 'required|string|max:255',
            'email'        => 'required|email|unique:clients,email,' . $client->id,
        ]);

        // Actualización masiva segura (excluyendo campos de finanzas)
        $client->update($request->only([
            'company_name', 'tax_id', 'contact_name', 'email', 'phone', 'address'
        ]));

        // Sincronizar credenciales de usuario vinculadas al cliente
        User::where('client_id', $client->id)->update([
            'email' => $request->email,
            'name'  => $request->contact_name
        ]);

        return redirect()->route('admin.clients.index')->with('success', 'Información del cliente actualizada correctamente.');
    }

    /**
     * Eliminación de cliente.
     * El borrado de usuarios es automático por el método booted() definido en el Canvas.
     */
    public function destroy(Client $client)
    {
        $client->delete();
        return redirect()->route('admin.clients.index')->with('success', 'Cliente y accesos eliminados del sistema.');
    }

    /**
     * Genera una nueva contraseña aleatoria y la muestra en el listado.
     */
    public function resetPassword(Client $client)
    {
        $newPass = 'solu' . Str::lower(Str::random(4)) . date('Y');

        foreach ($client->users as $user) {
            $user->password = Hash::make($newPass);
            $user->save();

            // Enviar correo con la nueva contraseña
            try {
                Mail::to($user->email)->send(new UserCredentials($user, $newPass, true));
            } catch (\Exception $e) {
                \Log::error('Error reenviando credenciales: ' . $e->getMessage());
                return redirect()->back()->with('warning', "Clave cambiada a {$newPass}, pero falló el envío del correo.");
            }
        }

        return redirect()->back()->with('success', "Acceso restablecido para {$client->company_name}. Nueva clave: {$newPass} (Enviada por correo)");
    }

    /**
     * Alternar estado de acceso (is_active).
     * Si está suspendido (false), el middleware de autenticación debe impedir el login.
     */
    public function toggleStatus(Client $client)
    {
        $client->is_active = !$client->is_active;
        $client->save();

        $statusText = $client->is_active ? 'HABILITADO' : 'SUSPENDIDO';
        return redirect()->back()->with('info', "El portal del cliente {$client->company_name} ha sido {$statusText}.");
    }
}