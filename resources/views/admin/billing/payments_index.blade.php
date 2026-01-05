@extends('layouts.admin')

@section('title', 'Gestión de Pagos')
@section('header_title', 'Pagos y Billetera')

@section('content')

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6" x-data="{ showManualPayment: false }">
    
    {{-- Columna Izquierda: Listado de Pagos Pendientes --}}
    <div class="lg:col-span-2 space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-black text-slate-800 text-sm uppercase tracking-wide">Pagos por Aprobar</h3>
                <span class="bg-amber-100 text-amber-700 text-xs font-bold px-2 py-0.5 rounded-full">{{ $payments->where('status', 'pending')->count() }} Pendientes</span>
            </div>
            
            <div class="divide-y divide-slate-50">
                @forelse($payments as $payment)
                    <div class="p-4 hover:bg-slate-50 transition flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center text-lg {{ $payment->status == 'pending' ? 'bg-amber-100 text-amber-600' : ($payment->status == 'approved' ? 'bg-emerald-100 text-emerald-600' : 'bg-red-100 text-red-600') }}">
                                <i class="fa-solid fa-money-bill-transfer"></i>
                            </div>
                            <div>
                               <h4 class="font-bold text-slate-700 text-sm">{{ $payment->client->company_name }}</h4>
                                <p class="text-xs text-slate-500">
                                    {{ $payment->payment_method }} • Ref: <span class="font-mono">{{ $payment->reference }}</span>
                                </p>
                                <p class="text-[10px] text-slate-400 mt-0.5">{{ $payment->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        
                        <div class="flex items-center gap-4">
                            <span class="font-black text-slate-800 text-base">${{ number_format($payment->amount, 2) }}</span>
                            
                            @if($payment->status == 'pending')
                                <div class="flex gap-1">
                                    <form action="{{ route('admin.billing.payments.approve', $payment->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 hover:bg-emerald-600 hover:text-white transition flex items-center justify-center" title="Aprobar">
                                            <i class="fa-solid fa-check"></i>
                                        </button>
                                    </form>
                                    <form action="{{ route('admin.billing.payments.reject', $payment->id) }}" method="POST" onsubmit="return confirm('¿Rechazar pago?')">
                                        @csrf
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 hover:bg-rose-600 hover:text-white transition flex items-center justify-center" title="Rechazar">
                                            <i class="fa-solid fa-xmark"></i>
                                        </button>
                                    </form>
                                    <a href="{{ Storage::url($payment->proof_path) }}" target="_blank" class="w-8 h-8 rounded-lg bg-blue-100 text-blue-600 hover:bg-blue-600 hover:text-white transition flex items-center justify-center" title="Ver Comprobante">
                                        <i class="fa-solid fa-file-invoice"></i>
                                    </a>
                                </div>
                            @else
                                <span class="text-[10px] uppercase font-bold px-2 py-1 rounded-lg {{ $payment->status == 'approved' ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' }}">
                                    {{ $payment->status == 'approved' ? 'Aprobado' : 'Rechazado' }}
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="p-8 text-center text-slate-400 text-sm italic">No hay registros de pagos recientes.</div>
                @endforelse
            </div>
            
            <div class="p-4 border-t border-slate-100">
                {{ $payments->links() }}
            </div>
        </div>
    </div>

    {{-- Columna Derecha: Acciones Rápidas --}}
    <div class="space-y-6">
        <div class="bg-indigo-600 rounded-2xl p-6 text-white shadow-xl shadow-indigo-500/30">
            <h3 class="font-black text-lg mb-1">Recarga Manual</h3>
            <p class="text-xs text-indigo-200 mb-4">Acreditar saldo a billetera o registrar pago manual.</p>
            <button @click="showManualPayment = true" class="w-full bg-white text-indigo-600 py-2.5 rounded-xl font-bold text-xs uppercase tracking-wider hover:bg-indigo-50 transition shadow-sm">
                <i class="fa-solid fa-plus mr-1"></i> Registrar Transacción
            </button>
        </div>
    </div>

    {{-- Modal Pago Manual --}}
    <div x-show="showManualPayment" style="display: none;" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm" x-transition.opacity>
        <div class="bg-white rounded-2xl w-full max-w-sm shadow-2xl" @click.away="showManualPayment = false">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-black text-slate-800">Registro Manual Admin</h3>
                <button @click="showManualPayment = false" class="text-slate-400 hover:text-rose-500"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.billing.payments.manual.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Cliente Destino</label>
                    <select name="client_id" required class="w-full border border-slate-200 rounded-lg p-2 text-sm bg-white">
                        <option value="">Seleccionar...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Tipo de Operación</label>
                    <select name="type" required class="w-full border border-slate-200 rounded-lg p-2 text-sm bg-white">
                        <option value="wallet_recharge">Recarga de Billetera (Saldo Envíos)</option>
                        <option value="invoice_payment">Pago de Factura (Servicios)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Monto ($)</label>
                    <input type="number" step="0.01" name="amount" required class="w-full border border-slate-200 rounded-lg p-2 text-sm" placeholder="0.00">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Referencia / Nota</label>
                    <input type="text" name="reference" required class="w-full border border-slate-200 rounded-lg p-2 text-sm" placeholder="Ej: Depósito efectivo #1234">
                </div>

                <button type="submit" class="w-full bg-indigo-600 text-white py-2.5 rounded-lg font-bold text-xs uppercase hover:bg-indigo-700 transition">Procesar</button>
            </form>
        </div>
    </div>

</div>
@endsection