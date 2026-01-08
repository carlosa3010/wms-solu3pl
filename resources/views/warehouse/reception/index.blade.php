@extends('layouts.warehouse')

@section('station_title', 'Recepción Inbound')

@section('content')
<div class="max-w-4xl mx-auto space-y-4">
    
    <div class="flex gap-2 overflow-x-auto pb-2">
        <span class="bg-blue-600 text-white px-4 py-1 rounded-full text-xs font-bold uppercase whitespace-nowrap">Por Llegar</span>
        <span class="bg-slate-700 text-slate-300 px-4 py-1 rounded-full text-xs font-bold uppercase whitespace-nowrap border border-slate-600">Parciales</span>
    </div>

    @forelse($asns as $asn)
        <a href="{{ route('warehouse.reception.show', $asn->id) }}" class="block bg-slate-800 border border-slate-700 rounded-2xl p-5 hover:border-blue-500 transition active:scale-95 shadow-md group">
            <div class="flex justify-between items-start mb-2">
                <div>
                    <span class="text-blue-400 font-mono font-bold text-lg group-hover:underline">{{ $asn->asn_number }}</span>
                    <p class="text-slate-300 font-bold text-sm">{{ $asn->client->company_name }}</p>
                </div>
                <div class="text-right">
                    <span class="bg-slate-700 text-slate-300 px-2 py-1 rounded text-[10px] font-bold uppercase">{{ $asn->status }}</span>
                </div>
            </div>
            
            <div class="flex justify-between items-end mt-4">
                <div class="text-xs text-slate-500">
                    <p><i class="fa-solid fa-truck mr-1"></i> {{ $asn->carrier_name ?? 'Particular' }}</p>
                    <p><i class="fa-solid fa-calendar mr-1"></i> {{ \Carbon\Carbon::parse($asn->expected_arrival_date)->format('d M') }}</p>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-black text-white">{{ $asn->items->sum('expected_quantity') }}</span>
                    <span class="text-[10px] text-slate-400 uppercase block">Unidades</span>
                </div>
            </div>

            @if($asn->status == 'partial' || $asn->status == 'in_process')
                @php
                    $rec = $asn->items->sum('received_quantity');
                    $exp = $asn->items->sum('expected_quantity');
                    $pct = $exp > 0 ? ($rec/$exp)*100 : 0;
                @endphp
                <div class="mt-3 w-full bg-slate-700 rounded-full h-1.5">
                    <div class="bg-green-500 h-1.5 rounded-full" style="width: {{ $pct }}%"></div>
                </div>
            @endif
        </a>
    @empty
        <div class="text-center py-20 opacity-50">
            <i class="fa-solid fa-clipboard-check text-6xl mb-4 text-slate-600"></i>
            <p class="text-xl">No hay recepciones pendientes</p>
        </div>
    @endforelse
</div>
@endsection