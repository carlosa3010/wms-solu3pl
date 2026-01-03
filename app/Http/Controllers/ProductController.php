<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Client;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::with(['client', 'category']);

        // Filtro por búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Filtro por cliente
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        $products = $query->latest()->paginate(15);
        
        // Obtenemos todos los clientes ordenados por ID descendente para ver los NUEVOS primero
        $clients = Client::orderBy('id', 'desc')->get();

        return view('admin.products.index', compact('products', 'clients'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Ordenamos por ID desc para que los clientes recién creados aparezcan al principio del select
        $clients = Client::orderBy('id', 'desc')->get();
        $categories = Category::orderBy('name')->get();
        return view('admin.products.create', compact('clients', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'client_id'      => 'required|exists:clients,id',
            'category_id'    => 'required|exists:categories,id',
            'sku'            => 'required|unique:products,sku',
            'name'           => 'required|string|max:255',
            'weight_kg'      => 'nullable|numeric|min:0',
            'length_cm'      => 'nullable|numeric|min:0',
            'width_cm'       => 'nullable|numeric|min:0',
            'height_cm'      => 'nullable|numeric|min:0',
            'min_stock_level'=> 'nullable|integer|min:0',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        // Usamos only() para asegurarnos de NO enviar 'is_active' si la columna no existe en la DB
        $data = $request->only([
            'client_id', 'category_id', 'sku', 'name', 'barcode', 
            'description', 'weight_kg', 'length_cm', 'width_cm', 
            'height_cm', 'min_stock_level'
        ]);
        
        // Manejo de la imagen
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        Product::create($data);

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        $clients = Client::orderBy('id', 'desc')->get();
        $categories = Category::orderBy('name')->get();
        return view('admin.products.edit', compact('product', 'clients', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'client_id'      => 'required|exists:clients,id',
            'category_id'    => 'required|exists:categories,id',
            'sku'            => 'required|unique:products,sku,' . $product->id,
            'name'           => 'required|string|max:255',
            'weight_kg'      => 'nullable|numeric|min:0',
            'length_cm'      => 'nullable|numeric|min:0',
            'width_cm'       => 'nullable|numeric|min:0',
            'height_cm'      => 'nullable|numeric|min:0',
            'image'          => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->only([
            'client_id', 'category_id', 'sku', 'name', 'barcode', 
            'description', 'weight_kg', 'length_cm', 'width_cm', 
            'height_cm', 'min_stock_level'
        ]);

        if ($request->hasFile('image')) {
            if ($product->image_path) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto actualizado correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Producto eliminado.');
    }
}