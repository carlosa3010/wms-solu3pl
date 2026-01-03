@extends('layouts.admin')

@section('title', 'Traslados Internos')
@section('header_title', 'Consolidación y Movimientos')

@section('content')

    <!-- Alertas de Sistema -->
    @if(session('success'))
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 mb-6 rounded-xl shadow-sm animate-fade-in flex justify-between items-center">
            <p class="text-sm font-bold"><i class="fa-solid fa-circle-check mr-2"></i> {{ session('success') }}</p>
            <button onclick="this.parentElement.remove()" class="text-emerald-400 hover:text-emerald-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border-l-4 border-rose-500 text-rose-700 p-4 mb-6 rounded-xl shadow-sm animate-fade-in flex justify-between items-center">
            <p class="text-sm font-bold"><i class="fa-solid fa-circle-exclamation mr-2"></i> {{ session('error') }}</p>
            <button onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-600"><i class="fa-solid fa-xmark"></i></button>
        </div>
    @endif

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Gestión de Traslados</h2>
            <p class="text-xs text-slate-500 font-bold uppercase tracking-widest mt-1">Órdenes manuales y automáticas de consolidación</p>
        </div>
        
        <a href="{{ route('admin.transfers.create') }}" class="bg-blue-600 text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-blue-500/20 hover:bg-blue-700 transition flex items-center gap-2 active:scale-95">
            <i class="fa-solid fa-plus"></i> Nuevo Traslado Manual
        </a>
    </div>

    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 font-black text-[10px] uppercase tracking-widest border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-5">Orden / Fecha</th>
                        <th class="px-6 py-5 text-center">Ruta (Sede)</th>
                        <th class="px-6 py-5">Contenido (SKUs)</th>
                        <th class="px-6 py-5 text-center">Estado</th>
                        <th class="px-6 py-5 text-right">Acciones de Flujo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transfers as $trf)
                        <tr class="hover:bg-slate-50/50 transition group">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="font-black text-slate-800 text-sm tracking-tighter">{{ $trf->transfer_number }}</span>
                                    <span class="text-[10px] text-slate-400 font-bold">{{ $trf->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-3">
                                    <div class="text-center">
                                        <span class="block text-[9px] font-black text-slate-400 uppercase tracking-tighter">Origen</span>
                                        <span class="bg-slate-100 px-2.5 py-1 rounded-lg text-[10px] font-black text-slate-700 border border-slate-200 uppercase">
                                            {{ $trf->originBranch->name }}
                                        </span>
                                    </div>
                                    <i class="fa-solid fa-arrow-right text-blue-400 text-xs mt-3 animate-pulse"></i>
                                    <div class="text-center">
                                        <span class="block text-[9px] font-black text-slate-400 uppercase tracking-tighter">Destino</span>
                                        <span class="bg-blue-50 px-2.5 py-1 rounded-lg text-[10px] font-black text-blue-600 border border-blue-100 uppercase">
                                            {{ $trf->destinationBranch->name }}
                                        </span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col gap-1">
                                    @php $itemCount = $trf->items->count(); @endphp
                                    <span class="text-xs font-bold text-slate-700">
                                        {{ $trf->items->first()->product->name ?? 'Sin items' }}
                                        @if($itemCount > 1) 
                                            <span class="text-blue-500 ml-1">+{{ $itemCount - 1 }} más</span>
                                        @endif
                                    </span>
                                    <div class="flex gap-2">
                                        <span class="text-[9px] text-slate-400 font-mono uppercase tracking-tighter">Total Unidades: {{ $trf->items->sum('quantity') }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusClasses = [
                                        'pending' => 'bg-amber-100 text-amber-700 border-amber-200',
                                        'in_transit' => 'bg-blue-100 text-blue-700 border-blue-200',
                                        'completed' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                        'cancelled' => 'bg-rose-100 text-rose-700 border-rose-200',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'POR DESPACHAR',
                                        'in_transit' => 'EN CAMINO',
                                        'completed' => 'ENTREGADO',
                                        'cancelled' => 'ANULADO',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-[9px] font-black border {{ $statusClasses[$trf->status] ?? 'bg-slate-100 text-slate-500' }}">
                                    {{ $statusLabels[$trf->status] ?? $trf->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- BOTÓN IMPRIMIR: Manifiesto / Etiqueta --}}
                                    <a href="{{ route('admin.transfers.manifest', $trf->transfer_number) }}" target="_blank" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all" title="Imprimir Etiqueta / Manifiesto">
                                        <i class="fa-solid fa-print"></i>
                                    </a>

                                    <div class="h-4 w-px bg-slate-100 mx-1"></div>

                                    @if($trf->status == 'pending')
                                        {{-- ACCIÓN: Despachar de Origen --}}
                                        <form action="{{ route('admin.transfers.ship', $trf->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-slate-800 text-white px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-black transition-all active:scale-95 shadow-sm">
                                                Confirmar Salida
                                            </button>
                                        </form>
                                    @endif

                                    @if($trf->status == 'in_transit')
                                        {{-- ACCIÓN: Recibir en Destino --}}
                                        <form action="{{ route('admin.transfers.receive', $trf->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-emerald-600 text-white px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-emerald-700 transition-all active:scale-95 shadow-sm">
                                                Recibir Mercancía
                                            </button>
                                        </form>
                                    @endif

                                    @if($trf->status == 'completed')
                                        <span class="text-[9px] font-black text-emerald-500 uppercase tracking-widest"><i class="fa-solid fa-check-double mr-1"></i> Stock Disponible</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-20 text-center text-slate-400 bg-slate-50/30">
                                <div class="flex flex-col items-center opacity-30">
                                    <div class="w-16 h-16 bg-slate-200 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-shuffle text-2xl"></i>
                                    </div>
                                    <p class="font-black uppercase tracking-widest text-[10px]">No hay traslados activos en el sistema</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($transfers->hasPages())
        <div class="p-4 bg-slate-50 border-t border-slate-100">
            {{ $transfers->links() }}
        </div>
        @endif
    </div>

    <!-- Mini Banner Informativo -->
    <div class="mt-6 bg-blue-600 rounded-3xl p-6 text-white shadow-xl shadow-blue-500/20 relative overflow-hidden flex items-center justify-between">
        <div class="relative z-10">
            <h4 class="font-black text-sm uppercase tracking-widest mb-1">Inteligencia de Consolidación</h4>
            <p class="text-[11px] opacity-80 max-w-md">El sistema detecta automáticamente pedidos con stock parcial y genera estas órdenes para completar el despacho desde la sede más eficiente.</p>
        </div>
        <div class="w-12 h-12 bg-white/10 rounded-2xl flex items-center justify-center backdrop-blur-md border border-white/20 relative z-10">
            <i class="fa-solid fa-microchip text-xl"></i>
        </div>
        <!-- Decoración -->
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/5 rounded-full blur-3xl"></div>
    </div>

@endsection