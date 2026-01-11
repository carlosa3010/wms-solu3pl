@extends('layouts.warehouse')

@section('station_title', 'Gestión de Traslados')

@section('content')
<div class="max-w-4xl mx-auto space-y-6 p-4">

    <div class="flex space-x-2 bg-slate-800 p-1 rounded-xl" x-data="{ tab: 'inbound' }">
        <button @click="tab = 'inbound'" 
            :class="{ 'bg-blue-600 text-white shadow': tab === 'inbound', 'text-slate-400 hover:text-white': tab !== 'inbound' }"
            class="flex-1 py-3 text-sm font-bold rounded-lg transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-truck-ramp-box"></i>
            ENTRANTES ({{ $inbound->count() }})
        </button>
        <button @click="tab = 'outbound'" 
            :class="{ 'bg-orange-600 text-white shadow': tab === 'outbound', 'text-slate-400 hover:text-white': tab !== 'outbound' }"
            class="flex-1 py-3 text-sm font-bold rounded-lg transition-all flex items-center justify-center gap-2">
            <i class="fa-solid fa-dolly"></i>
            SALIENTES ({{ $outbound->count() }})
        </button>
    </div>

    <div x-show="tab === 'inbound'" class="space-y-4" x-data="{ tab: 'inbound' }">
        @forelse($inbound as $tr)
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-lg hover:border-blue-500 transition-colors">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="bg-blue-500/20 text-blue-400 px-2 py-1 rounded text-xs font-bold border border-blue-500/30">
                        {{ $tr->transfer_number }}
                    </span>
                    <span class="text-xs text-slate-400 flex items-center">
                        <i class="fa-regular fa-clock mr-1"></i> {{ $tr->created_at->format('d/m H:i') }}
                    </span>
                </div>
                <h3 class="text-white font-bold text-lg flex items-center gap-2">
                    <i class="fa-solid fa-arrow-right-to-bracket text-emerald-500"></i>
                    Desde: {{ $tr->originBranch->name }}
                </h3>
                <p class="text-sm text-slate-400 mt-1">
                    Contiene: <span class="text-white font-medium">{{ $tr->items->sum('quantity') }} unidades</span>
                </p>
            </div>
            <a href="{{ route('warehouse.transfers.inbound', $tr->id) }}" 
               class="w-full md:w-auto bg-blue-600 hover:bg-blue-500 text-white font-bold py-3 px-6 rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg active:scale-95">
                <i class="fa-solid fa-box-open"></i>
                RECIBIR AHORA
            </a>
        </div>
        @empty
        <div class="text-center py-12 text-slate-500">
            <i class="fa-solid fa-check-circle text-4xl mb-3 opacity-50"></i>
            <p>No hay traslados pendientes por recibir.</p>
        </div>
        @endforelse
    </div>

    <div x-show="tab === 'outbound'" class="space-y-4" style="display: none;">
        @forelse($outbound as $tr)
        <div class="bg-slate-800 rounded-xl border border-slate-700 p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-lg hover:border-orange-500 transition-colors">
            <div>
                <div class="flex items-center gap-3 mb-2">
                    <span class="bg-orange-500/20 text-orange-400 px-2 py-1 rounded text-xs font-bold border border-orange-500/30">
                        {{ $tr->transfer_number }}
                    </span>
                    <span class="text-xs text-slate-400">
                        Creado por: {{ $tr->creator->name ?? 'Sistema' }}
                    </span>
                </div>
                <h3 class="text-white font-bold text-lg flex items-center gap-2">
                    <i class="fa-solid fa-truck-fast text-orange-500"></i>
                    Para: {{ $tr->destinationBranch->name }}
                </h3>
                <p class="text-sm text-slate-400 mt-1">
                    Solicitado: <span class="text-white font-medium">{{ $tr->items->sum('quantity') }} unidades</span>
                </p>
            </div>
            <a href="{{ route('warehouse.transfers.outbound', $tr->id) }}" 
               class="w-full md:w-auto bg-orange-600 hover:bg-orange-500 text-white font-bold py-3 px-6 rounded-xl flex items-center justify-center gap-2 transition-all shadow-lg active:scale-95">
                <i class="fa-solid fa-clipboard-list"></i>
                PREPARAR ENVÍO
            </a>
        </div>
        @empty
        <div class="text-center py-12 text-slate-500">
            <i class="fa-solid fa-check-circle text-4xl mb-3 opacity-50"></i>
            <p>No hay traslados pendientes de salida.</p>
        </div>
        @endforelse
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
@endsection