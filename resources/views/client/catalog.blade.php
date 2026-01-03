@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <!-- Encabezado con Botón -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Catálogo de Productos</h2>
            <p class="text-sm text-slate-500">Gestiona tus códigos SKU y sus especificaciones físicas.</p>
        </div>
        <button onclick="openCreateModal()" class="flex items-center justify-center space-x-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
            <i data-lucide="plus-circle" class="w-5 h-5"></i>
            <span>Nuevo SKU</span>
        </button>
    </div>

    <!-- Tabla de Productos -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Producto</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Identificadores</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Categoría</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Dimensiones & Peso</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Stock Físico</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($skus as $sku)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-12 h-12 bg-slate-100 border border-slate-200 rounded-lg flex items-center justify-center overflow-hidden">
                                    @if($sku->image_path)
                                        <img src="{{ asset('storage/' . $sku->image_path) }}" class="w-full h-full object-cover">
                                    @else
                                        <i data-lucide="package" class="w-6 h-6 text-slate-400"></i>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $sku->name }}</p>
                                    <p class="text-xs text-slate-400 line-clamp-1 max-w-[200px]">{{ $sku->description }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs font-bold text-blue-600">SKU: {{ $sku->sku }}</p>
                            <p class="text-[10px] text-slate-400 font-medium">EAN: {{ $sku->barcode ?? 'N/A' }}</p>
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-medium text-slate-600">
                            {{ $sku->category->name ?? 'General' }}
                        </td>
                        <td class="px-6 py-4 text-center text-xs">
                            <span class="font-bold text-slate-700 block">{{ number_format($sku->weight_kg, 2) }} kg</span>
                            <span class="text-[10px] text-slate-400 italic">
                                {{ $sku->length_cm ?? 0 }}x{{ $sku->width_cm ?? 0 }}x{{ $sku->height_cm ?? 0 }} cm
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-black {{ ($sku->total_stock ?? 0) > 0 ? 'bg-blue-50 text-blue-700' : 'bg-slate-100 text-slate-400' }}">
                                {{ number_format($sku->total_stock ?? 0) }} unds
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <!-- Botón Editar -->
                                <button onclick="openEditModal({{ json_encode($sku) }})" class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all" title="Editar Producto">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>

                                <!-- Botón Eliminar (Condicional al stock) -->
                                @if(($sku->total_stock ?? 0) <= 0)
                                <form action="{{ route('client.catalog.destroy', $sku->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este SKU? Esta acción es irreversible.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all" title="Eliminar SKU">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                @else
                                <button class="p-2 text-slate-200 cursor-not-allowed" title="No se puede eliminar: el almacén reporta stock de este producto">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                            <i data-lucide="search-x" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                            <p class="text-sm font-bold">No tienes productos registrados en tu catálogo.</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Unificado (Crear/Editar SKU) -->
<div id="modalSku" class="fixed inset-0 z-[60] hidden overflow-y-auto">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="flex items-center justify-between p-6 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
                        <i id="modalIcon" data-lucide="package" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="text-lg font-black text-slate-800 tracking-tight">Producto</h3>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Ficha Técnica</p>
                    </div>
                </div>
                <button onclick="closeModal()" class="text-slate-400 hover:text-slate-600 p-2">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form id="skuForm" action="{{ route('client.catalog.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <div id="methodField"></div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-wider">SKU <span class="text-rose-500">*</span></label>
                        <input type="text" name="sku" id="inputSku" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-wider">EAN / Código Barras</label>
                        <input type="text" name="barcode" id="inputBarcode" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-wider">Nombre del Producto <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="inputName" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-wider">Categoría <span class="text-rose-500">*</span></label>
                        <select name="category_id" id="inputCategory" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold bg-white">
                            <option value="" disabled selected>Elegir...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Atributos Logísticos Sincronizados -->
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-4">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-200 pb-2">Atributos Logísticos</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="text-[9px] font-bold text-slate-500 mb-1 block">Largo (cm)</label>
                            <input type="number" step="0.1" name="length_cm" id="inputLength" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-500 mb-1 block">Ancho (cm)</label>
                            <input type="number" step="0.1" name="width_cm" id="inputWidth" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-500 mb-1 block">Alto (cm)</label>
                            <input type="number" step="0.1" name="height_cm" id="inputHeight" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs focus:border-blue-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-500 mb-1 block">Peso (kg)</label>
                            <input type="number" step="0.01" name="weight_kg" id="inputWeight" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs focus:border-blue-500 outline-none font-black text-blue-600">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-wider">Imagen Referencial</label>
                    <input type="file" name="image" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition-all">
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-wider">Descripción del Producto</label>
                    <textarea name="description" id="inputDescription" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600"></textarea>
                </div>

                <div class="flex items-center space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="closeModal()" class="flex-1 py-3 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition-all">Cancelar</button>
                    <button type="submit" class="flex-[2] py-3 bg-blue-600 text-white rounded-xl font-black shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const modal = document.getElementById('modalSku');
    const form = document.getElementById('skuForm');
    const methodField = document.getElementById('methodField');

    function openCreateModal() {
        document.getElementById('modalTitle').innerText = "Registrar Nuevo Producto";
        form.action = "{{ route('client.catalog.store') }}";
        methodField.innerHTML = "";
        form.reset();
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        lucide.createIcons();
    }

    function openEditModal(sku) {
        document.getElementById('modalTitle').innerText = "Editar Producto";
        let updateUrl = "{{ route('client.catalog.update', ':id') }}";
        form.action = updateUrl.replace(':id', sku.id);
        
        methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';
        
        // Carga de datos dinámica
        document.getElementById('inputSku').value = sku.sku;
        document.getElementById('inputBarcode').value = sku.barcode || '';
        document.getElementById('inputName').value = sku.name;
        document.getElementById('inputCategory').value = sku.category_id;
        document.getElementById('inputLength').value = sku.length_cm || '';
        document.getElementById('inputWidth').value = sku.width_cm || '';
        document.getElementById('inputHeight').value = sku.height_cm || '';
        document.getElementById('inputWeight').value = sku.weight_kg || '';
        document.getElementById('inputDescription').value = sku.description || '';

        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
        lucide.createIcons();
    }

    function closeModal() {
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }
</script>
@endsection