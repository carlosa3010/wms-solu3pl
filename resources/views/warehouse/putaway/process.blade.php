@extends('layouts.warehouse')

@section('station_title', 'Confirmar Ubicación')

@section('content')
<div class="max-w-2xl mx-auto h-full flex flex-col">

    <div class="bg-slate-800 rounded-2xl p-6 mb-6 border border-slate-700 text-center">
        <h2 class="text-white font-bold text-xl">{{ $product->name }}</h2>
        <span class="inline-block bg-slate-900 text-yellow-400 font-mono text-lg px-3 py-1 rounded mt-2 border border-yellow-500/30">
            {{ $product->sku }}
        </span>
        <div class="mt-4 flex justify-center gap-4">
            <div class="bg-slate-700/50 p-2 rounded-lg">
                <p class="text-xs text-slate-400 uppercase">A Mover</p>
                <p class="text-2xl font-black text-white">{{ $inventory->quantity }}</p>
            </div>
        </div>
    </div>

    <div class="bg-blue-900/20 border border-blue-500/50 rounded-2xl p-6 mb-6 text-center animate-pulse">
        <p class="text-blue-400 text-xs font-bold uppercase tracking-widest mb-1">Ubicación Sugerida</p>
        <h1 class="text-4xl font-black text-white font-mono">
            {{ $suggestedLoc ? $suggestedLoc->code : 'SIN ASIGNAR' }}
        </h1>
        <p class="text-xs text-blue-300 mt-2">
            @if($suggestedLoc)
                Pasillo {{ $suggestedLoc->aisle }} - Estante {{ $suggestedLoc->rack }} - Nivel {{ $suggestedLoc->level }}
            @else
                Busca un espacio vacío disponible.
            @endif
        </p>
    </div>

    <form action="{{ route('warehouse.putaway.confirm') }}" method="POST" autocomplete="off" class="mt-auto">
        @csrf
        <input type="hidden" name="inventory_id" value="{{ $inventory->id }}">
        
        <input type="hidden" name="quantity" value="{{ $inventory->quantity }}">

        <div class="bg-slate-800 p-4 rounded-t-2xl border-t border-slate-700">
            <label class="text-xs font-bold text-white uppercase mb-2 block">Escanear Ubicación Destino</label>
            <div class="flex gap-2">
                <input type="text" name="location_code" autofocus required placeholder="Escanear Bin..."
                    class="flex-1 bg-slate-900 border-2 border-green-500 text-white p-4 rounded-xl text-lg font-mono outline-none focus:ring-4 focus:ring-green-500/30">
                
                <button type="submit" class="bg-green-600 hover:bg-green-500 text-white px-6 rounded-xl font-bold transition">
                    <i class="fa-solid fa-check text-xl"></i>
                </button>
            </div>
        </div>
    </form>

    <div class="mt-4 text-center">
        <a href="{{ route('warehouse.putaway.index') }}" class="text-slate-500 text-sm hover:text-white underline">Cancelar / Volver</a>
    </div>
</div>
@endsection