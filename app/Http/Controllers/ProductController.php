<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Client;
use App\Models\Category;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Listado maestro de SKUs con filtros.
     */
    public function index(Request $request)
    {
        $query = Product::with(['client', 'category']);

        // Búsqueda por SKU o Nombre
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Filtro por Cliente
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $products = $query->orderBy('name')->paginate(15);
        $clients = Client::orderBy('company_name')->get();

        return view('admin.products.index', compact('products', 'clients'));
    }

    /**
     * Formulario de creación de nuevo SKU.
     */
    public function create()
    {
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        $categories = Category::orderBy('name')->get();
        
        return view('admin.products.create', compact('clients', 'categories'));
    }

    /**
     * Almacena el producto validando las dimensiones logísticas.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id'   => 'required|exists:clients,id',
            'category_id' => 'nullable|exists:categories,id',
            'sku'         => 'required|string|unique:products,sku|max:50',
            'name'        => 'required|string|max:255',
            'barcode'     => 'nullable|string|max:100',
            'weight_kg'   => 'required|numeric|min:0',
            'length_cm'   => 'required|numeric|min:0',
            'width_cm'    => 'required|numeric|min:0',
            'height_cm'   => 'required|numeric|min:0',
            'image'       => 'nullable|image|max:2048', // Max 2MB
        ]);

        // Procesar imagen si existe
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $validated['is_active'] = true;

        Product::create($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto registrado exitosamente en el catálogo maestro.');
    }

    /**
     * Formulario de edición.
     */
    public function edit($id)
    {
        $product = Product::findOrFail($id);
        $clients = Client::where('is_active', true)->orderBy('company_name')->get();
        $categories = Category::orderBy('name')->get();

        return view('admin.products.edit', compact('product', 'clients', 'categories'));
    }

    /**
     * Actualiza los datos del SKU.
     */
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'sku'         => 'required|string|max:50|unique:products,sku,' . $product->id,
            'name'        => 'required|string|max:255',
            'barcode'     => 'nullable|string|max:100',
            'weight_kg'   => 'required|numeric|min:0',
            'length_cm'   => 'required|numeric|min:0',
            'width_cm'    => 'required|numeric|min:0',
            'height_cm'   => 'required|numeric|min:0',
            'image'       => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');
            $validated['image_url'] = Storage::url($path);
        }

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Información del producto actualizada correctamente.');
    }

    /**
     * Eliminación lógica (SoftDelete).
     */
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return back()->with('success', 'El producto ha sido retirado del catálogo activo.');
    }
}