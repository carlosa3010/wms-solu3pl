@extends('layouts.admin')

@section('title', 'Finanzas y Facturación')
@section('header_title', 'Panel Financiero')

@section('content')

{{-- Métricas Rápidas --}}
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pre-Facturación (Mes Actual)</p>
        <h3 class="text-2xl font-black text-slate-800">${{ number_format($openPreInvoices->sum('total_amount'), 2) }}</h3>
        <div class="mt-2 text-[10px] text-slate-400">
            <span class="text-emerald-500 font-bold"><i class="fa-solid fa-arrow-trend-up"></i> Acumulado</span> del periodo en curso
        </div>
    </div>
    <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-200">
        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Pagos Pendientes</p>
        <h3 class="text-2xl font-black text-amber-500">{{ $pendingPayments->count() }}</h3>
        <div class="mt-2 text-[10px] text-slate-400">Solicitudes por aprobar</div>
    </div>
    <div class="bg-indigo-600 p-5 rounded-2xl shadow-lg shadow-indigo-500/30 text-white relative overflow-hidden group cursor-pointer" onclick="document.getElementById('runBillingForm').submit()">
        <div class="relative z-10">
            <p class="text-xs font-bold text-indigo-200 uppercase tracking-wider mb-1">Proceso Diario</p>
            <h3 class="text-xl font-black">Ejecutar Corte</h3>
            <p class="text-[10px] text-indigo-100 mt-2">Calcula costos de hoy para todos los clientes</p>
        </div>
        <i class="fa-solid fa-gears absolute -bottom-4 -right-4 text-6xl text-indigo-500 group-hover:rotate-45 transition-transform duration-500"></i>
        <form id="runBillingForm" action="{{ route('admin.billing.run_daily') }}" method="POST" class="hidden">@csrf</form>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    {{-- Pre-Facturas Abiertas --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-black text-slate-800 text-sm uppercase tracking-wide">Cortes en Curso</h3>
            <span class="text-[10px] bg-slate-100 text-slate-500 px-2 py-1 rounded-lg font-bold">{{ now()->format('F Y') }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] text-slate-400 uppercase border-b border-slate-100">
                        <th class="pb-2 font-black">Cliente</th>
                        <th class="pb-2 font-black text-right">Acumulado</th>
                        <th class="pb-2 font-black text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    @forelse($openPreInvoices as $invoice)
                        <tr class="border-b border-slate-50 hover:bg-slate-50 transition">
                            <td class="py-3 font-bold text-slate-700">{{ $invoice->client->name }}</td>
                            <td class="py-3 font-bold text-slate-700 text-right">${{ number_format($invoice->total_amount, 2) }}</td>
                            <td class="py-3 text-center">
                                <a href="{{ route('admin.billing.pre_invoice', $invoice->client_id) }}" class="text-indigo-600 hover:text-indigo-800" title="Ver Detalle">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-center text-slate-400 italic">No hay cortes abiertos. Ejecuta el proceso diario.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Últimos Pagos Recibidos --}}
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="font-black text-slate-800 text-sm uppercase tracking-wide">Últimos Pagos</h3>
            <a href="{{ route('admin.billing.payments.index') }}" class="text-[10px] text-indigo-600 font-bold hover:underline">Ver Todos</a>
        </div>
        <div class="space-y-3">
            @forelse($pendingPayments as $payment)
                <div class="flex items-center justify-between p-3 bg-amber-50 border border-amber-100 rounded-xl">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-amber-200 text-amber-700 rounded-lg flex items-center justify-center text-xs">
                            <i class="fa-solid fa-money-bill-wave"></i>
                        </div>
                        <div>
                            <p class="text-xs font-black text-slate-700">{{ $payment->client->name }}</p>
                            <p class="text-[10px] text-slate-500">{{ $payment->payment_method }} • Ref: {{ $payment->reference }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-xs font-black text-emerald-600">+${{ number_format($payment->amount, 2) }}</p>
                        <p class="text-[9px] text-slate-400">{{ $payment->created_at->diffForHumans() }}</p>
                    </div>
                </div>
            @empty
                <p class="text-center text-slate-400 text-xs py-4 italic">No hay pagos pendientes de revisión.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection