@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Mis Pedidos</h2>
            <p class="text-sm text-slate-500">Gestiona las órdenes de salida y monitorea su estado de despacho.</p>
        </div>
        <a href="{{ route('client.orders.create') }}" class="flex items-center justify-center space-x-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
            <i data-lucide="shopping-cart" class="w-5 h-5"></i>
            <span>Nuevo Pedido</span>
        </a>
    </div>

    <!-- Tabla de Pedidos -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @if($orders->isEmpty())
            <div class="p-12 text-center text-slate-400">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="clipboard-list" class="w-8 h-8 text-slate-300"></i>
                </div>
                <h3 class="text-slate-800 font-bold mb-1">Sin pedidos registrados</h3>
                <p class="text-sm">Inicia tu primera orden para comenzar el proceso de logística.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Pedido / Ref</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Destinatario</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Items</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Estado</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($orders as $order)
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-xs">
                                        ORD
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">{{ $order->order_number }}</p>
                                        <p class="text-xs text-slate-400">Ref: {{ $order->reference_number ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-sm font-bold text-slate-700">{{ $order->customer_name }}</p>
                                <p class="text-xs text-slate-500">{{ $order->customer_city }}, {{ $order->customer_state }}</p>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-600">
                                {{ $order->created_at->format('d M, Y') }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-slate-700">
                                {{ $order->items->sum('quantity') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusClasses = [
                                        'pending' => 'bg-slate-100 text-slate-600',
                                        'processing' => 'bg-blue-100 text-blue-700',
                                        'shipped' => 'bg-emerald-100 text-emerald-700',
                                        'completed' => 'bg-gray-800 text-white',
                                        'cancelled' => 'bg-rose-100 text-rose-700',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Pendiente',
                                        'processing' => 'Procesando',
                                        'shipped' => 'Enviado',
                                        'completed' => 'Completado',
                                        'cancelled' => 'Cancelado',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-500' }}">
                                    {{ $statusLabels[$order->status] ?? $order->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- Botón Ver Detalles -->
                                    <a href="{{ route('client.orders.show', $order->id) }}" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Ver Detalles">
                                        <i data-lucide="eye" class="w-5 h-5"></i>
                                    </a>
                                    
                                    <!-- Botón Editar (Solo si el pedido está pendiente) -->
                                    @if($order->status === 'pending')
                                    <a href="{{ route('client.orders.edit', $order->id) }}" class="p-2 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-all" title="Editar Pedido">
                                        <i data-lucide="pencil" class="w-5 h-5"></i>
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection