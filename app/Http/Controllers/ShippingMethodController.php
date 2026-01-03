<?php

namespace App\Http\Controllers;

use App\Models\ShippingMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShippingMethodController extends Controller
{
    public function index()
    {
        $methods = ShippingMethod::all();
        return view('admin.settings.shipping_methods_index', compact('methods'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
        ]);

        ShippingMethod::create([
            'name' => $request->name,
            'code' => Str::slug($request->name), // Genera 'envio-express' de 'Envío Express'
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
}