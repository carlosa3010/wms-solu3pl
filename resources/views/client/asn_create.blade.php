@extends('layouts.client_layout')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-800">Crear Aviso de Envío</h2>
            <p class="text-sm text-slate-500">Notifica qué productos enviarás a nuestras bodegas.</p>
        </div>
        <a href="{{ route('client.asn.index') }}" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Cancelar
        </a>
    </div>

    <form action="{{ route('client.asn.store') }}" method="POST" id="asnForm" class="space-y-6">
        @csrf
        
        <!-- Sección General -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm space-y-4">
            <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2 mb-4">Información del Envío</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Referencia / Tracking <span class="text-rose-500">*</span></label>
                    <input type="text" name="reference_number" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700" placeholder="Ej: FEDEX-998877">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Fecha Estimada de Llegada <span class="text-rose-500">*</span></label>
                    <input type="date" name="expected_arrival_date" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Notas / Instrucciones de Recepción</label>
                <textarea name="notes" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600" placeholder="Ej: Mercancía frágil, viene en 2 pallets..."></textarea>
            </div>
        </div>

        <!-- Sección Items -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest">Contenido del Envío</h3>
                <button type="button" onclick="addItemRow()" class="text-xs font-bold text-blue-600 hover:bg-blue-50 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                    <i data-lucide="plus" class="w-4 h-4"></i> Agregar Producto
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left" id="itemsTable">
                    <thead class="bg-slate-50 rounded-lg">
                        <tr>
                            <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest w-2/3">Producto (SKU)</th>
                            <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center w-1/4">Cantidad Esperada</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="itemsContainer">
                        <!-- Fila Inicial -->
                        <tr class="item-row group">
                            <td class="p-2">
                                <select name="items[0][product_id]" required class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:border-blue-500 outline-none">
                                    <option value="" disabled selected>Seleccionar producto...</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td class="p-2">
                                <input type="number" name="items[0][quantity]" required min="1" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:border-blue-500 outline-none text-center font-bold" placeholder="0">
                            </td>
                            <td class="p-2 text-center">
                                <button type="button" onclick="removeItem(this)" class="text-slate-300 hover:text-rose-500 transition-colors p-2" disabled>
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="mt-4 p-4 bg-blue-50 rounded-xl flex items-start gap-3">
                <i data-lucide="info" class="w-5 h-5 text-blue-600 mt-0.5"></i>
                <p class="text-xs text-blue-800 leading-relaxed">
                    Asegúrate de que las cantidades coincidan con lo físico. El almacén reportará cualquier discrepancia al recibir.
                </p>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flex items-center justify-end gap-4 pt-4">
            <a href="{{ route('client.asn.index') }}" class="px-6 py-3 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition-all">
                Cancelar
            </a>
            <button type="submit" class="px-8 py-3 bg-blue-600 text-white rounded-xl font-black shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all flex items-center gap-2">
                <i data-lucide="send" class="w-4 h-4"></i>
                Enviar ASN
            </button>
        </div>
    </form>
</div>

<script>
    let itemCount = 1;
    const products = @json($products); // Pasamos los productos a JS para generar opciones

    function addItemRow() {
        const container = document.getElementById('itemsContainer');
        const newRow = document.createElement('tr');
        newRow.className = 'item-row group animate-in fade-in slide-in-from-top-2 duration-200';
        
        let optionsHtml = '<option value="" disabled selected>Seleccionar producto...</option>';
        products.forEach(p => {
            optionsHtml += `<option value="${p.id}">${p.sku} - ${p.name}</option>`;
        });

        newRow.innerHTML = `
            <td class="p-2">
                <select name="items[${itemCount}][product_id]" required class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:border-blue-500 outline-none">
                    ${optionsHtml}
                </select>
            </td>
            <td class="p-2">
                <input type="number" name="items[${itemCount}][quantity]" required min="1" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:border-blue-500 outline-none text-center font-bold" placeholder="0">
            </td>
            <td class="p-2 text-center">
                <button type="button" onclick="removeItem(this)" class="text-slate-300 hover:text-rose-500 transition-colors p-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
            </td>
        `;

        container.appendChild(newRow);
        itemCount++;
        lucide.createIcons(); // Refrescar iconos
        updateDeleteButtons();
    }

    function removeItem(btn) {
        const row = btn.closest('tr');
        if(document.querySelectorAll('.item-row').length > 1) {
            row.remove();
            updateDeleteButtons();
        }
    }

    function updateDeleteButtons() {
        const rows = document.querySelectorAll('.item-row');
        const buttons = document.querySelectorAll('.item-row button');
        
        if (rows.length === 1) {
            buttons[0].disabled = true;
            buttons[0].classList.add('opacity-50', 'cursor-not-allowed');
        } else {
            buttons.forEach(btn => {
                btn.disabled = false;
                btn.classList.remove('opacity-50', 'cursor-not-allowed');
            });
        }
    }
</script>
@endsection