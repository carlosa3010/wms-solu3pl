@extends('layouts.warehouse')

@section('station_title', 'Preparar Traslado ' . $transfer->transfer_number)

@section('content')
<div class="max-w-4xl mx-auto p-4 space-y-6">

    <div class="bg-slate-800 rounded-2xl p-5 border border-slate-700 flex justify-between items-center">
        <div>
            <p class="text-slate-400 text-xs uppercase font-bold tracking-wider">Destino</p>
            <h2 class="text-white text-2xl font-bold">{{ $transfer->destinationBranch->name }}</h2>
        </div>
        <div class="text-right">
            <p class="text-slate-400 text-xs uppercase font-bold">Total Ítems</p>
            <p class="text-orange-400 text-xl font-mono font-bold">{{ $transfer->items->sum('quantity') }}</p>
        </div>
    </div>

    <div class="space-y-3">
        @foreach($transfer->items as $item)
        <div class="bg-slate-800 p-4 rounded-xl border border-slate-700 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-slate-700 flex items-center justify-center text-slate-300 font-bold">
                    {{ $loop->iteration }}
                </div>
                <div>
                    <p class="text-white font-bold text-lg">{{ $item->product->sku }}</p>
                    <p class="text-slate-400 text-sm">{{ Str::limit($item->product->name, 40) }}</p>
                </div>
            </div>
            <div class="text-right bg-slate-900 px-4 py-2 rounded-lg border border-slate-600">
                <span class="text-xs text-slate-500 block">Recoger</span>
                <span class="text-white font-mono text-xl font-bold">{{ $item->quantity }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="pt-4">
        <form action="{{ route('warehouse.transfers.outbound_confirm', $transfer->id) }}" method="POST" 
              onsubmit="return confirm('¿Confirmas que has recolectado todos los ítems físicamente?');">
            @csrf
            <button type="submit" class="w-full bg-orange-600 hover:bg-orange-500 text-white font-bold py-4 rounded-xl shadow-lg shadow-orange-900/20 active:scale-95 transition-all flex flex-col items-center justify-center">
                <span class="text-lg">CONFIRMAR DESPACHO</span>
                <span class="text-xs opacity-80 font-normal">Mover stock a "En Tránsito"</span>
            </button>
        </form>
        <a href="{{ route('warehouse.transfers.index') }}" class="block text-center text-slate-500 mt-4 py-2 hover:text-white">
            Cancelar y Volver
        </a>
    </div>
</div>
@endsection