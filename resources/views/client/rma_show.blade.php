@extends('layouts.client_layout')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    
    <!-- Header y Navegación -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('client.rma') }}" class="w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 hover:text-blue-600 hover:border-blue-200 transition-all">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h2 class="text-2xl font-black text-slate-800">RMA #{{ $rma->id }}</h2>
                <p class="text-sm text-slate-500">Detalles de la inspección y resolución.</p>
            </div>
        </div>
        <div class="text-right">
            <span class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Estado Actual</span>
            @if($rma->status == 'waiting_client')
                <span class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-black uppercase tracking-wider flex items-center gap-2">
                    <span class="w-2 h-2 bg-amber-500 rounded-full animate-pulse"></span> Esperando tu Aprobación
                </span>
            @else
                <span class="px-3 py-1 bg-slate-100 text-slate-600 rounded-full text-xs font-black uppercase tracking-wider">
                    {{ ucfirst($rma->status) }}
                </span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Columna Izquierda: Información y Productos -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Productos Devueltos -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide">Productos Inspeccionados</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach($rma->items as $item)
                    <div class="p-6 flex gap-4">
                        <div class="w-16 h-16 bg-slate-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i data-lucide="package" class="w-8 h-8 text-slate-300"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex justify-between items-start">
                                <div>
                                    <h4 class="font-bold text-slate-800">{{ $item->product->name ?? 'Producto Desconocido' }}</h4>
                                    <p class="text-xs text-slate-500 font-mono mt-1">SKU: {{ $item->product->sku ?? 'N/A' }}</p>
                                </div>
                                <div class="text-right">
                                    <span class="block text-sm font-black text-slate-900">{{ $item->quantity }} unds.</span>
                                </div>
                            </div>
                            
                            <!-- Reporte del Operador -->
                            <div class="mt-4 p-3 bg-slate-50 rounded-xl border border-slate-100">
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Reporte de Almacén:</p>
                                <p class="text-xs text-slate-600 font-medium">
                                    {{ $item->notes ?? 'Sin observaciones específicas.' }}
                                </p>
                                <div class="mt-2 flex gap-2">
                                    <span class="px-2 py-0.5 bg-white border border-slate-200 rounded text-[10px] font-bold text-slate-500">
                                        Condición: {{ $item->condition ?? 'No especificada' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- Evidencia Fotográfica -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wide mb-4 flex items-center gap-2">
                    <i data-lucide="camera" class="w-4 h-4 text-blue-600"></i>
                    Evidencia Fotográfica
                </h3>
                
                @if($rma->images && count($rma->images) > 0)
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        @foreach($rma->images as $img)
                            <div class="group relative aspect-square bg-slate-100 rounded-xl overflow-hidden cursor-zoom-in border border-slate-200">
                                <img src="{{ asset('storage/' . $img->path) }}" alt="Evidencia RMA" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/20 transition-colors"></div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-8 bg-slate-50 rounded-xl border border-slate-100 border-dashed">
                        <i data-lucide="image-off" class="w-8 h-8 text-slate-300 mx-auto mb-2"></i>
                        <p class="text-xs text-slate-500 font-medium">El operador no ha subido fotos aún.</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Columna Derecha: Acciones y Resolución -->
        <div class="space-y-6">
            <!-- Panel de Decisión -->
            @if($rma->status == 'waiting_client')
                <div class="bg-white rounded-2xl border border-blue-100 shadow-lg shadow-blue-50 p-6 relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-20 h-20 bg-blue-50 rounded-bl-full -mr-10 -mt-10 z-0"></div>
                    
                    <div class="relative z-10">
                        <h3 class="font-bold text-slate-800 mb-2">Acción Requerida</h3>
                        <p class="text-xs text-slate-500 mb-6 leading-relaxed">
                            El almacén ha completado la inspección. Por favor revisa las fotos y confirma si aceptas la resolución propuesta (Reembolso / Cambio).
                        </p>

                        <form action="{{ route('client.rma.action', $rma->id) }}" method="POST" class="space-y-3">
                            @csrf
                            <button type="submit" name="action" value="approve" class="w-full py-3 bg-emerald-500 hover:bg-emerald-600 text-white rounded-xl font-bold text-sm shadow-lg shadow-emerald-200 transition-all flex items-center justify-center gap-2">
                                <i data-lucide="check-circle" class="w-4 h-4"></i>
                                Aprobar Resolución
                            </button>
                            
                            <button type="submit" name="action" value="reject" class="w-full py-3 bg-white border border-rose-200 text-rose-600 hover:bg-rose-50 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2">
                                <i data-lucide="x-circle" class="w-4 h-4"></i>
                                Rechazar / Disputar
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="bg-slate-50 rounded-2xl border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-800 text-sm mb-2">Proceso Cerrado</h3>
                    <p class="text-xs text-slate-500">
                        Este RMA se encuentra en estado <strong>{{ $rma->status }}</strong> y ya no requiere acciones por tu parte.
                    </p>
                    @if($rma->resolution_notes)
                        <div class="mt-4 pt-4 border-t border-slate-200">
                            <p class="text-[10px] font-bold text-slate-400 uppercase">Notas Finales</p>
                            <p class="text-xs text-slate-700 mt-1">{{ $rma->resolution_notes }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Notas del Admin -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-bold text-slate-800 text-sm mb-4">Notas Administrativas</h3>
                <div class="space-y-4">
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-xs shrink-0">
                            OP
                        </div>
                        <div class="bg-slate-50 p-3 rounded-tr-xl rounded-br-xl rounded-bl-xl text-xs text-slate-600 leading-relaxed">
                            {{ $rma->admin_notes ?? 'No hay notas del operador visibles.' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection