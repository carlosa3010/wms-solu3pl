<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BinType;

class BinTypeController extends Controller
{
    public function index()
    {
        $binTypes = BinType::all();
        return view('admin.settings.bin_types', compact('binTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|unique:bin_types,code|max:20',
            'length' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:0',
            'height' => 'required|numeric|min:0',
            'max_weight' => 'required|numeric|min:0',
        ]);

        BinType::create($validated);
        return back()->with('success', 'Tipo de bin creado.');
    }

    public function destroy($id)
    {
        BinType::destroy($id);
        return back()->with('success', 'Tipo de bin eliminado.');
    }
}