@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Facturación y Pagos</h2>
            <p class="text-sm text-slate-500">Descarga tus facturas y reporta tus transferencias.</p>
        </div>
        <div class="flex gap-3">
            <button onclick="togglePaymentModal()" class="flex items-center justify-center space-x-2 bg-emerald-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-emerald-700 transition-all shadow-lg shadow-emerald-100">
                <i data-lucide="banknote" class="w-5 h-5"></i>
                <span>Reportar Pago</span>
            </button>
        </div>
    </div>

    <!-- Tabla de Facturas -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
            <h3 class="font-bold text-slate-700">Historial de Facturas</h3>
        </div>
        
        @if($invoices->isEmpty())
            <div class="p-12 text-center text-slate-400">
                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="file-x" class="w-8 h-8 text-slate-300"></i>
                </div>
                <h3 class="text-slate-800 font-bold mb-1">Sin facturas generadas</h3>
                <p class="text-sm">Tus facturas o pre-facturas aparecerán aquí al cierre de corte.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead class="bg-slate-50 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Folio</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Fecha</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Monto</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Estado</th>
                            <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Descargar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($invoices as $invoice)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4 font-bold text-slate-700">
                                {{ $invoice->invoice_number }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                {{ $invoice->created_at->format('d M, Y') }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono font-bold text-slate-800">
                                ${{ number_format($invoice->total, 2) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusClasses = [
                                        'paid' => 'bg-emerald-100 text-emerald-700',
                                        'pending' => 'bg-amber-100 text-amber-700',
                                        'overdue' => 'bg-rose-100 text-rose-700',
                                    ];
                                    $labels = [
                                        'paid' => 'Pagado',
                                        'pending' => 'Pendiente',
                                        'overdue' => 'Vencida',
                                    ];
                                @endphp
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider {{ $statusClasses[$invoice->status] ?? 'bg-slate-100 text-slate-500' }}">
                                    {{ $labels[$invoice->status] ?? $invoice->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="#" class="text-blue-600 hover:text-blue-800 font-bold text-xs flex items-center justify-end gap-1">
                                    <i data-lucide="download" class="w-3 h-3"></i> PDF
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

<!-- Modal de Reportar Pago -->
<div id="paymentModal" class="fixed inset-0 z-[60] hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <!-- Backdrop -->
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="togglePaymentModal()"></div>

    <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
            
            <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-center mb-5">
                    <h3 class="text-lg font-black text-slate-800" id="modal-title">Reportar Nuevo Pago</h3>
                    <button onclick="togglePaymentModal()" class="text-slate-400 hover:text-slate-600">
                        <i data-lucide="x" class="w-5 h-5"></i>
                    </button>
                </div>

                <form action="{{ route('client.billing.store_payment') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    
                    <!-- Selección de Método -->
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Método de Pago</label>
                        <select name="payment_method" id="paymentMethodSelect" onchange="updatePaymentDetails()" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
                            <option value="" disabled selected>Selecciona una opción...</option>
                            @foreach($paymentMethods as $key => $method)
                                <option value="{{ $key }}">{{ $method['name'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Detalles del Método (Dinámico) -->
                    <div id="methodDetails" class="hidden p-4 bg-blue-50 rounded-xl border border-blue-100 text-sm text-blue-800">
                        <p class="font-bold mb-1">Instrucciones de Pago:</p>
                        <p id="methodInstructions" class="whitespace-pre-line text-xs leading-relaxed"></p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Monto Pagado</label>
                            <div class="relative">
                                <span class="absolute left-3 top-2.5 text-slate-400 font-bold">$</span>
                                <input type="number" step="0.01" name="amount" required class="w-full pl-7 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Fecha Pago</label>
                            <input type="date" name="payment_date" required value="{{ date('Y-m-d') }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Referencia / Folio Banco</label>
                        <input type="text" name="reference" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600" placeholder="Ej: 00492811">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Comprobante (Imagen/PDF)</label>
                        <input type="file" name="proof_file" accept=".pdf,.jpg,.jpeg,.png" required class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full py-3 bg-emerald-600 text-white rounded-xl font-black shadow-lg shadow-emerald-200 hover:bg-emerald-700 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="send" class="w-4 h-4"></i>
                            Enviar Reporte
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Datos pasados desde el controlador a JS
    const paymentMethodsData = @json($paymentMethods);

    function togglePaymentModal() {
        const modal = document.getElementById('paymentModal');
        modal.classList.toggle('hidden');
    }

    function updatePaymentDetails() {
        const select = document.getElementById('paymentMethodSelect');
        const detailsDiv = document.getElementById('methodDetails');
        const instructionsP = document.getElementById('methodInstructions');
        
        const selectedKey = select.value;
        
        if (selectedKey && paymentMethodsData[selectedKey]) {
            instructionsP.textContent = paymentMethodsData[selectedKey].details;
            detailsDiv.classList.remove('hidden');
            detailsDiv.classList.add('animate-in', 'fade-in', 'slide-in-from-top-1');
        } else {
            detailsDiv.classList.add('hidden');
        }
    }
</script>
@endsection