@extends('layouts.admin')

@section('title', 'Detalle de ASN')
@section('header_title', 'Recepción #' . $asn->asn_number)

@section('content')

    <div class="max-w-6xl mx-auto">
        
        <!-- Navegación Breadcrumb -->
        <nav class="flex items-center text-sm text-slate-500 mb-6">
            <a href="{{ route('admin.receptions.index') }}" class="hover:text-custom-primary transition">Recepciones</a>
            <i class="fa-solid fa-chevron-right text-[10px] mx-2"></i>
            <span class="font-bold text-slate-700">{{ $asn->asn_number }}</span>
        </nav>

        <!-- Info General -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 mb-6 flex flex-col md:flex-row justify-between gap-6 relative overflow-hidden">
            
            <!-- Banda de estado lateral -->
            @php
                $statusColor = match($asn->status) {
                    'pending' => 'bg-yellow-500',
                    'receiving' => 'bg-blue-500',
                    'completed' => 'bg-green-500',
                    'cancelled' => 'bg-red-500',
                    default => 'bg-slate-500'
                };
            @endphp
            <div class="absolute left-0 top-0 bottom-0 w-1 {{ $statusColor }}"></div>

            <div>
                <div class="flex items-center gap-3 mb-2">
                    <h2 class="text-2xl font-black text-slate-800 tracking-tight">{{ $asn->asn_number }}</h2>
                    <span class="{{ $statusColor }} text-white px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider">
                        {{ $asn->status }}
                    </span>
                </div>
                <div class="flex flex-col gap-1">
                    <p class="text-sm font-bold text-slate-700 flex items-center gap-2">
                        <i class="fa-solid fa-briefcase text-slate-400"></i> {{ $asn->client->company_name }}
                    </p>
                    <p class="text-xs text-slate-500 flex items-center gap-2">
                        <i class="fa-solid fa-file-invoice text-slate-400"></i> Ref: {{ $asn->document_ref ?? 'N/A' }}
                    </p>
                </div>
            </div>
            
            <div class="flex flex-wrap gap-6 md:gap-12 items-center">
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Llegada Estimada</p>
                    <p class="font-bold text-slate-700 text-lg">
                        <i class="fa-regular fa-calendar mr-1 text-custom-primary"></i> 
                        {{ $asn->expected_arrival_date->format('d M, Y') }}
                    </p>
                </div>
                
                <div class="text-right">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Transporte</p>
                    <p class="font-bold text-slate-700 text-lg">
                        <i class="fa-solid fa-truck-fast mr-1 text-custom-primary"></i> 
                        {{ $asn->carrier_name ?? '---' }}
                    </p>
                    @if($asn->tracking_number)
                        <p class="text-[10px] text-slate-500 font-mono">{{ $asn->tracking_number }}</p>
                    @endif
                </div>
            </div>
        </div>

        @if($asn->notes)
            <div class="bg-yellow-50 border border-yellow-100 rounded-xl p-4 mb-6 flex gap-3 text-yellow-800 text-sm">
                <i class="fa-regular fa-note-sticky mt-0.5"></i>
                <p><strong>Nota de Recepción:</strong> {{ $asn->notes }}</p>
            </div>
        @endif

        <!-- PLAN DE RECEPCIÓN (Resultado de la IA) -->
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-bold text-slate-700 text-lg flex items-center gap-2">
                <i class="fa-solid fa-list-check text-custom-primary"></i> Plan de Recepción y Ubicación
            </h3>
            <span class="text-xs text-slate-500 bg-white px-3 py-1 rounded-full border border-slate-200 font-bold shadow-sm">
                Total Items: {{ $asn->items->count() }}
            </span>
        </div>

        <div class="space-y-4">
            @foreach($asn->items as $item)
                <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-sm hover:shadow-md transition-all">
                    <!-- Cabecera del Item -->
                    <div class="bg-slate-50 p-4 border-b border-slate-100 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-white rounded-lg border border-slate-200 flex items-center justify-center text-slate-400 shadow-sm">
                                <i class="fa-solid fa-box-open text-xl"></i>
                            </div>
                            <div>
                                <p class="font-bold text-slate-800 text-base">{{ $item->product->name }}</p>
                                <p class="text-xs text-slate-500 font-mono font-bold text-custom-primary bg-blue-50 px-2 py-0.5 rounded w-fit mt-1">
                                    {{ $item->product->sku }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-6">
                            <div class="text-right">
                                <span class="block text-2xl font-black text-slate-800 leading-none">{{ $item->expected_quantity }}</span>
                                <span class="text-[9px] text-slate-400 uppercase font-bold tracking-wider">Esperado</span>
                            </div>
                            <!-- Barra visual de estado (Pendiente) -->
                            <div class="w-1 h-8 bg-slate-200 rounded-full"></div>
                            <div class="text-right opacity-30">
                                <span class="block text-2xl font-black text-slate-800 leading-none">{{ $item->received_quantity }}</span>
                                <span class="text-[9px] text-slate-400 uppercase font-bold tracking-wider">Recibido</span>
                            </div>
                        </div>
                    </div>

                    <!-- Asignaciones Sugeridas -->
                    <div class="p-5 bg-white">
                        @if($item->allocations->count() > 0)
                            <div class="flex items-center gap-2 mb-3">
                                <i class="fa-solid fa-wand-magic-sparkles text-purple-500"></i>
                                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Ubicaciones Sugeridas (Auto-Slotting)</p>
                            </div>
                            
                            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                                @foreach($item->allocations as $alloc)
                                    <div class="flex items-center justify-between p-3 border border-purple-100 bg-purple-50/30 rounded-lg group hover:border-purple-300 transition">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded bg-white flex items-center justify-center text-purple-500 border border-purple-100 shadow-sm">
                                                <i class="fa-solid fa-location-crosshairs"></i>
                                            </div>
                                            <div>
                                                <p class="font-black text-sm text-slate-700 font-mono">{{ $alloc->location->code }}</p>
                                                <p class="text-[10px] text-slate-500">{{ $alloc->location->warehouse->name ?? 'Bodega Principal' }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right bg-white px-2 py-1 rounded border border-purple-100">
                                            <span class="font-bold text-slate-800">{{ $alloc->quantity }}</span>
                                            <span class="text-[9px] text-slate-400 uppercase">ud</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="flex items-start gap-3 text-orange-600 bg-orange-50 p-4 rounded-lg text-sm border border-orange-100">
                                <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                                <div>
                                    <p class="font-bold">Asignación Manual Requerida</p>
                                    <p class="text-xs opacity-80 mt-1">El sistema no encontró bines óptimos disponibles automáticamente. El operario deberá escanear una ubicación vacía al recibir.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Footer de Acciones -->
        <div class="mt-8 pt-6 border-t border-slate-200 flex flex-col md:flex-row justify-end gap-4 sticky bottom-0 bg-slate-100/90 backdrop-blur py-4 -mx-4 px-4 md:mx-0 md:px-0 z-10">
            
            <a href="{{ route('admin.receptions.index') }}" class="px-6 py-3 bg-white border border-slate-300 text-slate-600 font-bold rounded-xl hover:bg-slate-50 transition text-center shadow-sm">
                Volver al Listado
            </a>

            <!-- Botón Imprimir Etiquetas -->
            <a href="{{ route('admin.receptions.labels', $asn->id) }}" target="_blank" class="px-6 py-3 bg-slate-800 text-white font-bold rounded-xl hover:bg-slate-700 flex items-center justify-center gap-2 transition shadow-lg shadow-slate-900/20">
                <i class="fa-solid fa-print"></i> Imprimir Etiquetas
            </a>
            
            <!-- Botón Iniciar Recepción (Solo visual por ahora, para módulo Warehouse) -->
            @if($asn->status === 'pending' || $asn->status === 'draft')
                <button disabled class="px-6 py-3 bg-green-600/50 text-white font-bold rounded-xl cursor-not-allowed flex items-center justify-center gap-2 opacity-70" title="Disponible en Panel Operario">
                    <i class="fa-solid fa-dolly"></i> Iniciar Recepción (App Bodega)
                </button>
            @endif
        </div>

    </div>

@endsection