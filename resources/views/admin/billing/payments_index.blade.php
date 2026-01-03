@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-bold text-slate-800">Gestión de Pagos Recibidos</h2>
        <div class="flex gap-2">
            <!-- Filtros sencillos -->
            <a href="?status=pending" class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-xs font-bold">Pendientes</a>
            <a href="?status=approved" class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold">Aprobados</a>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4 font-bold text-slate-500 text-xs uppercase">Cliente</th>
                    <th class="px-6 py-4 font-bold text-slate-500 text-xs uppercase">Referencia / Método</th>
                    <th class="px-6 py-4 font-bold text-slate-500 text-xs uppercase">Monto</th>
                    <th class="px-6 py-4 font-bold text-slate-500 text-xs uppercase">Fecha</th>
                    <th class="px-6 py-4 font-bold text-slate-500 text-xs uppercase text-center">Comprobante</th>
                    <th class="px-6 py-4 font-bold text-slate-500 text-xs uppercase text-center">Estado</th>
                    <th class="px-6 py-4 font-bold text-slate-500 text-xs uppercase text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($payments as $payment)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4">
                        <div class="font-bold text-slate-800">{{ $payment->client->name }}</div>
                        <div class="text-xs text-slate-500">ID: {{ $payment->client_id }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-mono font-bold text-slate-700">{{ $payment->reference }}</div>
                        <div class="text-xs text-slate-500 uppercase">{{ $payment->payment_method }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="font-bold text-slate-800">${{ number_format($payment->amount, 2) }}</span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        {{ \Carbon\Carbon::parse($payment->payment_date)->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($payment->proof_path)
                            <a href="{{ asset('storage/' . $payment->proof_path) }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-xs font-bold">
                                <i data-lucide="file-check" class="w-4 h-4"></i> Ver
                            </a>
                        @else
                            <span class="text-xs text-slate-400">Sin archivo</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-center">
                        @if($payment->status == 'pending')
                            <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded text-xs font-bold">Pendiente</span>
                        @elseif($payment->status == 'approved')
                            <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded text-xs font-bold">Acreditado</span>
                        @else
                            <span class="px-2 py-1 bg-rose-100 text-rose-700 rounded text-xs font-bold">Rechazado</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 text-right">
                        @if($payment->status == 'pending')
                        <div class="flex items-center justify-end gap-2">
                            <form action="{{ route('admin.billing.payments.approve', $payment->id) }}" method="POST" onsubmit="return confirm('¿Acreditar este pago?');">
                                @csrf
                                <button type="submit" class="p-2 bg-emerald-100 text-emerald-600 hover:bg-emerald-200 rounded-lg transition-colors" title="Aprobar">
                                    <i data-lucide="check" class="w-4 h-4"></i>
                                </button>
                            </form>
                            <form action="{{ route('admin.billing.payments.reject', $payment->id) }}" method="POST" onsubmit="return confirm('¿Rechazar este pago?');">
                                @csrf
                                <button type="submit" class="p-2 bg-rose-100 text-rose-600 hover:bg-rose-200 rounded-lg transition-colors" title="Rechazar">
                                    <i data-lucide="x" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection