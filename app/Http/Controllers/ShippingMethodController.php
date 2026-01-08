<?php

namespace App\Http\Controllers;

use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use App\Models\State; // Importante
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShippingMethodController extends Controller
{
    public function index()
    {
        // Cargamos los métodos con sus tarifas y los estados disponibles
        $methods = ShippingMethod::with('rates.state')->get();
        $states = State::orderBy('name')->get(); // Para el selector del modal
        
        return view('admin.settings.shipping_methods_index', compact('methods', 'states'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        ShippingMethod::create([
            'name' => $request->name,
            'code' => Str::slug($request->name),
            'description' => $request->description,
            'is_active' => true,
        ]);

        return redirect()->back()->with('success', 'Método de envío creado.');
    }

    public function update(Request $request, $id)
    {
        $method = ShippingMethod::findOrFail($id);
        
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        $method->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->back()->with('success', 'Método actualizado.');
    }

    public function toggle($id)
    {
        $method = ShippingMethod::findOrFail($id);
        $method->is_active = !$method->is_active;
        $method->save();

        return redirect()->back()->with('success', 'Estado actualizado.');
    }

    public function destroy($id)
    {
        $method = ShippingMethod::findOrFail($id);
        $method->delete();
        return redirect()->back()->with('success', 'Método eliminado.');
    }

    // --- NUEVAS FUNCIONES PARA TARIFAS ---

    public function storeRate(Request $request, $id)
    {
        $request->validate([
            'state_id' => 'required|exists:states,id',
            'price' => 'required|numeric|min:0'
        ]);

        // Verificar si ya existe tarifa para ese estado y actualizarla o crearla
        ShippingRate::updateOrCreate(
            [
                'shipping_method_id' => $id,
                'state_id' => $request->state_id
            ],
            [
                'price' => $request->price
            ]
        );

        return redirect()->back()->with('success', 'Tarifa configurada correctamente.');
    }

    public function destroyRate($rateId)
    {
        $rate = ShippingRate::findOrFail($rateId);
        $rate->delete();
        return redirect()->back()->with('success', 'Tarifa eliminada.');
    }
}