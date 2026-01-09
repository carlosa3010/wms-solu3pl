@extends('layouts.warehouse')

@section('station_title', 'Picking (Recolección)')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    
    @forelse($orders as $order)
        <a href="{{ route('warehouse.picking.process', $order->id) }}" class="block bg-slate-800 border border-slate-700 rounded-2xl p-5 hover:border-orange-500 transition active:scale-95 shadow-md relative overflow-hidden group">
            
            <div class="absolute top-0 right-0 bg-orange-600 text-white text-[10px] font-bold px-3 py-1 rounded-bl-xl">
                PRIORIDAD ALTA
            </div>

            <div class="flex justify-between items-start mb-2">
                <div>
                    <span class="text-orange-400 font-mono font-bold text-xl group-hover:underline">#{{ $order->order_number }}</span>
                    <p class="text-slate-300 font-bold text-sm">{{ $order->client->company_name }}</p>
                </div>
            </div>
            
            <div class="flex justify-between items-end mt-4">
                <div class="text-xs text-slate-500">
                    <p><i class="fa-solid fa-clock mr-1"></i> {{ $order->created_at->diffForHumans() }}</p>
                    <p><i class="fa-solid fa-list mr-1"></i> {{ $order->items->count() }} Líneas</p>
                </div>
                <div class="text-right">
                    @php
                        $total = $order->items->sum('quantity');
                        $picked = $order->items->sum('quantity_picked');
                        $pct = $total > 0 ? ($picked/$total)*100 : 0;
                    @endphp
                    <span class="text-2xl font-black text-white">{{ $total }}</span>
                    <span class="text-[10px] text-slate-400 uppercase block">Unidades</span>
                </div>
            </div>

            <div class="mt-3 w-full bg-slate-700 rounded-full h-1.5">
                <div class="bg-orange-500 h-1.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
            </div>
        </a>
    @empty
        <div class="text-center py-20 opacity-50">
            <i class="fa-solid fa-check-double text-6xl mb-4 text-slate-600"></i>
            <p class="text-xl text-white">Todo al día</p>
            <p class="text-slate-500">No hay órdenes pendientes de picking.</p>
        </div>
    @endforelse
</div>
@endsection