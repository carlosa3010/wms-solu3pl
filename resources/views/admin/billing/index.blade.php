@extends('layouts.admin')

@section('title', 'Facturación')
@section('header_title', 'Gestión Financiera')

@section('content')

    <!-- Resumen de KPIs Financieros -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
        <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-red-500">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Por Cobrar (Vencido)</p>
            <h3 class="text-3xl font-black text-slate-800 mt-1">${{ number_format($stats['total_pending'], 2) }}</h3>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-emerald-500">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Recaudado este Mes</p>
            <h3 class="text-3xl font-black text-slate-800 mt-1">${{ number_format($stats['collected_month'], 2) }}</h3>
        </div>
        <div class="bg-white p-6 rounded-2xl shadow-sm border-l-4 border-blue-500 flex justify-between items-center">
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cargos sin Facturar</p>
                <h3 class="text-3xl font-black text-slate-800 mt-1">{{ $stats['pending_charges'] }}</h3>
            </div>
            <button class="bg-blue-600 text-white p-3 rounded-xl hover:scale-105 transition shadow-lg shadow-blue-200">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
            </div>
        </div>
    </div>

    <!-- Historial de Facturas -->
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center">
            <h3 class="font-bold text-slate-800">Facturación Reciente</h3>
            <div class="flex gap-2">
                <button class="bg-slate-100 text-slate-600 px-4 py-2 rounded-lg text-xs font-bold hover:bg-slate-200 transition">Exportar Reporte</button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Factura #</th>
                        <th class="px-6 py-4">Cliente / Socio</th>
                        <th class="px-6 py-4">Periodo</th>
                        <th class="px-6 py-4 text-center">Total</th>
                        <th class="px-6 py-4 text-center">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $invoice)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 font-mono font-bold text-custom-primary">{{ $invoice->invoice_number }}</td>
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-700">{{ $invoice->client->company_name }}</p>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $invoice->client->tax_id }}</span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $invoice->period_start->format('d/m/y') }} - {{ $invoice->period_end->format('d/m/y') }}
                            </td>
                            <td class="px-6 py-4 text-center font-black text-slate-700">
                                ${{ number_format($invoice->total_amount, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($invoice->status == 'paid')
                                    <span class="bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">Cobrada</span>
                                @else
                                    <span class="bg-red-100 text-red-700 px-2 py-1 rounded-full text-[10px] font-black uppercase tracking-tighter">Pendiente</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="#" class="text-slate-400 hover:text-custom-primary transition p-2"><i class="fa-solid fa-file-pdf"></i></a>
                                    <button class="text-slate-400 hover:text-emerald-500 transition p-2"><i class="fa-solid fa-check-double"></i></button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-20 text-center text-slate-400 italic">No se han generado facturas en este periodo.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

@endsection