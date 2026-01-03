@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <!-- Encabezado con estadísticas rápidas -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Avisos de Envío (ASN)</h2>
            <p class="text-sm text-slate-500">Notifica tus llegadas para agilizar la descarga en el warehouse.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="hidden sm:flex items-center gap-2 px-4 py-2 bg-slate-50 rounded-xl border border-slate-100">
                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">En Camino:</span>
                <span class="text-sm font-black text-blue-600">{{ $asns->where('status', 'sent')->count() }}</span>
            </div>
            <a href="{{ route('client.asn.create') }}" class="flex items-center justify-center space-x-2 bg-blue-600 text-white px-6 py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-700 transition-all shadow-xl shadow-blue-500/20 active:scale-95">
                <i data-lucide="truck" class="w-4 h-4"></i>
                <span>Crear Nuevo ASN</span>
            </a>
        </div>
    </div>

    <!-- Alertas -->
    @if(session('success'))
        <div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-4 rounded-xl shadow-sm animate-fade-in flex justify-between items-center">
            <p class="text-sm font-bold"><i data-lucide="check-circle" class="w-4 h-4 inline mr-2"></i> {{ session('success') }}</p>
        </div>
    @endif

    <!-- Tabla de ASNs -->
    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        @if($asns->isEmpty())
            <div class="p-20 text-center text-slate-400">
                <div class="w-20 h-20 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4 border border-slate-100">
                    <i data-lucide="package-search" class="w-10 h-10 text-slate-200"></i>
                </div>
                <h3 class="text-slate-800 font-black text-lg">No hay envíos notificados</h3>
                <p class="text-sm max-w-xs mx-auto mt-2">Registra tu primer ASN para que nuestro equipo de warehouse esté preparado para recibirte.</p>
                <a href="{{ route('client.asn.create') }}" class="mt-6 inline-flex text-blue-600 font-bold text-xs uppercase tracking-widest hover:underline italic">Empezar ahora →</a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50/50 border-b border-slate-100">
                        <tr>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Identificación / Ref</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest">Llegada Est.</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Bultos</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">SKUs / Unidades</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Estado</th>
                            <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Documentación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($asns as $asn)
                        <tr class="hover:bg-slate-50/50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center font-black text-[10px] border border-blue-100 shadow-sm">
                                        ASN
                                    </div>
                                    <div>
                                        <p class="text-sm font-black text-slate-800 tracking-tight">{{ $asn->asn_number ?? 'ID #'.$asn->id }}</p>
                                        <p class="text-[10px] text-slate-400 font-bold uppercase truncate max-w-[150px]">{{ $asn->reference_number ?? 'Sin Referencia' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-600">{{ \Carbon\Carbon::parse($asn->expected_arrival_date)->format('d M, Y') }}</span>
                                    <span class="text-[9px] text-slate-400 font-bold uppercase">{{ \Carbon\Carbon::parse($asn->expected_arrival_date)->diffForHumans() }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="bg-slate-100 text-slate-700 px-3 py-1 rounded-lg text-xs font-black border border-slate-200">
                                    {{ $asn->total_packages ?? 1 }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-700">{{ $asn->items->count() }} SKUs</span>
                                    <span class="text-[10px] text-slate-400 font-bold">{{ $asn->items->sum('expected_quantity') }} Unidades</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusConfig = [
                                        'draft' => ['class' => 'bg-slate-100 text-slate-500 border-slate-200', 'label' => 'Borrador'],
                                        'sent' => ['class' => 'bg-blue-100 text-blue-700 border-blue-200', 'label' => 'En Camino'],
                                        'in_transit' => ['class' => 'bg-amber-100 text-amber-700 border-amber-200', 'label' => 'En Tránsito'],
                                        'received' => ['class' => 'bg-emerald-100 text-emerald-700 border-emerald-200', 'label' => 'Recibido'],
                                        'cancelled' => ['class' => 'bg-rose-100 text-rose-700 border-rose-200', 'label' => 'Cancelado'],
                                    ];
                                    $current = $statusConfig[$asn->status] ?? ['class' => 'bg-slate-100', 'label' => $asn->status];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border {{ $current['class'] }}">
                                    {{ $current['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <!-- BOTÓN IMPRIMIR ETIQUETAS -->
                                    <a href="{{ route('client.asn.label', $asn->id) }}" target="_blank" class="p-2.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all border border-transparent hover:border-blue-100" title="Imprimir Etiquetas de Bulto">
                                        <i data-lucide="printer" class="w-4 h-4"></i>
                                    </a>
                                    
                                    <div class="h-4 w-px bg-slate-100 mx-1"></div>

                                    <a href="{{ route('client.asn.show', $asn->id) }}" class="p-2.5 text-slate-400 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition-all" title="Ver Detalle">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <!-- Info Footer -->
    <div class="bg-blue-600 rounded-3xl p-6 text-white shadow-xl shadow-blue-500/20 flex items-center justify-between relative overflow-hidden">
        <div class="relative z-10">
            <h4 class="text-sm font-black uppercase tracking-widest mb-1">Recepción Express con Etiquetas</h4>
            <p class="text-[11px] opacity-80 max-w-md font-medium leading-relaxed">Cada bulto de tu ASN debe llevar su etiqueta. Al llegar a bodega, nuestro equipo escanea el código y sincroniza tu inventario en segundos.</p>
        </div>
        <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-md relative z-10 border border-white/30">
            <i data-lucide="scan" class="w-6 h-6"></i>
        </div>
        <!-- Decoración -->
        <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-white/10 rounded-full blur-3xl"></div>
    </div>
</div>
@endsection