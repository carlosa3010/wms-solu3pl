@extends('layouts.warehouse')

@section('station_title', 'Inventario y Conteos')

@section('content')
<div class="max-w-6xl mx-auto p-4">

    <div class="bg-slate-800 rounded-2xl p-4 mb-6 shadow-xl border border-slate-700">
        <form action="{{ route('warehouse.inventory.index') }}" method="GET" class="flex flex-col md:flex-row gap-4">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <i class="fa-solid fa-search text-slate-400"></i>
                </div>
                <input type="text" name="q" value="{{ request('q') }}" autofocus
                       class="w-full bg-slate-900 border border-slate-600 text-white text-lg pl-11 pr-4 py-3 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition placeholder-slate-500"
                       placeholder="Escanear Ubicación (Bin), SKU o LPN...">
            </div>
            <button type="submit" class="bg-indigo-600 hover:bg-indigo-500 text-white font-bold py-3 px-8 rounded-xl transition shadow-lg flex items-center justify-center gap-2">
                <i class="fa-solid fa-filter"></i> BUSCAR
            </button>
        </form>
    </div>

    <div class="bg-slate-800 rounded-2xl overflow-hidden border border-slate-700 shadow-2xl">
        <div class="p-5 border-b border-slate-700 bg-slate-800/50 flex justify-between items-center">
            <h3 class="text-white font-bold text-lg flex items-center gap-2">
                <i class="fa-solid fa-cubes-stacked text-emerald-400"></i> Existencias Físicas
            </h3>
            <span class="bg-slate-700 text-slate-300 px-3 py-1 rounded-lg text-xs font-bold border border-slate-600">
                {{ $stocks->total() }} Registros
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-300">
                <thead class="bg-slate-900/80 text-xs uppercase font-bold text-slate-500 border-b border-slate-700">
                    <tr>
                        <th class="px-6 py-4">Ubicación</th>
                        <th class="px-6 py-4">Producto (SKU)</th>
                        <th class="px-6 py-4 hidden md:table-cell">Lote / Vencimiento</th>
                        <th class="px-6 py-4 text-right">Stock Sistema</th>
                        <th class="px-6 py-4 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-700">
                    @forelse($stocks as $stock)
                    <tr class="hover:bg-slate-700/40 transition group">
                        
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-xl font-mono font-bold text-white bg-slate-700 px-3 py-1 rounded-lg border border-slate-600 w-fit">
                                    {{ $stock->location->code ?? 'N/A' }}
                                </span>
                                <span class="text-[10px] text-slate-500 uppercase mt-1 tracking-wider">
                                    {{ $stock->location->type ?? 'General' }}
                                </span>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-white font-bold text-base">{{ $stock->product->sku }}</span>
                                <span class="text-xs text-slate-400 mt-0.5">{{ Str::limit($stock->product->name, 45) }}</span>
                                <span class="text-[10px] text-indigo-400 mt-1 uppercase font-bold">
                                    {{ $stock->product->client->company_name ?? 'Sin Cliente' }}
                                </span>
                            </div>
                        </td>

                        <td class="px-6 py-4 hidden md:table-cell">
                            @if($stock->batch_number || $stock->expiry_date)
                                <div class="flex flex-col gap-1">
                                    @if($stock->batch_number)
                                        <span class="text-xs bg-slate-900 px-2 py-0.5 rounded text-slate-400 border border-slate-600 w-fit">
                                            Lote: {{ $stock->batch_number }}
                                        </span>
                                    @endif
                                    @if($stock->expiry_date)
                                        <span class="text-xs text-red-400 flex items-center gap-1">
                                            <i class="fa-regular fa-clock"></i> {{ $stock->expiry_date }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-600 text-xs italic">-- N/A --</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 text-right">
                            <span class="text-3xl font-bold text-emerald-400 tracking-tight">{{ number_format($stock->quantity) }}</span>
                            <span class="text-[10px] text-slate-500 uppercase block">Unidades</span>
                        </td>

                        <td class="px-6 py-4 text-center">
                            <div class="flex justify-center gap-2">
                                <button type="button" onclick="alert('Funcionalidad de Conteo Cíclico: Aquí se abriría un modal para confirmar si la cantidad {{ $stock->quantity }} es correcta en el bin {{ $stock->location->code }}.')" 
                                        class="bg-slate-700 hover:bg-blue-600 text-slate-300 hover:text-white p-2.5 rounded-xl transition border border-slate-600 group-hover:border-blue-500" title="Auditar / Contar">
                                    <i class="fa-solid fa-clipboard-check"></i>
                                </button>
                                
                                <button type="button" class="bg-slate-700 hover:bg-slate-600 text-slate-300 p-2.5 rounded-xl transition border border-slate-600" title="Imprimir Etiqueta">
                                    <i class="fa-solid fa-print"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <div class="w-16 h-16 bg-slate-800 rounded-full flex items-center justify-center mb-4 border border-slate-700">
                                    <i class="fa-solid fa-magnifying-glass text-2xl opacity-50"></i>
                                </div>
                                <p class="text-lg font-medium text-slate-400">No se encontraron resultados</p>
                                <p class="text-sm">Intenta escanear otro código o ubicación.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($stocks->hasPages())
        <div class="p-4 border-t border-slate-700 bg-slate-800">
            {{ $stocks->appends(['q' => request('q')])->links() }}
        </div>
        @endif
    </div>
</div>
@endsection