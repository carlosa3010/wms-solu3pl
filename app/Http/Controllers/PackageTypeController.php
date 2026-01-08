<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PackageType;
use App\Models\Client;

class PackageTypeController extends Controller
{
    public function index()
    {
        $packages = PackageType::with('client')
            ->orderByRaw('client_id IS NULL DESC')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.settings.packages.index', compact('packages'));
    }

    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        return view('admin.settings.packages.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'length' => 'required|numeric|min:0.1',
            'width' => 'required|numeric|min:0.1',
            'height' => 'required|numeric|min:0.1',
            'max_weight' => 'required|numeric|min:0',
            'empty_weight' => 'required|numeric|min:0',
            'client_id' => 'nullable|exists:clients,id'
        ]);

        PackageType::create($request->all());

        // CORRECCIÓN: Agregar prefijo 'admin.'
        return redirect()->route('admin.settings.packages.index')
            ->with('success', 'Tipo de caja creado correctamente.');
    }

    public function edit($id)
    {
        $package = PackageType::findOrFail($id);
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        
        return view('admin.settings.packages.edit', compact('package', 'clients'));
    }

    public function update(Request $request, $id)
    {
        $package = PackageType::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:100',
            'length' => 'required|numeric|min:0.1',
            'width' => 'required|numeric|min:0.1',
            'height' => 'required|numeric|min:0.1',
            'max_weight' => 'required|numeric|min:0',
            'empty_weight' => 'required|numeric|min:0',
            'client_id' => 'nullable|exists:clients,id'
        ]);

        $package->update($request->all());

        // CORRECCIÓN: Agregar prefijo 'admin.'
        return redirect()->route('admin.settings.packages.index')
            ->with('success', 'Tipo de caja actualizado correctamente.');
    }

    public function destroy($id)
    {
        $package = PackageType::findOrFail($id);
        $package->delete();
        
        return back()->with('success', 'Caja eliminada correctamente.');
    }
}