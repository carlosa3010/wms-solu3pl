@extends('layouts.warehouse')

@section('station_title', 'Estación de Empaque')

@section('content')
<div class="max-w-4xl mx-auto p-4">

    @if(session('success'))
        <div class="bg-emerald-500/10 border border-emerald-500 text-emerald-400 px-4 py-3 rounded-xl mb-4 flex items-center gap-3">
            <i class="fa-solid fa-circle-check text-xl"></i>
            <div>
                <p class="font-bold">¡Éxito!</p>
                <p class="text-xs">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-500/10 border border-red-500 text-red-400 px-4 py-3 rounded-xl mb-4 flex items-center gap-3">
            <i class="fa-solid fa-circle-exclamation text-xl"></i>
            <div>
                <p class="font-bold">Error</p>
                <p class="text-xs">{{ session('error') }}</p>
            </div>
        </div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h2 class="text-white text-lg font-bold uppercase tracking-wider">
            <i class="fa-solid fa-box-open mr-2 text-blue-500"></i> Pendientes de Empaque
        </h2>
        <span class="bg-blue-600 text-white px-3 py-1 rounded-lg text-xs font-bold">
            {{ $orders->count() }} Órdenes
        </span>
    </div>

    <div class="grid gap-4">
        @forelse($orders as $order)
        <div class="bg-slate-800 p-5 rounded-xl border border-slate-700 flex flex-col md:flex-row justify-between items-start md:items-center shadow-lg hover:border-blue-500 transition-colors group">
            
            <div class="mb-4 md:mb-0">
                <div class="flex items-center gap-3 mb-2">
                    <span class="text-xs font-bold bg-blue-500/20 text-blue-400 px-2 py-1 rounded border border-blue-500/30">
                        {{ $order->order_number }}
                    </span>
                    <span class="text-[10px] text-slate-500 uppercase font-bold tracking-wider">
                        {{ $order->updated_at->diffForHumans() }}
                    </span>
                </div>
                
                <h3 class="text-white font-bold text-lg leading-tight">{{ $order->client->company_name ?? 'Cliente Desconocido' }}</h3>
                
                <div class="flex items-center gap-4 mt-2 text-xs text-slate-400">
                    <span class="flex items-center gap-1">
                        <i class="fa-solid fa-layer-group"></i> {{ $order->items->sum('requested_quantity') }} Unidades
                    </span>
                    <span class="flex items-center gap-1">
                        <i class="fa-solid fa-location-dot"></i> {{ Str::limit($order->shipping_address ?? 'Sin dirección', 25) }}
                    </span>
                </div>
            </div>

            <a href="{{ route('warehouse.packing.process', $order->id) }}" 
               class="w-full md:w-auto bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg active:scale-95 group-hover:bg-blue-500">
                <span>EMPACAR</span>
                <i class="fa-solid fa-arrow-right group-hover:translate-x-1 transition-transform"></i>
            </a>
        </div>
        @empty
        <div class="text-center py-16 bg-slate-800/50 rounded-2xl border-2 border-dashed border-slate-700">
            <div class="w-20 h-20 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4">
                <i class="fa-solid fa-check-circle text-4xl text-emerald-500 opacity-80"></i>
            </div>
            <h3 class="text-white font-bold text-lg">Todo al día</h3>
            <p class="text-slate-400 text-sm mt-1">No hay órdenes pendientes de empaque en este momento.</p>
            <p class="text-slate-500 text-xs mt-4">Las órdenes aparecerán aquí cuando se complete el Picking.</p>
        </div>
        @endforelse
    </div>

</div>
@endsection