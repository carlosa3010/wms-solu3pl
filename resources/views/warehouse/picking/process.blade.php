@extends('layouts.warehouse')

@section('station_title', 'Picking: ' . $order->order_number)

@section('content')
<div class="max-w-2xl mx-auto h-full flex flex-col">

    <div class="mb-4 flex justify-between items-end px-2">
        <span class="text-xs text-slate-400">Progreso Orden</span>
        <span class="text-xs font-bold text-orange-400">
            {{ $order->items->where('quantity_picked', '>=', 'quantity')->count() }} / {{ $order->items->count() }} Líneas
        </span>
    </div>

    <div class="bg-slate-800 rounded-2xl border-2 border-slate-700 overflow-hidden shadow-xl flex-1 flex flex-col relative">
        
        <div class="bg-slate-900 p-6 text-center border-b border-slate-700 relative">
            <p class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">DIRÍGETE A LA UBICACIÓN</p>
            
            @if($suggestedLoc)
                <h1 class="text-5xl font-black text-white font-mono tracking-tight">{{ $suggestedLoc->code }}</h1>
                <p class="text-xs text-slate-500 mt-2">
                    Pasillo {{ $suggestedLoc->aisle }} - Nivel {{ $suggestedLoc->level }}
                </p>
            @else
                <h1 class="text-3xl font-bold text-red-500">SIN STOCK</h1>
                <p class="text-xs text-red-400 mt-1">No se encontró inventario disponible para este ítem.</p>
            @endif

            @if(session('location_verified'))
                <div class="absolute top-4 right-4 bg-green-500 text-white rounded-full p-2 shadow-lg animate-bounce-in">
                    <i class="fa-solid fa-check"></i>
                </div>
            @endif
        </div>

        <div class="p-6 text-center flex-1 flex flex-col justify-center items-center">
            <h2 class="text-xl font-bold text-white mb-1">{{ $nextItem->product->name }}</h2>
            <div class="bg-slate-700/50 px-4 py-2 rounded-lg mb-4 inline-block">
                <span class="font-mono text-orange-300 text-lg tracking-wider">{{ $nextItem->product->sku }}</span>
            </div>

            <div class="grid grid-cols-2 gap-4 w-full max-w-xs mb-4">
                <div class="bg-slate-700 p-3 rounded-xl">
                    <span class="block text-xs text-slate-400 uppercase">Solicitado</span>
                    <span class="block text-2xl font-bold text-white">{{ $nextItem->quantity }}</span>
                </div>
                <div class="bg-slate-700 p-3 rounded-xl">
                    <span class="block text-xs text-slate-400 uppercase">Recolectado</span>
                    <span class="block text-2xl font-bold text-orange-400">{{ $nextItem->quantity_picked }}</span>
                </div>
            </div>
        </div>

        <div class="p-4 bg-slate-900 border-t border-slate-700">
            
            @if(!session('location_verified') && $suggestedLoc)
                <form action="{{ route('warehouse.picking.scan_location') }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <input type="hidden" name="suggested_location_id" value="{{ $suggestedLoc->id }}">
                    
                    <label class="text-xs text-slate-500 uppercase font-bold mb-1 block">1. Escanear Ubicación</label>
                    <div class="flex gap-2">
                        <input type="text" name="location_code" autofocus placeholder="Escanear Bin..."
                            class="flex-1 bg-slate-800 border border-blue-500 text-white p-3 rounded-xl font-mono outline-none focus:ring-2 focus:ring-blue-500/50">
                        <button class="bg-blue-600 text-white px-4 rounded-xl font-bold"><i class="fa-solid fa-arrow-right"></i></button>
                    </div>
                </form>

            @elseif(session('location_verified') && $suggestedLoc)
                <form action="{{ route('warehouse.picking.scan_item') }}" method="POST" autocomplete="off">
                    @csrf
                    <input type="hidden" name="order_id" value="{{ $order->id }}">
                    <input type="hidden" name="item_id" value="{{ $nextItem->id }}">
                    <input type="hidden" name="location_id" value="{{ $suggestedLoc->id }}">
                    <input type="hidden" name="qty_to_pick" value="{{ $nextItem->quantity - $nextItem->quantity_picked }}">

                    <label class="text-xs text-slate-500 uppercase font-bold mb-1 block">2. Escanear Producto</label>
                    <div class="flex gap-2">
                        <input type="text" name="barcode" autofocus placeholder="Escanear SKU..."
                            class="flex-1 bg-slate-800 border border-orange-500 text-white p-3 rounded-xl font-mono outline-none focus:ring-2 focus:ring-orange-500/50">
                        <button class="bg-orange-600 text-white px-4 rounded-xl font-bold"><i class="fa-solid fa-check"></i></button>
                    </div>
                </form>
            @else
                <button class="w-full bg-red-600/20 text-red-500 py-3 rounded-xl font-bold border border-red-600/50">
                    <i class="fa-solid fa-triangle-exclamation mr-2"></i> Reportar Quiebre de Stock
                </button>
            @endif

        </div>
    </div>

    <div class="mt-4 text-center">
        <a href="{{ route('warehouse.picking.index') }}" class="text-slate-500 text-xs uppercase hover:text-white">Pausar y Salir</a>
    </div>

</div>
@endsection