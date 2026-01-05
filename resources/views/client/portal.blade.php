@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <!-- Encabezado de Bienvenida -->
    <div class="flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-black text-slate-800">Panel de Control</h2>
            <p class="text-slate-500 text-sm">Resumen operativo y financiero de tu cuenta.</p>
        </div>
        <div class="text-right">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Ciclo Actual</span>
            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold">{{ now()->translatedFormat('F Y') }}</span>
        </div>
    </div>

    <!-- Indicadores Principales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <!-- Saldo Billetera -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-emerald-500">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">Saldo Disponible</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">${{ number_format($wallet->balance ?? 0, 2) }}</p>
                </div>
                <div class="bg-emerald-50 p-2 rounded-lg text-emerald-600">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                </div>
            </div>
            <button onclick="openModal('modalRecharge')" class="mt-4 text-[10px] bg-emerald-600 text-white px-3 py-1.5 rounded-lg font-black uppercase hover:bg-emerald-700 transition shadow-md shadow-emerald-100 flex items-center">
                <i data-lucide="plus" class="w-3 h-3 mr-1"></i> Recargar Saldo
            </button>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">Corte de Cuenta</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">${{ number_format($corteCuenta ?? 0, 2) }}</p>
                </div>
                <div class="bg-blue-50 p-2 rounded-lg text-blue-600">
                    <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                </div>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 italic">Acumulado mes en curso</p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">RMAs Pendientes</p>
                    <p class="text-2xl font-black text-slate-900 mt-1 text-rose-600">{{ $pendingRmas ?? 0 }}</p>
                </div>
                <div class="bg-rose-50 p-2 rounded-lg text-rose-600">
                    <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">Envíos Activos</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ $activeAsns ?? 0 }}</p>
                </div>
                <div class="bg-indigo-50 p-2 rounded-lg text-indigo-600">
                    <i data-lucide="truck" class="w-5 h-5"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna Izquierda: Acciones y Pedidos Recientes -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h3 class="font-bold text-slate-800 mb-6 flex items-center">
                    <i data-lucide="zap" class="mr-2 text-amber-500 w-5 h-5"></i> 
                    Acciones Rápidas
                </h3>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <a href="{{ route('client.asn.create') }}" class="flex flex-col items-center p-6 border border-slate-100 rounded-2xl hover:bg-blue-50 hover:border-blue-200 transition-all group">
                        <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3 group-hover:bg-white">
                            <i data-lucide="plus-square" class="text-slate-400 group-hover:text-blue-600"></i>
                        </div>
                        <span class="text-sm font-bold text-slate-600">Crear ASN</span>
                    </a>
                    
                    <a href="{{ route('client.orders.create') }}" class="flex flex-col items-center p-6 border border-slate-100 rounded-2xl hover:bg-emerald-50 hover:border-emerald-200 transition-all group">
                        <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3 group-hover:bg-white">
                            <i data-lucide="shopping-cart" class="text-slate-400 group-hover:text-emerald-600"></i>
                        </div>
                        <span class="text-sm font-bold text-slate-600">Nuevo Pedido</span>
                    </a>

                    <a href="{{ route('client.catalog') }}" class="flex flex-col items-center p-6 border border-slate-100 rounded-2xl hover:bg-purple-50 hover:border-purple-200 transition-all group">
                        <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3 group-hover:bg-white">
                            <i data-lucide="package-plus" class="text-slate-400 group-hover:text-purple-600"></i>
                        </div>
                        <span class="text-sm font-bold text-slate-600">Gestionar SKU</span>
                    </a>
                </div>
            </div>

            <!-- Tabla de Pedidos Recientes -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700">Últimos Pedidos</h3>
                    <a href="{{ route('client.orders.index') }}" class="text-xs font-bold text-blue-600 hover:underline">Ver todos</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <tbody class="divide-y divide-slate-100">
                            @forelse($recentOrders as $order)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-xs font-bold text-slate-700">
                                    {{ $order->order_number }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ $order->customer_name }}
                                </td>
                                <td class="px-6 py-4 text-xs text-slate-500">
                                    {{ $order->created_at->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @php
                                        $statusClasses = [
                                            'pending' => 'bg-slate-100 text-slate-600',
                                            'processing' => 'bg-blue-100 text-blue-700',
                                            'shipped' => 'bg-emerald-100 text-emerald-700',
                                            'completed' => 'bg-gray-800 text-white',
                                            'cancelled' => 'bg-rose-100 text-rose-700',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-[10px] font-black uppercase {{ $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-500' }}">
                                        {{ $order->status }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-slate-400 text-sm">
                                    No hay actividad reciente.
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Módulo de Estado de Cuenta -->
        <div class="space-y-6 h-fit">
            <div class="bg-slate-900 text-white rounded-3xl p-8 shadow-2xl relative overflow-hidden">
                <div class="relative z-10">
                    <h3 class="font-bold text-xl mb-2">Estado de Cuenta</h3>
                    <p class="text-slate-400 text-sm mb-8">Descarga tu pre-factura del ciclo en curso para revisión.</p>
                    
                    <div class="p-4 bg-white/5 rounded-2xl border border-white/10 mb-8">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-xs text-slate-400">Total acumulado</span>
                            <span class="text-lg font-black text-blue-400">${{ number_format($corteCuenta ?? 0, 2) }}</span>
                        </div>
                        <div class="w-full bg-white/10 h-1.5 rounded-full">
                            <div class="bg-blue-500 h-1.5 rounded-full" style="width: 45%"></div>
                        </div>
                    </div>

                    <a href="{{ route('client.billing.download') }}" class="w-full py-4 bg-blue-600 hover:bg-blue-700 rounded-2xl font-black flex items-center justify-center space-x-2 transition-all shadow-lg shadow-blue-900/40">
                        <i data-lucide="download-cloud"></i>
                        <span>Descargar Prefactura</span>
                    </a>
                </div>
                <!-- Decoración -->
                <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl"></div>
            </div>

            <!-- Tus SKUs Info -->
            <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm flex items-center gap-4">
                <div class="w-12 h-12 bg-purple-100 text-purple-600 rounded-xl flex items-center justify-center">
                    <i data-lucide="layers" class="w-6 h-6"></i>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Catálogo</p>
                    <p class="text-lg font-black text-slate-800">{{ $productsCount ?? 0 }} Productos</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL RECARGAR BILLETERA --}}
<div id="modalRecharge" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[100] flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-300">
        <form action="{{ route('client.billing.store_payment') }}" method="POST">
            @csrf
            {{-- Indicamos que es una recarga de billetera --}}
            <input type="hidden" name="type" value="wallet_recharge">
            
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-black text-slate-800 uppercase tracking-tight text-sm">Reportar Recarga de Saldo</h3>
                <button type="button" onclick="closeModal('modalRecharge')" class="text-slate-400 hover:text-slate-600 transition"><i data-lucide="x" class="w-5 h-5"></i></button>
            </div>
            
            <div class="p-8 space-y-5">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Método de Pago</label>
                    <select name="payment_method_id" id="payment_method_id" required onchange="showPaymentInfo(this)" class="w-full bg-slate-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-blue-500 p-3">
                        <option value="">Seleccione un método...</option>
                        @foreach($paymentMethods as $method)
                            <option value="{{ $method->id }}" data-details="{{ $method->details }}">{{ $method->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Información del método de pago --}}
                <div id="payment-details-info" class="hidden animate-in slide-in-from-top-2 duration-300">
                    <div class="p-4 bg-blue-50 border border-blue-100 rounded-2xl">
                        <p class="text-[10px] font-black text-blue-400 uppercase mb-1">Instrucciones de Pago:</p>
                        <div id="payment-details-text" class="text-xs text-blue-700 leading-relaxed"></div>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Monto ($)</label>
                        <input type="number" step="0.01" name="amount" required placeholder="0.00" class="w-full bg-slate-100 border-none rounded-xl text-sm p-3 focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Referencia</label>
                        <input type="text" name="reference" required placeholder="# Transacción" class="w-full bg-slate-100 border-none rounded-xl text-sm p-3 focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Notas adicionales</label>
                    <textarea name="notes" rows="2" placeholder="Opcional..." class="w-full bg-slate-100 border-none rounded-xl text-sm p-3 focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>

            <div class="p-6 bg-slate-50 flex gap-3">
                <button type="button" onclick="closeModal('modalRecharge')" class="flex-1 py-3 text-sm font-bold text-slate-500 hover:bg-slate-200 rounded-xl transition">Cerrar</button>
                <button type="submit" class="flex-1 py-3 text-sm font-bold bg-blue-600 text-white rounded-xl hover:bg-blue-700 shadow-lg shadow-blue-200 transition flex items-center justify-center">
                    <i data-lucide="send" class="w-4 h-4 mr-2"></i> Reportar Pago
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    function showPaymentInfo(select) {
        const selected = select.options[select.selectedIndex];
        const details = selected.getAttribute('data-details');
        const container = document.getElementById('payment-details-info');
        const textElement = document.getElementById('payment-details-text');

        if (details) {
            // Reemplazar saltos de línea por <br> para visualización HTML
            textElement.innerHTML = details.replace(/\n/g, '<br>');
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
        }
    }
</script>
@endsection