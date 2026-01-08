@extends('layouts.admin')

@section('title', 'Gestión de Pedidos')
@section('header_title', 'Órdenes de Salida')

@section('content')

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <form action="{{ route('admin.orders.index') }}" method="GET" class="flex-1 max-w-lg w-full">
            <div class="relative group">
                <span class="absolute left-3 top-2.5 text-slate-400 group-focus-within:text-custom-primary transition">
                    <i class="fa-solid fa-search"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" 
                       class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none transition shadow-sm" 
                       placeholder="Buscar por Orden, Cliente o Destinatario...">
            </div>
        </form>
        
        <div class="flex gap-3 w-full md:w-auto">
            <a href="{{ route('admin.orders.create') }}" class="flex-1 md:flex-none bg-custom-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:brightness-95 transition flex items-center justify-center gap-2">
                <i class="fa-solid fa-plus"></i> Nueva Orden
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 tracking-wider">Orden / Ref</th>
                        <th class="px-6 py-4 tracking-wider">Cliente (Dueño)</th>
                        <th class="px-6 py-4 tracking-wider">Destinatario / Sede Asignada</th>
                        <th class="px-6 py-4 tracking-wider">Fecha Creación</th>
                        <th class="px-6 py-4 text-center tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-right tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($orders as $order)
                        <tr class="hover:bg-slate-50/50 transition group">
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-700 block text-sm group-hover:text-custom-primary transition">{{ $order->order_number }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">
                                    {{ $order->external_ref ?? 'Sin Referencia Externa' }}
                                </span>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-[10px]">
                                        <i class="fa-solid fa-briefcase"></i>
                                    </div>
                                    <span class="font-bold text-slate-600">{{ $order->client->company_name }}</span>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex flex-col">
                                        <p class="font-bold text-slate-700 leading-tight">{{ $order->customer_name }}</p>
                                        <p class="text-[10px] text-slate-400 flex items-center gap-1">
                                            <i class="fa-solid fa-location-dot text-[8px]"></i>
                                            {{ $order->city }}, {{ $order->state }}
                                        </p>
                                    </div>
                                    <i class="fa-solid fa-arrow-right text-[10px] text-slate-300"></i>
                                    
                                    <div class="flex flex-col items-center">
                                        @if($order->branch)
                                            <div class="bg-blue-50 px-2 py-1 rounded border border-blue-100 flex items-center gap-1.5 shadow-sm">
                                                <i class="fa-solid fa-building-circle-check text-custom-primary text-[10px]"></i>
                                                <span class="text-[9px] font-black text-custom-primary uppercase tracking-tighter">
                                                    {{ $order->branch->name }}
                                                </span>
                                            </div>
                                        @else
                                            <div class="bg-red-50 px-2 py-1 rounded border border-red-100 flex items-center gap-1.5">
                                                <i class="fa-solid fa-triangle-exclamation text-red-400 text-[10px]"></i>
                                                <span class="text-[9px] font-bold text-red-400 uppercase">Sin Sede</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td class="px-6 py-4">
                                <span class="text-xs text-slate-500 flex items-center gap-2">
                                    <i class="fa-regular fa-calendar text-slate-300"></i>
                                    {{ $order->created_at->format('d/m/Y') }}
                                </span>
                                <span class="text-[10px] text-slate-400 block ml-5">
                                    {{ $order->created_at->format('H:i A') }}
                                </span>
                            </td>

                            <td class="px-6 py-4 text-center">
                                @if($order->status == 'pending')
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800 border border-yellow-200">
                                        <i class="fa-regular fa-clock mr-1 mt-0.5"></i> Pendiente
                                    </span>
                                @elseif($order->status == 'allocated')
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800 border border-indigo-200">
                                        <i class="fa-solid fa-list-check mr-1 mt-0.5"></i> Listo para Picking
                                    </span>
                                @elseif($order->status == 'shipped')
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 border border-green-200">
                                        <i class="fa-solid fa-truck-fast mr-1 mt-0.5"></i> Despachado
                                    </span>
                                @elseif($order->status == 'cancelled')
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 border border-red-200">
                                        <i class="fa-solid fa-ban mr-1 mt-0.5"></i> Anulado
                                    </span>
                                @else
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 border border-gray-200">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                @endif
                            </td>

                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.orders.show', $order->id) }}" 
                                       class="text-slate-400 hover:text-custom-primary transition p-2 hover:bg-blue-50 rounded-lg" 
                                       title="Ver Detalle y Plan de Picking">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    @if(in_array($order->status, ['draft', 'pending', 'cancelled']))
                                        <form action="{{ route('admin.orders.destroy', $order->id) }}" method="POST" 
                                              onsubmit="return confirm('¿Está seguro de eliminar el pedido {{ $order->order_number }}? Esta acción no se puede deshacer.');" 
                                              class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-slate-400 hover:text-red-500 transition p-2 hover:bg-red-50 rounded-lg" title="Eliminar Pedido">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @else
                                        <button disabled class="text-slate-200 cursor-not-allowed p-2" title="No se puede eliminar una orden en proceso">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-20 text-center">
                                <div class="flex flex-col items-center justify-center opacity-40">
                                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-cart-flatbed text-3xl text-slate-300"></i>
                                    </div>
                                    <p class="font-bold text-slate-600 text-lg">No hay órdenes registradas</p>
                                    <p class="text-xs max-w-xs mx-auto">Las órdenes aparecerán aquí cuando los clientes soliciten despachos o se creen manualmente.</p>
                                </div>
                                <div class="mt-6">
                                    <a href="{{ route('admin.orders.create') }}" class="text-custom-primary font-bold text-sm hover:underline">Crear el primer pedido ahora</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($orders->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/30">
                {{ $orders->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

@endsection