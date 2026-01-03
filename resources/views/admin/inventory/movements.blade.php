@extends('layouts.admin')

@section('title', 'Kardex de Movimientos')
@section('header_title', 'Trazabilidad de Inventario')

@section('content')
    
    <!-- Filtros -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form action="{{ route('admin.inventory.movements') }}" method="GET" class="flex gap-4 items-end">
            <div class="flex-1">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Buscar SKU</label>
                <input type="text" name="sku" value="{{ request('sku') }}" placeholder="Ej: LAP-001" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none">
            </div>
            <div class="flex-1">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Tipo Movimiento</label>
                <select name="type" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none bg-white">
                    <option value="">Todos</option>
                    <option value="Compra">Entrada (Compra)</option>
                    <option value="Venta">Salida (Venta)</option>
                    <option value="Ajuste">Ajuste Manual</option>
                </select>
            </div>
            <button type="submit" class="bg-custom-primary text-white px-6 py-2 rounded-lg font-bold text-sm">Filtrar</button>
        </form>
    </div>

    <!-- Tabla Kardex -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                <tr>
                    <th class="px-6 py-4">Fecha / Hora</th>
                    <th class="px-6 py-4">Producto</th>
                    <th class="px-6 py-4">Origen <i class="fa-solid fa-arrow-right mx-1 text-slate-300"></i> Destino</th>
                    <th class="px-6 py-4 text-center">Cantidad</th>
                    <th class="px-6 py-4">Motivo / Ref</th>
                    <th class="px-6 py-4 text-right">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($movements as $mov)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-700 block">{{ $mov->created_at->format('d/m/Y') }}</span>
                            <span class="text-[10px] text-slate-400">{{ $mov->created_at->format('H:i A') }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-700">{{ $mov->product->name }}</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ $mov->product->sku }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 text-xs">
                                <span class="{{ $mov->from_location_id ? 'text-slate-600' : 'text-custom-primary font-bold italic' }}">
                                    {{ $mov->fromLocation->code ?? 'EXTERNO' }}
                                </span>
                                <i class="fa-solid fa-arrow-right text-[10px] text-slate-300"></i>
                                <span class="{{ $mov->to_location_id ? 'text-slate-600' : 'text-custom-primary font-bold italic' }}">
                                    {{ $mov->toLocation->code ?? 'EXTERNO' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if(!$mov->from_location_id)
                                <span class="bg-green-100 text-green-700 px-2 py-1 rounded-lg font-bold text-xs">+{{ $mov->quantity }}</span>
                            @elseif(!$mov->to_location_id)
                                <span class="bg-red-100 text-red-700 px-2 py-1 rounded-lg font-bold text-xs">-{{ $mov->quantity }}</span>
                            @else
                                <span class="bg-slate-100 text-slate-700 px-2 py-1 rounded-lg font-bold text-xs">{{ $mov->quantity }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-xs text-slate-600">{{ $mov->reason }}</div>
                            <div class="text-[10px] text-slate-400 font-mono">{{ $mov->reference_number }}</div>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="text-xs bg-slate-100 px-2 py-1 rounded-full text-slate-500">
                                <i class="fa-solid fa-user text-[10px] mr-1"></i> {{ $mov->user->name ?? 'Sistema' }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 italic">No hay movimientos registrados.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4">
            {{ $movements->links() }}
        </div>
    </div>
@endsection