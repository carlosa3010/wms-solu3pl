<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Muestra la lista de categorías.
     */
    public function index()
    {
        $categories = Category::withCount('products')->orderBy('name', 'asc')->paginate(10);
        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Guarda una nueva categoría.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Category::create($validated);

        return back()->with('success', 'Categoría logística creada exitosamente.');
    }

    /**
     * Actualiza una categoría existente.
     */
    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string|max:500',
        ]);

        $validated['slug'] = Str::slug($validated['name']);
        
        $category->update($validated);

        return back()->with('success', 'Categoría actualizada correctamente.');
    }

    /**
     * Elimina una categoría.
     */
    public function destroy($id)
    {
        $category = Category::findOrFail($id);
        
        // Verificamos si tiene productos asociados antes de borrar
        if ($category->products()->count() > 0) {
            return back()->withErrors(['error' => 'No se puede eliminar una categoría que tiene productos vinculados.']);
        }

        $category->delete();

        return back()->with('success', 'Categoría eliminada del sistema.');
    }
}