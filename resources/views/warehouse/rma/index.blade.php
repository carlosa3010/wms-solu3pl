@extends('layouts.warehouse')
@section('station_title', 'Recepción de Devoluciones (RMA)')

@section('content')
<div class="max-w-5xl mx-auto p-4">

    @if(session('success'))
        <div class="bg-emerald-500/20 border border-emerald-500 text-emerald-400 px-4 py-3 rounded-xl mb-4 flex items-center gap-3">
            <i class="fa-solid fa-check-circle text-xl"></i>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid gap-4">
        @forelse($rmas as $rma)
        <div class="bg-slate-800 p-5 rounded-xl border border-slate-700 flex flex-col md:flex-row justify-between items-start md:items-center shadow-lg hover:border-red-500/50 transition group">
            
            <div class="mb-4 md:mb-0">
                <div class="flex items-center gap-3 mb-2">
                    <span class="bg-red-500/20 text-red-400 border border-red-500/30 px-2 py-1 rounded text-xs font-bold">
                        RMA #{{ $rma->id }}
                    </span>
                    <span class="text-xs text-slate-500 flex items-center gap-1">
                        <i class="fa-solid fa-clock"></i> {{ $rma->created_at->format('d/m/Y') }}
                    </span>
                </div>
                
                <h3 class="text-white font-bold text-lg">{{ $rma->client->company_name ?? 'Cliente Desconocido' }}</h3>
                
                <div class="text-sm text-slate-400 mt-1">
                    Ref. Orden: <span class="text-white font-mono">{{ $rma->order->order_number ?? 'N/A' }}</span>
                </div>
                
                @if($rma->tracking_number)
                <div class="text-xs text-indigo-400 mt-2 flex items-center gap-1">
                    <i class="fa-solid fa-truck"></i> Tracking: {{ $rma->tracking_number }}
                </div>
                @endif
            </div>

            <a href="{{ route('warehouse.rma.process', $rma->id) }}" 
               class="w-full md:w-auto bg-slate-700 hover:bg-red-600 text-white font-bold py-3 px-6 rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg active:scale-95 group-hover:shadow-red-900/20">
                <i class="fa-solid fa-camera"></i>
                <span>INSPECCIONAR</span>
            </a>
        </div>
        @empty
        <div class="text-center py-16 bg-slate-800/50 rounded-2xl border-2 border-dashed border-slate-700">
            <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-600">
                <i class="fa-solid fa-box-open text-3xl text-slate-500"></i>
            </div>
            <h3 class="text-white font-bold text-lg">Sin Devoluciones Pendientes</h3>
            <p class="text-slate-400 text-sm mt-1">No hay solicitudes de RMA aprobadas para recibir.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection