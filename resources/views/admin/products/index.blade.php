@extends('layouts.admin')

@section('title', 'Catálogo maestro')
@section('header_title', 'Catálogo de SKUs')

@section('content')

    <!-- Encabezado de Sección y Botón de Creación -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Catálogo de Productos</h2>
            <p class="text-sm text-slate-500">Gestione los SKUs, dimensiones y pesos de la mercancía de sus clientes.</p>
        </div>
        <a href="{{ route('admin.products.create') }}" class="bg-custom-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:brightness-95 transition flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Nuevo Producto
        </a>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form action="{{ route('admin.products.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            
            <div class="md:col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Búsqueda de SKU o Nombre</label>
                <div class="relative group">
                    <span class="absolute left-3 top-2.5 text-slate-400 group-focus-within:text-custom-primary transition">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none transition" 
                           placeholder="Ej: NK-TSHIRT-S...">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dueño (Cliente)</label>
                <select name="client_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm outline-none bg-white">
                    <option value="">Todos los Clientes</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="bg-custom-primary text-white px-4 py-2 rounded-lg font-bold text-sm hover:brightness-95 transition flex-1">
                    Filtrar
                </button>
                <a href="{{ route('admin.products.index') }}" class="bg-slate-100 text-slate-500 px-3 py-2 rounded-lg hover:bg-slate-200 transition">
                    <i class="fa-solid fa-filter-circle-xmark"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla de Productos -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Producto / Catálogo</th>
                        <th class="px-6 py-4">Categoría</th>
                        <th class="px-6 py-4">Cliente (Dueño)</th>
                        <th class="px-6 py-4 text-center">Dimensiones / Peso</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($products as $product)
                        <tr class="hover:bg-slate-50/50 transition group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-300 border border-slate-200 overflow-hidden">
                                        @if($product->image_url)
                                            <img src="{{ $product->image_url }}" class="w-full h-full object-cover">
                                        @else
                                            <i class="fa-solid fa-barcode text-lg"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-700 group-hover:text-custom-primary transition">{{ $product->name }}</p>
                                        <p class="text-[10px] text-slate-400 font-mono font-bold uppercase tracking-tighter">SKU: {{ $product->sku }}</p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                @if($product->category)
                                    <span class="bg-blue-50 text-blue-600 px-2 py-1 rounded text-[10px] font-black uppercase border border-blue-100">
                                        {{ $product->category->name }}
                                    </span>
                                @else
                                    <span class="text-[10px] text-slate-300 italic uppercase">Sin Categoría</span>
                                @endif
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-xs font-bold text-slate-600">{{ $product->client->company_name }}</span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col items-center">
                                    <p class="text-xs font-bold text-slate-700">{{ $product->weight_kg }} kg</p>
                                    <p class="text-[9px] text-slate-400 uppercase tracking-tighter">
                                        {{ $product->length_cm }}x{{ $product->width_cm }}x{{ $product->height_cm }} cm
                                    </p>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.products.edit', $product->id) }}" class="text-slate-400 hover:text-custom-primary transition p-2 hover:bg-white rounded-lg shadow-sm border border-transparent hover:border-slate-200">
                                        <i class="fa-solid fa-pen-to-square"></i>
                                    </a>
                                    <form action="{{ route('admin.products.destroy', $product->id) }}" method="POST" onsubmit="return confirm('¿Retirar este producto del catálogo activo?');">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-slate-400 hover:text-red-500 transition p-2 hover:bg-white rounded-lg shadow-sm border border-transparent hover:border-slate-200">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-20 text-center">
                                <div class="flex flex-col items-center opacity-30">
                                    <i class="fa-solid fa-box-open text-4xl mb-4"></i>
                                    <p class="font-bold">No se encontraron productos en el catálogo.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-100">
            {{ $products->links() }}
        </div>
    </div>

@endsection