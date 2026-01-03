<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentMethodController extends Controller
{
    /**
     * Listar métodos de pago
     */
    public function index()
    {
        $methods = PaymentMethod::all();
        return view('admin.settings.payment_methods_index', compact('methods'));
    }

    /**
     * Guardar nuevo método
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'details' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);

        PaymentMethod::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'details' => $request->details,
            'instructions' => $request->instructions,
            'is_active' => true,
        ]);

        return redirect()->route('admin.payment_methods.index')->with('success', 'Método de pago creado exitosamente.');
    }

    /**
     * Actualizar método existente
     */
    public function update(Request $request, $id)
    {
        $method = PaymentMethod::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'details' => 'nullable|string',
            'instructions' => 'nullable|string',
        ]);

        $method->update([
            'name' => $request->name,
            'details' => $request->details,
            'instructions' => $request->instructions,
        ]);

        return redirect()->route('admin.payment_methods.index')->with('success', 'Método actualizado correctamente.');
    }

    /**
     * Activar / Desactivar
     */
    public function toggle($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->is_active = !$method->is_active;
        $method->save();

        return redirect()->route('admin.payment_methods.index')->with('success', 'Estado del método actualizado.');
    }
    
    /**
     * Eliminar método
     */
    public function destroy($id)
    {
        $method = PaymentMethod::findOrFail($id);
        $method->delete();
        
        return redirect()->route('admin.payment_methods.index')->with('success', 'Método eliminado.');
    }
}