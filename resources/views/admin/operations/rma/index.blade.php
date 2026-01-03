@extends('layouts.admin')

@section('title', 'Gestión de RMA')
@section('header_title', 'Devoluciones y Garantías')

@section('content')

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Logística Inversa</h2>
            <p class="text-xs text-slate-500">Gestione el retorno de mercancía y el control de calidad.</p>
        </div>
        <a href="{{ route('admin.rma.create') }}" class="bg-custom-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg flex items-center gap-2">
            <i class="fa-solid fa-rotate-left"></i> Nueva Devolución
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                <tr>
                    <th class="px-6 py-4">RMA #</th>
                    <th class="px-6 py-4">Cliente / Origen</th>
                    <th class="px-6 py-4">Motivo</th>
                    <th class="px-6 py-4 text-center">Estado</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rmas as $rma)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <span class="font-bold text-slate-700">{{ $rma->rma_number }}</span>
                            <p class="text-[9px] text-slate-400 font-mono">ORD: {{ $rma->order->order_number ?? 'S/R' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-600">{{ $rma->client->company_name }}</p>
                            <p class="text-xs text-slate-400">{{ $rma->customer_name }}</p>
                        </td>
                        <td class="px-6 py-4 text-xs">
                            <span class="bg-slate-100 px-2 py-1 rounded text-slate-600 font-medium italic">
                                "{{ $rma->reason }}"
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="bg-{{ $rma->status_color }}-100 text-{{ $rma->status_color }}-700 px-3 py-1 rounded-full text-[10px] font-black uppercase border border-{{ $rma->status_color }}-200">
                                {{ $rma->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <button class="text-slate-400 hover:text-custom-primary p-2"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-20 text-center text-slate-400 italic">No hay registros de RMA.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

@endsection