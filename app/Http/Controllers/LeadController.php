<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\Client;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class LeadController extends Controller
{
    /**
     * Listado de prospectos con filtros básicos.
     */
    public function index(Request $request)
    {
        $leads = Lead::latest()->paginate(10);
        return view('admin.crm.index', compact('leads'));
    }

    /**
     * Registro de un nuevo prospecto.
     */
    public function store(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:255',
            'contact_name' => 'required|string|max:255',
            'email'        => 'required|email|unique:leads,email',
            'phone'        => 'nullable|string',
        ]);

        Lead::create($request->all() + ['status' => 'new']);

        return redirect()->back()->with('success', 'Prospecto registrado en el CRM correctamente.');
    }

    /**
     * Lógica Maestra: Convertir Lead en Cliente Real.
     */
    public function convertToClient($id)
    {
        $lead = Lead::findOrFail($id);

        return DB::transaction(function () use ($lead) {
            // 1. Generar clave aleatoria para el nuevo cliente
            $randomPassword = Str::random(12);

            // 2. Crear el Cliente usando los datos del Lead
            $client = Client::create([
                'company_name' => $lead->company_name,
                'contact_name' => $lead->contact_name,
                'email'        => $lead->email,
                'phone'        => $lead->phone,
                'is_active'    => true,
                'tax_id'       => 'PENDIENTE', // Se debe editar luego
            ]);

            // 3. Crear el Usuario de acceso al Portal
            User::create([
                'name'      => $lead->contact_name,
                'email'     => $lead->email,
                'password'  => Hash::make($randomPassword),
                'client_id' => $client->id,
                'role'      => 'client',
            ]);

            // 4. Marcar Lead como convertido y eliminarlo (o conservarlo con status 'converted')
            $lead->update(['status' => 'converted']);
            $lead->delete(); // SoftDelete

            // Redirigir al listado de clientes mostrando las nuevas credenciales
            return redirect()->route('admin.clients.index')
                ->with('success', "¡CONVERSIÓN EXITOSA! Se ha creado el cliente {$client->company_name}. ACCESO AL PORTAL -> Usuario: {$client->email} | Contraseña: {$randomPassword}");
        });
    }

    public function destroy(Lead $lead)
    {
        $lead->delete();
        return redirect()->back()->with('success', 'Prospecto eliminado del CRM.');
    }
}