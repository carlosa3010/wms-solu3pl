@extends('layouts.admin')

@section('title', 'Gestión de Pedidos')
@section('header_title', 'Órdenes de Salida')

@section('content')

    <!-- Filtros y Acciones Superiores -->
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

    <!-- Tabla Principal de Pedidos -->
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
                            <!-- Identificación del Pedido -->
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-700 block text-sm group-hover:text-custom-primary transition">{{ $order->order_number }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">
                                    {{ $order->external_ref ?? 'Sin Referencia Externa' }}
                                </span>
                            </td>

                            <!-- Cliente (Propietario de la mercancía) -->
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-7 h-7 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 text-[10px]">
                                        <i class="fa-solid fa-briefcase"></i>
                                    </div>
                                    <span class="font-bold text-slate-600">{{ $order->client->company_name }}</span>
                                </div>
                            </td>

                            <!-- Inteligencia de Asignación Visual -->
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
                                    
                                    <!-- Badge de Sede que atiende el pedido -->
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

                            <!-- Fecha -->
                            <td class="px-6 py-4">
                                <span class="text-xs text-slate-500 flex items-center gap-2">
                                    <i class="fa-regular fa-calendar text-slate-300"></i>
                                    {{ $order->created_at->format('d/m/Y') }}
                                </span>
                                <span class="text-[10px] text-slate-400 block ml-5">
                                    {{ $order->created_at->format('H:i A') }}
                                </span>
                            </td>

                            <!-- Estado con Badge Dinámico -->
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusConfig = [
                                        'pending'   => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'border' => 'border-yellow-200', 'icon' => 'fa-clock'],
                                        'allocated' => ['bg' => 'bg-blue-50',   'text' => 'text-blue-700',   'border' => 'border-blue-200',   'icon' => 'fa-box-circle-check'],
                                        'picking'   => ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-200', 'icon' => 'fa-dolly'],
                                        'packing'   => ['bg' => 'bg-purple-50', 'text' => 'text-purple-700', 'border' => 'border-purple-200', 'icon' => 'fa-box-open'],
                                        'shipped'   => ['bg' => 'bg-green-50',  'text' => 'text-green-700',  'border' => 'border-green-200',  'icon' => 'fa-truck-fast'],
                                        'cancelled' => ['bg' => 'bg-red-50',    'text' => 'text-red-700',    'border' => 'border-red-200',    'icon' => 'fa-ban'],
                                    ];
                                    $s = $statusConfig[$order->status] ?? ['bg' => 'bg-slate-50', 'text' => 'text-slate-600', 'border' => 'border-slate-200', 'icon' => 'fa-circle-question'];
                                @endphp
                                <span class="{{ $s['bg'] }} {{ $s['text'] }} {{ $s['border'] }} px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border flex items-center justify-center gap-1.5 w-fit mx-auto shadow-sm">
                                    <i class="fa-solid {{ $s['icon'] }}"></i> {{ $order->status_label }}
                                </span>
                            </td>

                            <!-- Acciones -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-1">
                                    <!-- Ver Detalle / Picking Plan -->
                                    <a href="{{ route('admin.orders.show', $order->id) }}" 
                                       class="text-slate-400 hover:text-custom-primary transition p-2 hover:bg-blue-50 rounded-lg" 
                                       title="Ver Detalle y Plan de Picking">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <!-- Eliminar (Solo si está en espera) -->
                                    @if($order->status === 'pending')
                                        <form action="{{ route('admin.orders.destroy', $order->id) }}" method="POST" 
                                              onsubmit="return confirm('¿Está seguro de eliminar el pedido {{ $order->order_number }}? Esta acción no se puede deshacer.');" 
                                              class="inline">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-slate-400 hover:text-red-500 transition p-2 hover:bg-red-50 rounded-lg" title="Anular Pedido">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
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

        <!-- Paginación -->
        @if($orders->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/30">
                {{ $orders->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

@endsection