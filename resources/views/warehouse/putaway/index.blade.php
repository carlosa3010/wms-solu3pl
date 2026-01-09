@extends('layouts.warehouse')

@section('station_title', 'Put-away (Ubicación)')

@section('content')
<div class="max-w-3xl mx-auto h-full flex flex-col">
    
    <div class="bg-slate-800 p-4 rounded-2xl shadow-lg border border-yellow-500/50 mb-4">
        <form action="{{ route('warehouse.putaway.scan') }}" method="POST" autocomplete="off">
            @csrf
            <label class="text-xs font-bold text-yellow-500 uppercase block mb-1">Escanear Producto a Guardar</label>
            <div class="relative">
                <input type="text" name="barcode" autofocus placeholder="SKU o LPN..."
                    class="block w-full p-4 pl-4 text-lg font-mono text-white bg-slate-900 border-2 border-yellow-500 rounded-xl focus:ring-4 focus:ring-yellow-500/30 outline-none">
                <button type="submit" class="absolute inset-y-2 right-2 bg-yellow-600 text-white px-4 rounded-lg">
                    <i class="fa-solid fa-search"></i>
                </button>
            </div>
        </form>
    </div>

    <h3 class="text-slate-400 text-xs font-bold uppercase mb-2 px-2">Pendiente en Muelle (RECEPCION)</h3>
    
    <div class="flex-1 overflow-y-auto space-y-3 pb-20">
        @forelse($items as $item)
            <div class="bg-slate-800 border border-slate-700 rounded-xl p-4 flex justify-between items-center">
                <div>
                    <span class="bg-slate-900 text-slate-300 px-2 py-0.5 rounded text-xs font-mono border border-slate-600">
                        {{ $item->product->sku }}
                    </span>
                    <p class="text-white font-bold mt-1 text-sm">{{ Str::limit($item->product->name, 30) }}</p>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-black text-yellow-400">{{ $item->quantity }}</span>
                    <span class="text-[10px] text-slate-500 block uppercase">Unidades</span>
                </div>
            </div>
        @empty
            <div class="text-center py-10 opacity-50">
                <i class="fa-solid fa-check-circle text-5xl text-green-500 mb-3"></i>
                <p class="text-white">Todo guardado.</p>
                <p class="text-xs text-slate-400">El muelle de recepción está vacío.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection