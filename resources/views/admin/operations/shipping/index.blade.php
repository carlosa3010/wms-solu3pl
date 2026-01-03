@extends('layouts.admin')

@section('title', 'Cola de Despacho')
@section('header_title', 'Despachos Pendientes')

@section('content')

    <!-- Filtros Rápidos -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6 flex flex-col md:flex-row justify-between items-center gap-4">
        <form action="{{ route('admin.shipping.index') }}" method="GET" class="flex-1 max-w-lg w-full">
            <div class="relative group">
                <span class="absolute left-3 top-2.5 text-slate-400 group-focus-within:text-custom-primary transition">
                    <i class="fa-solid fa-truck-fast"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" 
                       class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none transition" 
                       placeholder="Buscar orden lista para despacho...">
            </div>
        </form>
        <div class="flex items-center gap-2">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Total en cola:</span>
            <span class="bg-custom-primary text-white px-2 py-0.5 rounded-full text-xs font-bold">{{ $pendingShipments->total() }}</span>
        </div>
    </div>

    <!-- Tabla de Cola de Salida -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Orden / Cliente</th>
                        <th class="px-6 py-4">Destino</th>
                        <th class="px-6 py-4">Sede Origen</th>
                        <th class="px-6 py-4 text-center">Cant. Items</th>
                        <th class="px-6 py-4 text-center">Estado</th>
                        <th class="px-6 py-4 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pendingShipments as $order)
                        <tr class="hover:bg-slate-50 transition group">
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-700 block">{{ $order->order_number }}</span>
                                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">{{ $order->client->company_name }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs">
                                    <p class="font-bold text-slate-600">{{ $order->customer_name }}</p>
                                    <p class="text-[10px] text-slate-400 italic truncate max-w-[200px]">{{ $order->shipping_address }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-[10px] bg-blue-50 text-blue-700 border border-blue-100 px-2 py-1 rounded font-black uppercase">
                                    {{ $order->branch->name ?? 'PRINCIPAL' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-sm font-black text-slate-700">{{ $order->items->count() }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-1 rounded-full text-[9px] font-black uppercase border {{ $order->status_color }}-100 {{ $order->status_color }}-700 bg-{{ $order->status_color }}-50">
                                    {{ $order->status_label }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('admin.shipping.process', $order->id) }}" class="bg-custom-primary text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-md hover:brightness-110 transition flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-box-open"></i> Despachar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-20 text-center">
                                <div class="flex flex-col items-center opacity-30">
                                    <i class="fa-solid fa-truck-loading text-4xl mb-4"></i>
                                    <p class="font-bold">No hay pedidos pendientes por despachar.</p>
                                    <p class="text-xs">Los pedidos aparecerán aquí una vez que se complete el picking.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-100">
            {{ $pendingShipments->links() }}
        </div>
    </div>
@endsection