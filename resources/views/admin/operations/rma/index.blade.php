@extends('layouts.admin')

@section('title', 'Gestión de RMA')
@section('header_title', 'Devoluciones y Garantías')

@section('content')

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Logística Inversa</h2>
            <p class="text-xs text-slate-500">Gestione el retorno de mercancía y el control de calidad.</p>
        </div>
        <div class="flex gap-2">
            <form action="{{ route('admin.rma.index') }}" method="GET" class="relative">
                <input type="text" name="search" placeholder="Buscar RMA, Cliente..." value="{{ request('search') }}" 
                    class="pl-9 pr-4 py-2 rounded-xl border-slate-200 text-sm focus:ring-custom-primary focus:border-custom-primary">
                <i class="fa-solid fa-search absolute left-3 top-3 text-slate-400 text-xs"></i>
            </form>

            <a href="{{ route('admin.rma.create') }}" class="bg-custom-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:brightness-95 transition flex items-center gap-2">
                <i class="fa-solid fa-rotate-left"></i> Nueva Devolución
            </a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                <tr>
                    <th class="px-6 py-4">RMA #</th>
                    <th class="px-6 py-4">Cliente / Origen</th>
                    <th class="px-6 py-4">Tracking / Motivo</th>
                    <th class="px-6 py-4 text-center">Estado</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($rmas as $rma)
                    @php
                        $statusStyles = [
                            'pending' => 'bg-yellow-100 text-yellow-700 border-yellow-200',
                            'approved' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                            'rejected' => 'bg-red-100 text-red-700 border-red-200',
                            'processing' => 'bg-blue-100 text-blue-700 border-blue-200',
                            'waiting_client' => 'bg-purple-100 text-purple-700 border-purple-200',
                        ];
                        $style = $statusStyles[$rma->status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                    @endphp
                    
                    <tr class="hover:bg-slate-50 transition group cursor-pointer" onclick="window.location='{{ route('admin.rma.show', $rma->id) }}'">
                        <td class="px-6 py-4">
                            <span class="font-black text-slate-700 block">{{ $rma->rma_number }}</span>
                            <span class="text-[10px] text-slate-400 font-mono font-bold">{{ $rma->created_at->format('d/m/Y') }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="font-bold text-slate-700 text-sm">{{ $rma->client->company_name }}</p>
                            <p class="text-xs text-slate-500 flex items-center gap-1">
                                <i class="fa-solid fa-user text-[10px]"></i> {{ $rma->customer_name }}
                            </p>
                        </td>
                        <td class="px-6 py-4 text-xs">
                            @if($rma->tracking_number)
                                <span class="bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded font-mono font-bold mb-1 inline-block">
                                    <i class="fa-solid fa-truck-fast mr-1"></i> {{ $rma->tracking_number }}
                                </span>
                            @endif
                            <p class="text-slate-500 italic max-w-[200px] truncate">"{{ $rma->reason }}"</p>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="{{ $style }} px-3 py-1 rounded-full text-[10px] font-black uppercase border tracking-wide">
                                {{ ucfirst(str_replace('_', ' ', $rma->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('admin.rma.show', $rma->id) }}" class="text-slate-400 hover:text-custom-primary transition p-2 rounded-full hover:bg-blue-50">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-20 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="bg-slate-100 rounded-full p-6 mb-4">
                                    <i class="fa-solid fa-box-open text-4xl text-slate-300"></i>
                                </div>
                                <p class="text-slate-500 font-medium">No hay devoluciones registradas.</p>
                                <p class="text-xs text-slate-400 mt-1">Utilice el botón superior para crear una nueva solicitud.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $rmas->withQueryString()->links() }}
    </div>

@endsection