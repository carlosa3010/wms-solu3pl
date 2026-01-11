@extends('layouts.warehouse')

@section('station_title', 'Empacando Orden ' . $order->order_number)

@section('content')
<div class="max-w-4xl mx-auto p-4 space-y-6">

    <div class="bg-slate-800 rounded-2xl p-5 border border-slate-700 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 shadow-lg">
        <div>
            <p class="text-slate-400 text-xs uppercase font-bold tracking-wider">Cliente</p>
            <h2 class="text-white text-xl font-bold">{{ $order->client->company_name }}</h2>
            <p class="text-xs text-slate-500 mt-1 flex items-center gap-1">
                <i class="fa-solid fa-location-dot"></i> {{ Str::limit($order->shipping_address, 40) }}
            </p>
        </div>
        <div class="text-right">
            <span class="bg-blue-600/20 text-blue-400 border border-blue-500/30 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                {{ $order->shipping_method ?? 'Estándar' }}
            </span>
        </div>
    </div>

    <div class="space-y-3">
        <h3 class="text-slate-400 text-sm font-bold uppercase tracking-wider px-1">Contenido del Pedido</h3>
        
        @foreach($order->items as $item)
        <div class="bg-slate-800 p-4 rounded-xl border border-slate-700 flex items-center justify-between group hover:border-blue-500 transition-colors">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-slate-900 rounded-lg flex items-center justify-center border border-slate-600 text-slate-400 font-bold text-lg">
                    {{ substr($item->product->sku, 0, 2) }}
                </div>
                
                <div>
                    <p class="text-white font-bold text-lg leading-tight">{{ $item->product->sku }}</p>
                    <p class="text-slate-400 text-xs mt-0.5">{{ Str::limit($item->product->name, 50) }}</p>
                </div>
            </div>

            <div class="text-right bg-slate-900 px-4 py-2 rounded-lg border border-slate-600 min-w-[80px]">
                <span class="text-[10px] text-slate-500 block uppercase font-bold">Cant.</span>
                <span class="text-white font-mono text-2xl font-bold">{{ $item->requested_quantity }}</span>
            </div>
        </div>
        @endforeach
    </div>

    <div class="bg-slate-900 rounded-2xl p-6 border border-slate-700 mt-8 shadow-2xl">
        <h3 class="text-white font-bold text-lg mb-4 flex items-center gap-2">
            <i class="fa-solid fa-box-tape text-blue-500"></i> Detalles del Paquete
        </h3>
        
        <form action="{{ route('warehouse.packing.close', $order->id) }}" method="POST" id="packingForm" onsubmit="return confirm('¿Confirmas que todo el contenido está correcto y empacado?');">
            @csrf
            
            <div class="grid grid-cols-2 gap-6 mb-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Peso Total (Kg) <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="number" step="0.01" name="weight" required min="0.1" 
                               class="w-full bg-slate-800 border-2 border-slate-600 text-white rounded-xl py-3 px-4 text-lg font-bold focus:border-blue-500 focus:ring-0 outline-none transition placeholder-slate-600"
                               placeholder="0.00">
                        <span class="absolute right-4 top-3.5 text-slate-500 font-bold text-sm">KG</span>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Total Cajas <span class="text-red-500">*</span></label>
                    <div class="flex items-center">
                        <button type="button" onclick="adjustBox(-1)" class="w-12 h-12 bg-slate-700 rounded-l-xl text-white hover:bg-slate-600 transition border-y-2 border-l-2 border-slate-600 font-bold text-xl">-</button>
                        <input type="number" id="boxCount" name="boxes" value="1" min="1" required readonly
                               class="w-full bg-slate-800 border-y-2 border-slate-600 text-white py-3 text-center text-lg font-bold focus:ring-0 outline-none h-12">
                        <button type="button" onclick="adjustBox(1)" class="w-12 h-12 bg-slate-700 rounded-r-xl text-white hover:bg-slate-600 transition border-y-2 border-r-2 border-slate-600 font-bold text-xl">+</button>
                    </div>
                </div>
            </div>

            <div class="flex gap-3">
                <a href="{{ route('warehouse.packing.index') }}" class="w-1/3 bg-slate-700 hover:bg-slate-600 text-slate-300 font-bold py-4 rounded-xl text-center transition">
                    Cancelar
                </a>
                <button type="submit" class="w-2/3 bg-emerald-600 hover:bg-emerald-500 text-white font-bold py-4 rounded-xl shadow-lg shadow-emerald-900/20 active:scale-95 transition-all flex items-center justify-center gap-2">
                    <i class="fa-solid fa-print"></i>
                    CERRAR Y ETIQUETAR
                </button>
            </div>
        </form>
    </div>

</div>

<script>
    function adjustBox(delta) {
        const input = document.getElementById('boxCount');
        let val = parseInt(input.value) || 1;
        val += delta;
        if(val < 1) val = 1;
        input.value = val;
    }
</script>
@endsection