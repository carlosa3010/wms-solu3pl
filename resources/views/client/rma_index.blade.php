@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Gestión de Devoluciones (RMA)</h2>
            <p class="text-sm text-slate-500">Solicita devoluciones y revisa la evidencia subida por el almacén.</p>
        </div>
        <button class="flex items-center justify-center space-x-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100 opacity-50 cursor-not-allowed" title="Contacta a soporte para iniciar un RMA" disabled>
            <i data-lucide="plus-circle" class="w-5 h-5"></i>
            <span>Solicitar RMA</span>
        </button>
    </div>

    <!-- Tabla de RMAs -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @if($rmas->isEmpty())
            <div class="p-12 text-center text-slate-400">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="rotate-ccw" class="w-8 h-8 text-slate-300"></i>
                </div>
                <h3 class="text-slate-800 font-bold mb-1">No hay devoluciones activas</h3>
                <p class="text-sm">El historial de tus devoluciones aparecerá aquí.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Folio RMA</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Pedido Origen</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha Solicitud</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Items</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Estado</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($rmas as $rma)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-xs">
                                        RMA
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">#{{ $rma->id }}</p>
                                        <p class="text-xs text-slate-400">{{ $rma->type ?? 'Devolución' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-mono font-bold text-blue-600">
                                    {{ $rma->order ? $rma->order->order_number : 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-600">
                                {{ $rma->created_at->format('d M, Y') }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-slate-700">
                                {{ $rma->items->count() }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusClasses = [
                                        'pending' => 'bg-slate-100 text-slate-600',
                                        'processing' => 'bg-blue-100 text-blue-700',
                                        'waiting_client' => 'bg-amber-100 text-amber-700 animate-pulse',
                                        'approved' => 'bg-emerald-100 text-emerald-700',
                                        'rejected' => 'bg-rose-100 text-rose-700',
                                    ];
                                    $statusLabels = [
                                        'pending' => 'Pendiente',
                                        'processing' => 'En Revisión',
                                        'waiting_client' => 'Acción Requerida',
                                        'approved' => 'Aprobado',
                                        'rejected' => 'Rechazado',
                                    ];
                                    $currentStatus = $rma->status ?? 'pending';
                                @endphp
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusClasses[$currentStatus] ?? 'bg-slate-100 text-slate-500' }}">
                                    {{ $statusLabels[$currentStatus] ?? $currentStatus }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('client.rma.show', $rma->id) }}" class="inline-flex items-center justify-center px-3 py-1.5 border border-slate-200 rounded-lg text-xs font-bold text-slate-600 hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition-all">
                                    Ver Detalles
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@endsection