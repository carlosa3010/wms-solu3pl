@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Avisos de Envío (ASN)</h2>
            <p class="text-sm text-slate-500">Gestiona tus envíos entrantes y notifica al almacén.</p>
        </div>
        <a href="{{ route('client.asn.create') }}" class="flex items-center justify-center space-x-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
            <i data-lucide="truck" class="w-5 h-5"></i>
            <span>Crear Nuevo ASN</span>
        </a>
    </div>

    <!-- Tabla de ASNs -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        @if($asns->isEmpty())
            <div class="p-12 text-center text-slate-400">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="package-search" class="w-8 h-8 text-slate-300"></i>
                </div>
                <h3 class="text-slate-800 font-bold mb-1">No hay envíos registrados</h3>
                <p class="text-sm">Notifica tu primer envío al almacén para iniciar el proceso de recepción.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Referencia / Tracking</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha Estimada</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">SKUs</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Unidades</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Estado</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($asns as $asn)
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-xs">
                                        ASN
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-800">{{ $asn->reference_number ?? 'S/R' }}</p>
                                        <p class="text-xs text-slate-400">ID: #{{ $asn->id }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-600">
                                {{ \Carbon\Carbon::parse($asn->expected_arrival_date)->format('d M, Y') }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-slate-700">
                                {{ $asn->items->count() }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-slate-700">
                                {{ $asn->items->sum('quantity') }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-slate-100 text-slate-600',
                                        'sent' => 'bg-blue-100 text-blue-700',
                                        'in_transit' => 'bg-amber-100 text-amber-700',
                                        'received' => 'bg-emerald-100 text-emerald-700',
                                        'cancelled' => 'bg-rose-100 text-rose-700',
                                    ];
                                    $statusLabels = [
                                        'draft' => 'Borrador',
                                        'sent' => 'Enviado',
                                        'in_transit' => 'En Tránsito',
                                        'received' => 'Recibido',
                                        'cancelled' => 'Cancelado',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusColors[$asn->status] ?? 'bg-slate-100 text-slate-500' }}">
                                    {{ $statusLabels[$asn->status] ?? $asn->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-blue-600 transition-colors">
                                    <i data-lucide="eye" class="w-5 h-5"></i>
                                </button>
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