@extends('layouts.admin')

@section('title', 'Stock Actual')
@section('header_title', 'Inventario Global')

@section('content')

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form action="{{ route('admin.inventory.stock') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            
            <div class="md:col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Búsqueda Rápida</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-slate-400"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none transition" 
                           placeholder="Buscar por SKU, Nombre de Producto...">
                </div>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cliente</label>
                <select name="client_id" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm outline-none focus:border-custom-primary bg-white">
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
                    <i class="fa-solid fa-filter mr-2"></i> Filtrar
                </button>
                <a href="{{ route('admin.inventory.stock') }}" class="bg-slate-100 text-slate-500 px-3 py-2 rounded-lg hover:bg-slate-200 transition text-center" title="Limpiar Filtros">
                    <i class="fa-solid fa-filter-circle-xmark"></i>
                </a>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Producto</th>
                        <th class="px-6 py-4 text-center text-slate-400" title="Total en Estantería">Físico</th>
                        <th class="px-6 py-4 text-center text-orange-400" title="Comprometido en Órdenes">Reservado</th>
                        <th class="px-6 py-4 text-center text-green-600" title="Libre para Venta">Disponible</th>
                        <th class="px-6 py-4 text-right">Detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($inventory as $item) 
                        {{-- 
                           NOTA: $item aquí representa una línea de 'Inventory' (un bin específico), 
                           pero para el reporte consolidado idealmente deberíamos iterar sobre 'Product'.
                           Si el controlador envía Inventory, agrupamos visualmente.
                           
                           Para este diseño "Amazon Style", asumiremos que $inventory es una colección de Productos 
                           (si cambiaste el controlador como sugerí). Si no, el código se adapta abajo.
                        --}}
                        
                        @php
                            // Si $item es Inventory, accedemos al producto. Si es Product, es directo.
                            $product = $item instanceof \App\Models\Product ? $item : $item->product;
                            
                            // Cálculos usando los nuevos accessors del modelo
                            $fisico = $product->physical_stock;
                            $reservado = $product->committed_stock;
                            $disponible = $product->available_stock;
                        @endphp

                        <tr class="hover:bg-slate-50 transition group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded bg-slate-100 flex items-center justify-center text-slate-300 border border-slate-200">
                                        @if($product->image_path)
                                            <img src="{{ asset('storage/' . $product->image_path) }}" class="w-full h-full object-cover rounded">
                                        @else
                                            <i class="fa-solid fa-box"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-700">{{ Str::limit($product->name, 40) }}</p>
                                        <div class="flex items-center gap-2">
                                            <span class="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500 font-mono border border-slate-200">
                                                {{ $product->sku }}
                                            </span>
                                            <span class="text-[9px] text-slate-400 italic">
                                                {{ $product->client->company_name }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4 text-center">
                                <span class="bg-slate-100 text-slate-600 px-3 py-1 rounded-lg font-bold border border-slate-200">
                                    {{ $fisico }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                @if($reservado > 0)
                                    <span class="bg-orange-50 text-orange-600 px-3 py-1 rounded-lg font-bold border border-orange-200 cursor-help" title="Pendiente de Picking">
                                        {{ $reservado }}
                                    </span>
                                @else
                                    <span class="text-slate-300">-</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-center">
                                @if($disponible > 0)
                                    <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-lg font-bold border border-emerald-200 text-lg shadow-sm">
                                        {{ $disponible }}
                                    </span>
                                @else
                                    <span class="bg-red-50 text-red-600 px-3 py-1 rounded-lg font-bold border border-red-100 text-xs">
                                        AGOTADO
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-right">
                                <button onclick="document.getElementById('details-{{ $product->id }}').classList.toggle('hidden')" 
                                        class="text-slate-400 hover:text-custom-primary transition p-2 hover:bg-slate-100 rounded-lg">
                                    <i class="fa-solid fa-chevron-down"></i>
                                </button>
                            </td>
                        </tr>

                        <tr id="details-{{ $product->id }}" class="hidden bg-slate-50/50">
                            <td colspan="5" class="px-6 py-4 border-t border-slate-100">
                                <div class="pl-14">
                                    <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Desglose de Ubicaciones Físicas</p>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($product->inventory as $inv)
                                            @if($inv->quantity > 0)
                                                <div class="bg-white border border-slate-200 px-3 py-1.5 rounded-md shadow-sm flex items-center gap-2">
                                                    <span class="font-mono text-xs font-bold text-custom-primary">
                                                        <i class="fa-solid fa-location-dot mr-1"></i>{{ $inv->location->code }}
                                                    </span>
                                                    <span class="h-4 w-px bg-slate-200"></span>
                                                    <span class="text-xs font-bold text-slate-600">{{ $inv->quantity }} un.</span>
                                                    @if($inv->lpn)
                                                        <span class="h-4 w-px bg-slate-200"></span>
                                                        <span class="text-[9px] font-mono text-purple-500" title="LPN / Serial">{{ $inv->lpn }}</span>
                                                    @endif
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    <div class="mt-3 flex gap-2">
                                        <a href="{{ route('admin.inventory.movements', ['sku' => $product->sku]) }}" class="text-xs text-blue-600 hover:underline">
                                            Ver Kardex Completo
                                        </a>
                                        <span class="text-slate-300">|</span>
                                        <a href="{{ route('admin.inventory.adjustments') }}" class="text-xs text-amber-600 hover:underline">
                                            Ajustar Inventario
                                        </a>
                                    </div>
                                </div>
                            </td>
                        </tr>

                    @empty
                        <tr>
                            <td colspan="5" class="p-12 text-center">
                                <div class="flex flex-col items-center opacity-50">
                                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-box-open text-3xl text-slate-300"></i>
                                    </div>
                                    <p class="font-bold text-slate-600">Sin Inventario</p>
                                    <p class="text-xs text-slate-400">No se encontraron productos con el filtro actual.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($inventory->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $inventory->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

@endsection