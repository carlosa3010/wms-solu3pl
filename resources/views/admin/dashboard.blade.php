@extends('layouts.admin')

@section('title', 'Centro de Comando')
@section('header_title', 'Dashboard Operativo')

@section('content')
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Nuevos (Hoy)</p>
                <h3 class="text-3xl font-black text-slate-800 mt-2">{{ $ordersToday }}</h3>
                <p class="text-[10px] text-slate-400 mt-1">Órdenes ingresadas</p>
            </div>
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-file-circle-plus"></i>
            </div>
        </div>

        <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-100 flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Salidas (Hoy)</p>
                <h3 class="text-3xl font-black text-emerald-600 mt-2">{{ $shippedToday }}</h3>
                <p class="text-[10px] text-slate-400 mt-1">Órdenes completadas</p>
            </div>
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center text-xl">
                <i class="fa-solid fa-truck-fast"></i>
            </div>
        </div>

        <div class="bg-gradient-to-br from-slate-800 to-slate-900 rounded-2xl p-6 shadow-lg text-white flex items-center justify-between relative overflow-hidden">
            <div class="relative z-10">
                <p class="text-xs font-bold text-slate-300 uppercase tracking-widest">Eficiencia Diaria</p>
                @php
                    $efficiency = $ordersToday > 0 ? round(($shippedToday / $ordersToday) * 100) : 0;
                    if($shippedToday > 0 && $ordersToday == 0) $efficiency = 100; // Si solo se despachó backlog
                @endphp
                <h3 class="text-3xl font-black mt-2">{{ $efficiency }}%</h3>
                <p class="text-[10px] text-slate-400 mt-1">Conversión Entrada/Salida</p>
            </div>
            <div class="relative z-10 w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center text-xl backdrop-blur-sm">
                <i class="fa-solid fa-stopwatch"></i>
            </div>
            <div class="absolute -right-6 -bottom-10 text-9xl text-white/5 rotate-12">
                <i class="fa-solid fa-chart-line"></i>
            </div>
        </div>
    </div>

    <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-4 px-1">Estado del Backlog</h3>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        
        <a href="{{ route('admin.orders.index', ['status' => 'pending']) }}" class="group bg-white p-5 rounded-xl shadow-sm border-l-4 border-yellow-400 hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="text-2xl font-bold text-slate-700">{{ $pendingOrders }}</h4>
                    <p class="text-xs font-bold text-slate-400 uppercase mt-1">Pendientes</p>
                </div>
                <i class="fa-regular fa-clock text-yellow-400 text-xl group-hover:scale-110 transition"></i>
            </div>
        </a>

        <a href="{{ route('admin.orders.index', ['status' => 'picking']) }}" class="group bg-white p-5 rounded-xl shadow-sm border-l-4 border-blue-500 hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="text-2xl font-bold text-slate-700">{{ $processingOrders }}</h4>
                    <p class="text-xs font-bold text-slate-400 uppercase mt-1">En Picking/Packing</p>
                </div>
                <i class="fa-solid fa-boxes-packing text-blue-500 text-xl group-hover:scale-110 transition"></i>
            </div>
        </a>

        <a href="{{ route('admin.receptions.index') }}" class="group bg-white p-5 rounded-xl shadow-sm border-l-4 border-purple-500 hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="text-2xl font-bold text-slate-700">{{ $pendingASNs }}</h4>
                    <p class="text-xs font-bold text-slate-400 uppercase mt-1">Recepciones (ASN)</p>
                </div>
                <i class="fa-solid fa-dolly text-purple-500 text-xl group-hover:scale-110 transition"></i>
            </div>
        </a>

        <a href="{{ route('admin.rma.index') }}" class="group bg-white p-5 rounded-xl shadow-sm border-l-4 border-red-500 hover:shadow-md transition">
            <div class="flex justify-between items-start">
                <div>
                    <h4 class="text-2xl font-bold text-slate-700">{{ $pendingRMAs }}</h4>
                    <p class="text-xs font-bold text-slate-400 uppercase mt-1">Devoluciones (RMA)</p>
                </div>
                <i class="fa-solid fa-rotate-left text-red-500 text-xl group-hover:scale-110 transition"></i>
            </div>
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
        
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 lg:col-span-1">
            <h3 class="font-bold text-slate-700 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-warehouse text-custom-primary"></i> Capacidad
            </h3>
            
            <div class="flex items-center justify-center py-4">
                <div class="relative w-40 h-40">
                    <svg class="w-full h-full" viewBox="0 0 36 36">
                        <path class="text-slate-100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" />
                        <path class="{{ $occupancyRate > 90 ? 'text-red-500' : ($occupancyRate > 70 ? 'text-yellow-500' : 'text-custom-primary') }}" stroke-dasharray="{{ $occupancyRate }}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831" fill="none" stroke="currentColor" stroke-width="3" />
                    </svg>
                    <div class="absolute inset-0 flex flex-col items-center justify-center">
                        <span class="text-3xl font-black text-slate-700">{{ $occupancyRate }}%</span>
                        <span class="text-[10px] uppercase font-bold text-slate-400">Ocupación</span>
                    </div>
                </div>
            </div>

            <div class="space-y-3 mt-4">
                <div class="flex justify-between text-sm border-b border-slate-50 pb-2">
                    <span class="text-slate-500">Total Ubicaciones</span>
                    <span class="font-bold text-slate-700">{{ number_format($totalBins) }}</span>
                </div>
                <div class="flex justify-between text-sm border-b border-slate-50 pb-2">
                    <span class="text-slate-500">Ocupadas</span>
                    <span class="font-bold text-slate-700">{{ number_format($occupiedBins) }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-500">Disponibles</span>
                    <span class="font-bold text-emerald-600">{{ number_format($totalBins - $occupiedBins) }}</span>
                </div>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700 text-sm">Actividad Reciente</h3>
                    <a href="{{ route('admin.orders.index') }}" class="text-xs text-blue-600 font-bold hover:underline">Ver todo</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <tbody class="divide-y divide-slate-50">
                            @forelse($recentOrders as $order)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="p-4">
                                        <p class="font-bold text-slate-700">{{ $order->order_number }}</p>
                                        <p class="text-[10px] text-slate-400">{{ $order->client->company_name }}</p>
                                    </td>
                                    <td class="p-4 text-center">
                                        @if($order->status == 'pending')
                                            <span class="bg-yellow-100 text-yellow-700 px-2 py-0.5 rounded text-[10px] font-bold">Pendiente</span>
                                        @elseif($order->status == 'shipped')
                                            <span class="bg-green-100 text-green-700 px-2 py-0.5 rounded text-[10px] font-bold">Despachado</span>
                                        @else
                                            <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-bold">{{ ucfirst($order->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="p-4 text-right text-xs text-slate-500">
                                        {{ $order->updated_at->diffForHumans() }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="p-6 text-center text-slate-400 text-xs">Sin actividad reciente.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="font-bold text-slate-700 text-sm">Top Clientes (Mes Actual)</h3>
                </div>
                <div class="p-4">
                    <div class="space-y-4">
                        @foreach($topClients as $client)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-xs">
                                        {{ substr($client->company_name, 0, 1) }}
                                    </div>
                                    <span class="text-sm font-bold text-slate-700">{{ $client->company_name }}</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-24 bg-slate-100 rounded-full overflow-hidden">
                                        {{-- Barra de progreso visual simple basada en un maximo teorico de 100 pedidos --}}
                                        <div class="h-full bg-custom-primary" style="width: {{ min(($client->orders_count / 100) * 100, 100) }}%"></div>
                                    </div>
                                    <span class="text-xs font-bold text-slate-600">{{ $client->orders_count }} Ops</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection