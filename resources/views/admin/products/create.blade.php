@extends('layouts.admin')

@section('title', 'Nuevo SKU')
@section('header_title', 'Gestión de Inventario')

@section('content')
    <!-- Navegación y Título -->
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-slate-800">Registrar Nuevo Producto</h2>
        <nav class="flex items-center text-sm text-slate-500 mt-1">
            <a href="{{ route('admin.products.index') }}" class="hover:text-custom-primary transition">Inventario</a>
            <i class="fa-solid fa-chevron-right text-[10px] mx-2"></i>
            <span class="font-bold text-slate-700">Crear SKU</span>
        </nav>
    </div>

    <!-- Gestión de Errores -->
    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm animate-fade-in-down">
            <p class="font-bold text-sm mb-1"><i class="fa-solid fa-circle-exclamation mr-2"></i> Errores de validación:</p>
            <ul class="list-disc list-inside text-xs">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.products.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- COLUMNA IZQUIERDA: Datos Maestros y Volumetría -->
            <div class="lg:col-span-2 space-y-6">
                
                <!-- Datos de Identidad -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-bold text-slate-700 flex items-center gap-2 italic uppercase text-[10px] tracking-widest">
                            <i class="fa-solid fa-file-invoice text-custom-primary"></i> Información del Producto
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Cliente / Propietario del Stock *</label>
                            <select name="client_id" required class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 ring-custom-primary border-custom-primary outline-none bg-white shadow-sm font-medium">
                                <option value="">Seleccione el propietario...</option>
                                @foreach($clients as $client)
                                    <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                        {{ $client->company_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Código SKU *</label>
                            <input type="text" name="sku" required value="{{ old('sku') }}" 
                                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 ring-custom-primary border-custom-primary outline-none font-mono uppercase" 
                                   placeholder="EJ: SKU-1001">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Código de Barras (EAN/UPC)</label>
                            <input type="text" name="barcode" value="{{ old('barcode') }}" 
                                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 ring-custom-primary border-custom-primary outline-none font-mono" 
                                   placeholder="759123456789">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Nombre Comercial del Producto *</label>
                            <input type="text" name="name" required value="{{ old('name') }}" 
                                   class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 ring-custom-primary border-custom-primary outline-none">
                        </div>

                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Descripción Detallada</label>
                            <textarea name="description" rows="3" 
                                      class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 ring-custom-primary border-custom-primary outline-none">{{ old('description') }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Volumetría -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-bold text-slate-700 flex items-center gap-2 italic uppercase text-[10px] tracking-widest">
                            <i class="fa-solid fa-ruler-combined text-custom-primary"></i> Volumetría y Pesaje
                        </h3>
                    </div>
                    <div class="p-6 grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Peso Neto (Kg) *</label>
                            <input type="number" step="0.001" name="weight_kg" value="{{ old('weight_kg', '0.000') }}" 
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 ring-custom-primary outline-none font-mono text-right">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Largo (cm)</label>
                            <input type="number" step="0.1" name="length_cm" value="{{ old('length_cm', '0.0') }}" 
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 ring-custom-primary outline-none font-mono text-right">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Ancho (cm)</label>
                            <input type="number" step="0.1" name="width_cm" value="{{ old('width_cm', '0.0') }}" 
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 ring-custom-primary outline-none font-mono text-right">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Alto (cm)</label>
                            <input type="number" step="0.1" name="height_cm" value="{{ old('height_cm', '0.0') }}" 
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 ring-custom-primary outline-none font-mono text-right">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Stock Mín.</label>
                            <input type="number" name="min_stock_level" value="{{ old('min_stock_level', '5') }}" 
                                   class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 ring-custom-primary outline-none font-mono text-right">
                        </div>
                    </div>
                </div>
            </div>

            <!-- COLUMNA DERECHA: Imagen y Categoría -->
            <div class="lg:col-span-1 space-y-6">
                <!-- Identificación Visual -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="font-bold text-slate-700 flex items-center gap-2 italic uppercase text-[10px] tracking-widest">
                            <i class="fa-solid fa-image text-custom-primary"></i> Foto del Producto
                        </h3>
                    </div>
                    <div class="p-6">
                        <div id="imagePreview" class="w-full aspect-square bg-slate-100 rounded-lg border-2 border-dashed border-slate-300 mb-4 flex items-center justify-center overflow-hidden text-slate-400">
                            <i class="fa-solid fa-cloud-arrow-up text-4xl"></i>
                        </div>
                        <input type="file" name="product_image" id="productImageInput" 
                               class="w-full text-[10px] text-slate-500 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-custom-primary file:text-white hover:file:opacity-80 cursor-pointer">
                    </div>
                </div>

                <!-- Categorización -->
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Categoría Logística</label>
                    <select name="category_id" class="w-full px-4 py-2 border border-slate-300 rounded-lg outline-none font-medium focus:ring-2 ring-custom-primary border-custom-primary">
                        <option value="">Seleccione categoría...</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Botones de Acción -->
                <div class="flex flex-col gap-3">
                    <button type="submit" class="w-full bg-custom-primary text-white py-3 rounded-xl font-bold shadow-lg shadow-black/10 hover-bg-primary transition flex items-center justify-center gap-2 uppercase tracking-tighter text-sm">
                        <i class="fa-solid fa-save"></i> Registrar en Catálogo
                    </button>
                    <a href="{{ route('admin.products.index') }}" class="w-full bg-white text-slate-500 py-3 rounded-xl font-bold border border-slate-200 text-center hover:bg-slate-50 transition text-sm">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('scripts')
<script>
    // Vista previa de imagen antes de subir
    document.getElementById('productImageInput').onchange = evt => {
        const [file] = document.getElementById('productImageInput').files
        if (file) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = `<img src="${URL.createObjectURL(file)}" class="w-full h-full object-cover">`;
        }
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