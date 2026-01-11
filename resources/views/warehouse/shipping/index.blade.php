@extends('layouts.warehouse')

@section('station_title', 'Estación de Despacho (Salidas)')

@section('content')
<div class="max-w-5xl mx-auto p-4">

    @if(session('success'))
        <div class="bg-emerald-500/20 border border-emerald-500 text-emerald-400 px-4 py-3 rounded-xl mb-6 flex items-center gap-3 animate-fade-in-down">
            <i class="fa-solid fa-truck-fast text-2xl"></i>
            <div>
                <p class="font-bold">¡Envío Confirmado!</p>
                <p class="text-xs opacity-90">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-500/20 border border-red-500 text-red-400 px-4 py-3 rounded-xl mb-6">
            <p class="font-bold"><i class="fa-solid fa-triangle-exclamation mr-2"></i> Error</p>
            <p class="text-sm">{{ session('error') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <div class="space-y-6">
            <div class="bg-gradient-to-br from-indigo-900 to-slate-900 rounded-2xl p-6 border border-indigo-500/30 shadow-2xl relative overflow-hidden">
                <div class="absolute top-0 right-0 -mt-2 -mr-2 w-24 h-24 bg-indigo-500 rounded-full blur-3xl opacity-20"></div>
                
                <h3 class="text-indigo-300 font-bold text-xs uppercase tracking-widest mb-1">Listos para Recolección</h3>
                <div class="flex items-baseline gap-2">
                    <span class="text-4xl font-black text-white">{{ $orders->count() }}</span>
                    <span class="text-sm text-indigo-400">Paquetes</span>
                </div>

                <div class="mt-6 pt-6 border-t border-white/10">
                    <label class="text-[10px] uppercase font-bold text-slate-400 mb-2 block">Filtro Rápido / Scan</label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-slate-500"></i>
                        <input type="text" id="searchInput" onkeyup="filterOrders()" 
                               class="w-full bg-slate-950/50 border border-slate-700 text-white rounded-xl py-2.5 pl-10 pr-4 text-sm focus:ring-1 focus:ring-indigo-500 outline-none transition"
                               placeholder="Buscar Orden, Cliente o Courier..." autofocus>
                    </div>
                </div>
            </div>

            <div class="bg-slate-800 rounded-2xl p-5 border border-slate-700 text-xs text-slate-400">
                <p class="mb-2"><strong class="text-white"><i class="fa-solid fa-info-circle text-blue-400"></i> Proceso:</strong></p>
                <ol class="list-decimal list-inside space-y-1 ml-1">
                    <li>Verifique que el courier coincida.</li>
                    <li>Entregue los paquetes físicos.</li>
                    <li>Haga clic en <strong>"Confirmar Salida"</strong> para dar de baja el inventario y notificar al cliente.</li>
                </ol>
            </div>
        </div>

        <div class="lg:col-span-2 space-y-4" id="ordersList">
            @forelse($orders as $order)
                <div class="order-card bg-slate-800 rounded-xl border border-slate-700 overflow-hidden hover:border-slate-500 transition group relative" 
                     data-search="{{ $order->order_number }} {{ $order->client->company_name }} {{ $order->shipping_method }}">
                    
                    <div class="p-5 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-start gap-4">
                            <div class="bg-slate-900 p-3 rounded-lg border border-slate-600 text-center min-w-[60px]">
                                <i class="fa-solid fa-box text-indigo-400 text-xl mb-1 block"></i>
                                <span class="text-[10px] font-bold text-slate-300 block">1 Bulto</span>
                            </div>
                            <div>
                                <div class="flex items-center gap-2 mb-1">
                                    <h4 class="text-white font-bold text-lg">{{ $order->order_number }}</h4>
                                    <span class="bg-emerald-500/20 text-emerald-400 text-[10px] font-bold px-2 py-0.5 rounded border border-emerald-500/30">
                                        PACKED
                                    </span>
                                </div>
                                <p class="text-slate-300 text-sm font-medium">{{ $order->client->company_name }}</p>
                                <p class="text-slate-500 text-xs mt-1 flex items-center gap-1">
                                    <i class="fa-solid fa-truck"></i> {{ $order->shipping_method ?? 'Courier Estándar' }}
                                </p>
                            </div>
                        </div>

                        <form action="{{ route('warehouse.shipping.manifest') }}" method="POST" class="w-full sm:w-auto" onsubmit="return confirm('¿Confirmar que el paquete ha sido entregado al transporte?');">
                            @csrf
                            <input type="hidden" name="order_id" value="{{ $order->id }}">
                            
                            <button type="submit" class="w-full sm:w-auto bg-slate-700 hover:bg-indigo-600 text-white font-bold py-3 px-6 rounded-xl transition-all flex items-center justify-center gap-2 group-hover:shadow-lg group-hover:shadow-indigo-500/20">
                                <span>CONFIRMAR SALIDA</span>
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div class="h-1 w-full bg-slate-900">
                        <div class="h-full bg-emerald-500 w-full"></div>
                    </div>
                </div>
            @empty
                <div class="bg-slate-800/50 rounded-2xl border-2 border-dashed border-slate-700 p-12 text-center">
                    <i class="fa-solid fa-dolly text-5xl text-slate-600 mb-4"></i>
                    <h3 class="text-white font-bold text-lg">Zona de Carga Vacía</h3>
                    <p class="text-slate-500 text-sm mt-1">No hay paquetes pendientes de despacho.</p>
                    <a href="{{ route('warehouse.packing.index') }}" class="text-indigo-400 text-xs font-bold mt-4 hover:underline block">
                        Ir a Empaque
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</div>

<script>
    // Filtro simple en cliente para simular búsqueda rápida
    function filterOrders() {
        const input = document.getElementById('searchInput');
        const filter = input.value.toLowerCase();
        const cards = document.getElementsByClassName('order-card');

        for (let i = 0; i < cards.length; i++) {
            const searchText = cards[i].getAttribute('data-search').toLowerCase();
            if (searchText.indexOf(filter) > -1) {
                cards[i].style.display = "";
            } else {
                cards[i].style.display = "none";
            }
        }
    }
</script>
@endsection