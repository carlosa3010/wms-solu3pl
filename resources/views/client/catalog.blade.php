@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <!-- Encabezado con Botón -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Catálogo de Productos</h2>
            <p class="text-sm text-slate-500">Gestiona tus códigos SKU y solicita nuevos registros.</p>
        </div>
        <button onclick="toggleModal('modalNuevoSku')" class="flex items-center justify-center space-x-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
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
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Estado</th>
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
                                {{ $sku->length ?? 0 }}x{{ $sku->width ?? 0 }}x{{ $sku->height ?? 0 }} cm
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-emerald-100 text-emerald-700">
                                Activo
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-400">
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

<!-- Modal Flotante (Nuevo SKU) -->
<div id="modalNuevoSku" class="fixed inset-0 z-[60] hidden overflow-y-auto">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="toggleModal('modalNuevoSku')"></div>
    
    <!-- Modal Content -->
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="relative bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
            <div class="flex items-center justify-between p-6 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="plus-square" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-slate-800 tracking-tight">Registrar Nuevo Producto</h3>
                        <p class="text-xs text-slate-400 font-bold uppercase">Especificaciones Técnicas Completas</p>
                    </div>
                </div>
                <button onclick="toggleModal('modalNuevoSku')" class="text-slate-400 hover:text-slate-600 transition-colors p-2 hover:bg-slate-50 rounded-lg">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form action="{{ route('client.catalog.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-wider">Código SKU <span class="text-rose-500">*</span></label>
                        <input type="text" name="sku" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all font-bold text-slate-700" placeholder="Ej: WMS-2024-X">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-wider">Código de Barras (EAN)</label>
                        <input type="text" name="barcode" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all font-bold text-slate-700" placeholder="750123456789">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-wider">Nombre del Producto <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all font-bold text-slate-700" placeholder="Nombre descriptivo">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-wider">Categoría <span class="text-rose-500">*</span></label>
                        <select name="category_id" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all font-bold text-slate-700 appearance-none bg-white">
                            <option value="" disabled selected>Elegir...</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100 space-y-4">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-200 pb-2">Logística y Dimensiones (cm)</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div>
                            <label class="text-[9px] font-bold text-slate-500 mb-1 block">Largo</label>
                            <input type="number" step="0.1" name="length" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs outline-none focus:border-blue-500" placeholder="0.0">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-500 mb-1 block">Ancho</label>
                            <input type="number" step="0.1" name="width" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs outline-none focus:border-blue-500" placeholder="0.0">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-500 mb-1 block">Alto</label>
                            <input type="number" step="0.1" name="height" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs outline-none focus:border-blue-500" placeholder="0.0">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-500 mb-1 block">Peso (kg)</label>
                            <input type="number" step="0.01" name="weight_kg" class="w-full px-3 py-2 bg-white border border-slate-200 rounded-lg text-xs outline-none focus:border-blue-500 font-black text-blue-600" placeholder="0.00">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-wider">Foto del Producto</label>
                    <div class="flex items-center justify-center w-full">
                        <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-slate-200 border-dashed rounded-2xl cursor-pointer bg-slate-50 hover:bg-slate-100 transition-all">
                            <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                <i data-lucide="image-plus" class="w-8 h-8 text-slate-400 mb-2"></i>
                                <p class="text-xs text-slate-500 font-medium">Click para subir o arrastra la imagen</p>
                                <p class="text-[10px] text-slate-400 mt-1 uppercase font-bold">PNG, JPG (Max. 2MB)</p>
                            </div>
                            <input type="file" name="image" class="hidden" accept="image/*" />
                        </label>
                    </div>
                </div>

                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase mb-1.5 block tracking-wider">Descripción / Notas</label>
                    <textarea name="description" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none transition-all font-medium text-slate-600" placeholder="Notas sobre el manejo..."></textarea>
                </div>

                <div class="flex items-center space-x-3 pt-4 border-t border-slate-100">
                    <button type="button" onclick="toggleModal('modalNuevoSku')" class="flex-1 py-3 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition-all">
                        Cancelar
                    </button>
                    <button type="submit" class="flex-[2] py-3 bg-blue-600 text-white rounded-xl font-black shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all">
                        Registrar Producto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal.classList.contains('hidden')) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        } else {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }
</script>
@endsection