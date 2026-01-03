@extends('layouts.admin')

@section('title', 'Stock Actual')
@section('header_title', 'Inventario Global')

@section('content')

    <!-- Filtros y Búsqueda -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form action="{{ route('admin.inventory.stock') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
            
            <!-- Buscador General -->
            <div class="md:col-span-2">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Búsqueda Rápida</label>
                <div class="relative">
                    <span class="absolute left-3 top-2.5 text-slate-400"><i class="fa-solid fa-magnifying-glass"></i></span>
                    <input type="text" name="search" value="{{ request('search') }}" 
                           class="w-full pl-9 pr-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none transition" 
                           placeholder="Buscar por SKU, Producto, LPN o Ubicación...">
                </div>
            </div>

            <!-- Filtro Cliente -->
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cliente</label>
                <select name="client_id" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm outline-none focus:border-custom-primary bg-white">
                    <option value="">Todos los Clientes</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}" {{ request('client_id') == $client->id ? 'selected' : '' }}>
                            {{ $client->company_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Botones -->
            <div class="flex gap-2">
                <button type="submit" class="bg-custom-primary text-white px-4 py-2 rounded-lg font-bold text-sm hover:brightness-95 transition flex-1">
                    Filtrar
                </button>
                <a href="{{ route('admin.inventory.stock') }}" class="bg-slate-100 text-slate-500 px-3 py-2 rounded-lg hover:bg-slate-200 transition text-center" title="Limpiar Filtros">
                    <i class="fa-solid fa-filter-circle-xmark"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- Tabla de Inventario -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Producto (SKU)</th>
                        <th class="px-6 py-4">Ubicación (Bin)</th>
                        <th class="px-6 py-4">LPN / Lote</th>
                        <th class="px-6 py-4 text-center">Físico</th>
                        <th class="px-6 py-4 text-center">Por Recibir (ASN)</th>
                        <th class="px-6 py-4 text-center">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($inventory as $item)
                        <tr class="hover:bg-slate-50/80 transition group">
                            <!-- Producto -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded bg-slate-100 flex items-center justify-center text-slate-300 overflow-hidden border border-slate-200">
                                        @if($item->product->image_url)
                                            <img src="{{ $item->product->image_url }}" class="w-full h-full object-cover">
                                        @else
                                            <i class="fa-solid fa-box"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-bold text-slate-700 group-hover:text-custom-primary transition">{{ $item->product->name }}</p>
                                        <p class="text-[10px] text-slate-400 font-mono font-bold">{{ $item->product->sku }}</p>
                                        <p class="text-[9px] text-slate-400 italic">{{ $item->product->client->company_name }}</p>
                                    </div>
                                </div>
                            </td>

                            <!-- Ubicación -->
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-mono font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded w-fit text-xs border border-slate-200">
                                        {{ $item->location->code }}
                                    </span>
                                    <span class="text-[10px] text-slate-400 mt-1">
                                        {{ $item->location->warehouse->name }}
                                    </span>
                                </div>
                            </td>

                            <!-- LPN -->
                            <td class="px-6 py-4">
                                @if($item->lpn)
                                    <span class="font-mono text-xs text-slate-600 font-bold"><i class="fa-solid fa-barcode mr-1 text-slate-300"></i>{{ $item->lpn }}</span>
                                @else
                                    <span class="text-[10px] text-slate-300 italic uppercase">Sin LPN</span>
                                @endif
                            </td>

                            <!-- Cantidad Física -->
                            <td class="px-6 py-4 text-center">
                                <span class="text-lg font-black text-slate-800">{{ $item->quantity }}</span>
                                <span class="text-[9px] text-slate-400 uppercase block font-bold">Unidades</span>
                            </td>

                            <!-- Cantidad en Tránsito (ASN) -->
                            <td class="px-6 py-4 text-center">
                                @php
                                    // Esta lógica es ilustrativa. En el controlador deberías sumar 
                                    // las cantidades de asn_items pendientes para este producto y ubicación.
                                    $inTransit = 0; 
                                @endphp
                                <span class="text-sm font-bold {{ $inTransit > 0 ? 'text-blue-600' : 'text-slate-300' }}">
                                    {{ $inTransit > 0 ? '+' . $inTransit : '--' }}
                                </span>
                                @if($inTransit > 0)
                                    <span class="text-[9px] text-blue-400 uppercase block font-bold">En Camino</span>
                                @endif
                            </td>

                            <!-- Estado -->
                            <td class="px-6 py-4 text-center">
                                @if($item->quantity > 0)
                                    <span class="bg-green-100 text-green-700 px-2 py-1 rounded-full text-[9px] font-bold border border-green-200 uppercase tracking-tighter shadow-sm">
                                        En Stock
                                    </span>
                                @else
                                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-[9px] font-bold border border-red-200 uppercase tracking-tighter shadow-sm">
                                        Agotado
                                    </span>
                                @endif
                            </td>

                            <!-- Acciones -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.inventory.movements', ['sku' => $item->product->sku]) }}" 
                                       class="text-slate-400 hover:text-custom-primary transition p-2 hover:bg-slate-100 rounded-lg" 
                                       title="Ver Historial (Kardex)">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </a>
                                    
                                    <a href="{{ route('admin.inventory.adjustments', ['product_id' => $item->product_id, 'location_code' => $item->location->code]) }}" 
                                       class="text-slate-400 hover:text-amber-600 transition p-2 hover:bg-amber-50 rounded-lg" 
                                       title="Realizar Ajuste Manual">
                                        <i class="fa-solid fa-scale-balanced"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center opacity-50">
                                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-box-open text-3xl text-slate-300"></i>
                                    </div>
                                    <p class="font-bold text-slate-600">No se encontraron existencias físicas</p>
                                    <p class="text-xs text-slate-400 max-w-xs mx-auto">Realice una recepción de mercancía para ver stock aquí.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        @if($inventory->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $inventory->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

@endsection