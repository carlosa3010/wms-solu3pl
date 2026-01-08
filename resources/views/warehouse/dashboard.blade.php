@extends('layouts.warehouse')

@section('station_title', 'Menú Principal')

@section('content')
<div class="h-full flex flex-col justify-center max-w-5xl mx-auto">
    
    <div class="grid grid-cols-2 md:grid-cols-3 gap-4 p-4">
        
        <a href="{{ route('warehouse.reception.index') }}" class="group bg-slate-800 border border-slate-700 hover:border-emerald-500 rounded-2xl p-6 flex flex-col items-center justify-center transition-all hover:bg-slate-750 shadow-lg">
            <div class="w-16 h-16 rounded-full bg-emerald-500/10 flex items-center justify-center mb-4 group-hover:bg-emerald-500 group-hover:text-white transition text-emerald-400">
                <i class="fa-solid fa-truck-ramp-box text-3xl"></i>
            </div>
            <h2 class="text-lg font-bold text-white">RECEPCIÓN</h2>
            <p class="text-xs text-slate-400 mt-1">Ingreso (ASN)</p>
        </a>

        <a href="{{ route('warehouse.picking.index') }}" class="group bg-slate-800 border border-slate-700 hover:border-orange-500 rounded-2xl p-6 flex flex-col items-center justify-center transition-all hover:bg-slate-750 shadow-lg">
            <div class="w-16 h-16 rounded-full bg-orange-500/10 flex items-center justify-center mb-4 group-hover:bg-orange-500 group-hover:text-white transition text-orange-400">
                <i class="fa-solid fa-cart-flatbed text-3xl"></i>
            </div>
            <h2 class="text-lg font-bold text-white">PICKING</h2>
            <p class="text-xs text-slate-400 mt-1">Recolección</p>
        </a>

        <a href="{{ route('warehouse.packing.index') }}" class="group bg-slate-800 border border-slate-700 hover:border-blue-500 rounded-2xl p-6 flex flex-col items-center justify-center transition-all hover:bg-slate-750 shadow-lg">
            <div class="w-16 h-16 rounded-full bg-blue-500/10 flex items-center justify-center mb-4 group-hover:bg-blue-500 group-hover:text-white transition text-blue-400">
                <i class="fa-solid fa-box-open text-3xl"></i>
            </div>
            <h2 class="text-lg font-bold text-white">PACKING</h2>
            <p class="text-xs text-slate-400 mt-1">Empaque & Etiquetado</p>
        </a>

        <a href="{{ route('warehouse.shipping.index') }}" class="group bg-slate-800 border border-slate-700 hover:border-indigo-500 rounded-2xl p-6 flex flex-col items-center justify-center transition-all hover:bg-slate-750 shadow-lg">
            <div class="w-16 h-16 rounded-full bg-indigo-500/10 flex items-center justify-center mb-4 group-hover:bg-indigo-500 group-hover:text-white transition text-indigo-400">
                <i class="fa-solid fa-truck-fast text-3xl"></i>
            </div>
            <h2 class="text-lg font-bold text-white">DESPACHO</h2>
            <p class="text-xs text-slate-400 mt-1">Salida de Camión</p>
        </a>

        <a href="{{ route('warehouse.inventory.index') }}" class="group bg-slate-800 border border-slate-700 hover:border-teal-500 rounded-2xl p-6 flex flex-col items-center justify-center transition-all hover:bg-slate-750 shadow-lg">
            <div class="w-16 h-16 rounded-full bg-teal-500/10 flex items-center justify-center mb-4 group-hover:bg-teal-500 group-hover:text-white transition text-teal-400">
                <i class="fa-solid fa-clipboard-check text-3xl"></i>
            </div>
            <h2 class="text-lg font-bold text-white">INVENTARIO</h2>
            <p class="text-xs text-slate-400 mt-1">Movimientos & Conteo</p>
        </a>

        <a href="{{ route('warehouse.rma.index') }}" class="group bg-slate-800 border border-slate-700 hover:border-red-500 rounded-2xl p-6 flex flex-col items-center justify-center transition-all hover:bg-slate-750 shadow-lg">
            <div class="w-16 h-16 rounded-full bg-red-500/10 flex items-center justify-center mb-4 group-hover:bg-red-500 group-hover:text-white transition text-red-400">
                <i class="fa-solid fa-rotate-left text-3xl"></i>
            </div>
            <h2 class="text-lg font-bold text-white">DEVOLUCIONES</h2>
            <p class="text-xs text-slate-400 mt-1">Revisión RMA</p>
        </a>

        <div class="md:col-span-3 mt-4">
            <form action="{{ route('warehouse.lookup') }}" method="GET" class="relative">
                <input type="text" name="q" placeholder="Escanea Ubicación, SKU o LPN para consultar..." autofocus
                    class="w-full bg-slate-900 border-2 border-slate-600 text-white rounded-xl py-4 pl-12 pr-4 focus:border-white focus:ring-0 outline-none shadow-xl text-lg font-mono placeholder-slate-500 transition">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-5 text-slate-400 text-xl"></i>
            </form>
        </div>

    </div>
</div>
@endsection