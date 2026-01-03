@extends('layouts.admin')

@section('title', 'Categorías Logísticas')
@section('header_title', 'Configuración de Sistema')

@section('content')
    <!-- Cabecera de Módulo -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Clasificación de Inventario</h2>
            <p class="text-slate-500 text-sm">Organice su catálogo de productos mediante categorías logísticas personalizadas.</p>
        </div>
    </div>

    <!-- Mensajes de Retroalimentación -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm animate-fade-in-down flex items-center gap-3">
            <i class="fa-solid fa-check-circle text-custom-primary"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm flex items-center gap-3">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span class="text-sm font-medium">{{ $errors->first() }}</span>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Formulario de Acción (Izquierda) -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden sticky top-24 transition-all duration-300">
                <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                    <h3 id="form-title" class="font-bold text-slate-700 text-[10px] uppercase tracking-widest flex items-center gap-2">
                        <i class="fa-solid fa-folder-plus text-custom-primary"></i> Nueva Categoría
                    </h3>
                </div>
                
                <form id="category-form" action="{{ route('admin.categories.store') }}" method="POST" class="p-6">
                    @csrf
                    <input type="hidden" name="_method" id="form-method" value="POST">
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nombre de la Categoría *</label>
                            <input type="text" name="name" id="cat-name" required 
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 ring-custom-primary border-custom-primary outline-none text-sm transition bg-white shadow-sm"
                                   placeholder="Ej: Electrónica, Alimentos...">
                        </div>
                        
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Descripción / Notas</label>
                            <textarea name="description" id="cat-desc" rows="3" 
                                      class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 ring-custom-primary border-custom-primary outline-none text-sm transition bg-white shadow-sm"
                                      placeholder="Describa el uso o restricciones de esta categoría..."></textarea>
                        </div>

                        <div class="pt-2 flex flex-col gap-2">
                            <button type="submit" id="submit-btn" class="w-full bg-custom-primary text-white py-2.5 rounded-lg font-bold text-xs shadow-md hover-bg-primary transition uppercase tracking-tighter">
                                Guardar Categoría
                            </button>
                            <button type="button" onclick="resetForm()" id="cancel-btn" class="hidden w-full py-2.5 bg-slate-100 text-slate-500 rounded-lg font-bold text-xs hover:bg-slate-200 transition uppercase tracking-tighter">
                                <i class="fa-solid fa-xmark mr-1"></i> Cancelar Edición
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Listado Maestro (Derecha) -->
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-4">Identificación / Slug</th>
                                <th class="px-6 py-4">Descripción</th>
                                <th class="px-6 py-4 text-center">Impacto (SKUs)</th>
                                <th class="px-6 py-4 text-right">Gestión</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($categories as $category)
                            <tr class="hover:bg-slate-50/80 transition group">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-700 group-hover:text-custom-primary transition">{{ $category->name }}</div>
                                    <div class="text-[9px] font-mono text-slate-400">/{{ $category->slug }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs text-slate-500 max-w-xs truncate">{{ $category->description ?? 'Sin descripción técnica registrada.' }}</p>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="bg-slate-100 text-slate-600 px-2.5 py-0.5 rounded-full text-[10px] font-bold border border-slate-200">
                                        {{ $category->products_count }} <span class="hidden md:inline">unid.</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end gap-1">
                                        <!-- Botón Editar (Carga datos en form izquierdo) -->
                                        <button onclick="editCategory({{ json_encode($category) }})" class="p-2 text-slate-400 hover:text-custom-primary transition hover:bg-slate-100 rounded-lg" title="Editar Categoría">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>

                                        <!-- Formulario de Eliminación -->
                                        <form action="{{ route('admin.categories.destroy', $category->id) }}" method="POST" onsubmit="return confirm('¿Seguro que desea eliminar esta categoría? Solo se permite si el contador de SKUs es cero.')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-2 text-slate-400 hover:text-red-600 transition hover:bg-red-50 rounded-lg" title="Eliminar">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-20 text-center">
                                    <div class="flex flex-col items-center opacity-40">
                                        <i class="fa-solid fa-folder-open text-5xl mb-3"></i>
                                        <p class="font-bold text-slate-500">No hay categorías configuradas</p>
                                        <p class="text-xs">Registre clasificaciones para organizar su inventario.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <!-- Paginación con estilos de Laravel -->
                <div class="p-4 bg-slate-50 border-t border-slate-100">
                    {{ $categories->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    /**
     * Prepara el formulario para editar una categoría existente
     * No requiere recargar la página (UX mejorada)
     */
    function editCategory(category) {
        const form = document.getElementById('category-form');
        const title = document.getElementById('form-title');
        const submitBtn = document.getElementById('submit-btn');
        const methodInput = document.getElementById('form-method');
        const cancelBtn = document.getElementById('cancel-btn');

        // Cambiar destino del formulario y método HTTP
        form.action = `/admin/categories/${category.id}`;
        methodInput.value = 'PUT';

        // Llenar los campos con los datos del registro
        document.getElementById('cat-name').value = category.name;
        document.getElementById('cat-desc').value = category.description || '';

        // Actualizar interfaz visual
        title.innerHTML = '<i class="fa-solid fa-pen-to-square text-custom-primary"></i> Editando Categoría';
        submitBtn.innerHTML = '<i class="fa-solid fa-rotate mr-1"></i> Actualizar Cambios';
        cancelBtn.classList.remove('hidden');

        // Scroll suave al formulario en móviles
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /**
     * Restablece el formulario a su estado de "Nueva Categoría"
     */
    function resetForm() {
        const form = document.getElementById('category-form');
        const title = document.getElementById('form-title');
        const submitBtn = document.getElementById('submit-btn');
        const methodInput = document.getElementById('form-method');
        const cancelBtn = document.getElementById('cancel-btn');

        form.action = "{{ route('admin.categories.store') }}";
        methodInput.value = 'POST';
        form.reset();

        title.innerHTML = '<i class="fa-solid fa-folder-plus text-custom-primary"></i> Nueva Categoría';
        submitBtn.innerHTML = 'Guardar Categoría';
        cancelBtn.classList.add('hidden');
    }
</script>
@endsection

@section('styles')
<style>
    .animate-fade-in-down {
        animation: fadeInDown 0.4s ease-out forwards;
    }
    @keyframes fadeInDown {
        0% { opacity: 0; transform: translateY(-10px); }
        100% { opacity: 1; transform: translateY(0); }
    }
</style>
@endsection