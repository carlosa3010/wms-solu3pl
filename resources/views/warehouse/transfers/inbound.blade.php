@extends('layouts.warehouse')

@section('station_title', 'Recibir Traslado ' . $transfer->transfer_number)

@section('content')
<div class="max-w-4xl mx-auto p-4 space-y-6">

    <div class="bg-slate-800 rounded-2xl p-5 border border-slate-700 flex justify-between items-center">
        <div>
            <p class="text-slate-400 text-xs uppercase font-bold tracking-wider">Origen</p>
            <h2 class="text-white text-2xl font-bold">{{ $transfer->originBranch->name }}</h2>
        </div>
        <div class="text-right">
            <span class="bg-blue-500/20 text-blue-400 border border-blue-500/30 px-3 py-1 rounded-full text-xs font-bold uppercase">
                En Tránsito
            </span>
        </div>
    </div>

    <div class="space-y-3">
        <p class="text-slate-400 text-sm px-1">Verifica que los siguientes productos estén en la carga:</p>
        
        @foreach($transfer->items as $item)
        <div class="bg-slate-800 p-4 rounded-xl border border-slate-700 flex items-center justify-between group hover:border-emerald-500 transition-colors">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-emerald-500/10 text-emerald-500 flex items-center justify-center font-bold">
                    <i class="fa-solid fa-check"></i>
                </div>
                <div>
                    <p class="text-white font-bold text-lg">{{ $item->product->sku }}</p>
                    <p class="text-slate-400 text-sm">{{ Str::limit($item->product->name, 40) }}</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-xs text-slate-500 block">Cantidad</span>
                <span class="text-white font-mono text-xl font-bold">{{ $item->quantity }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="bg-blue-900/30 border border-blue-800 p-4 rounded-xl flex gap-3">
        <i class="fa-solid fa-info-circle text-blue-400 mt-1"></i>
        <div class="text-sm text-blue-200">
            <p>Al confirmar, el inventario se sumará automáticamente a la ubicación <strong>RECEPCION</strong> de tu bodega.</p>
            <p class="mt-1 opacity-70">Si hay pedidos esperando este stock (Backorders), se liberarán automáticamente.</p>
        </div>
    </div>

    <div class="pt-2">
        <form action="{{ route('warehouse.transfers.inbound_confirm', $transfer->id) }}" method="POST" 
              onsubmit="return confirm('¿Confirmas que la mercancía ha llegado físicamente y está correcta?');">
            @csrf
            <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-4 rounded-xl shadow-lg shadow-emerald-900/20 active:scale-95 transition-all flex flex-col items-center justify-center">
                <span class="text-lg">CONFIRMAR RECEPCIÓN</span>
                <span class="text-xs opacity-80 font-normal">Ingresar Stock y Liberar Pedidos</span>
            </button>
        </form>
        <a href="{{ route('warehouse.transfers.index') }}" class="block text-center text-slate-500 mt-4 py-2 hover:text-white">
            Cancelar y Volver
        </a>
    </div>
</div>
@endsection