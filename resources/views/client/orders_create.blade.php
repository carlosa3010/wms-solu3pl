@extends('layouts.client_layout')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    
    <!-- Encabezado -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-800">Nuevo Pedido</h2>
            <p class="text-sm text-slate-500">Crea una orden de despacho. Los campos marcados con <span class="text-rose-500">*</span> son obligatorios.</p>
        </div>
        <a href="{{ route('client.orders.index') }}" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i> Cancelar
        </a>
    </div>

    <form action="{{ route('client.orders.store') }}" method="POST" id="orderForm" class="space-y-8">
        @csrf
        
        <!-- Datos del Destinatario -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2 mb-6 flex items-center gap-2">
                <i data-lucide="user" class="w-4 h-4 text-blue-500"></i> Datos del Destinatario
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Nombre Completo / Empresa <span class="text-rose-500">*</span></label>
                    <input type="text" name="customer_name" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700" placeholder="Ej: Distribuidora Central C.A.">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Dirección Exacta de Entrega <span class="text-rose-500">*</span></label>
                    <input type="text" name="customer_address" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600" placeholder="Calle, Edificio, Local, Punto de referencia">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">País <span class="text-rose-500">*</span></label>
                    <select name="customer_country" id="country_select" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
                        @foreach($countries as $country)
                            <option value="{{ $country->name }}" data-id="{{ $country->id }}" {{ $country->name == 'Venezuela' ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Estado / Provincia <span class="text-rose-500">*</span></label>
                    <select name="customer_state" id="state_select" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
                        <option value="">Cargando estados...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Ciudad <span class="text-rose-500">*</span></label>
                    <input type="text" name="customer_city" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Código Postal</label>
                    <input type="text" name="customer_zip" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Teléfono de Contacto <span class="text-rose-500">*</span></label>
                    <input type="text" name="customer_phone" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Email de Notificación <span class="text-rose-500">*</span></label>
                    <input type="email" name="customer_email" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">
                </div>
            </div>
        </div>

        <!-- Logística y Envío -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2 mb-6 flex items-center gap-2">
                <i data-lucide="truck" class="w-4 h-4 text-blue-500"></i> Logística del Pedido
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Referencia Externa (Opcional)</label>
                    <input type="text" name="reference_number" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700" placeholder="Ej: PO-2024-001">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Método de Envío Preferido</label>
                    <select name="shipping_method" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">
                        @foreach($shippingMethods as $method)
                            <option value="{{ $method->code }}">{{ $method->name }} ({{ $method->description }})</option>
                        @endforeach
                        @if($shippingMethods->isEmpty())
                            <option value="standard">Envío Estándar (Por defecto)</option>
                        @endif
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Notas de Despacho</label>
                    <textarea name="notes" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600" placeholder="Instrucciones especiales para el personal de almacén..."></textarea>
                </div>
            </div>
        </div>

        <!-- Productos (Items) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                    <i data-lucide="package" class="w-4 h-4 text-blue-500"></i> Productos en Inventario
                </h3>
                <button type="button" onclick="addOrderRow()" class="text-xs font-bold text-blue-600 hover:bg-blue-50 px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1">
                    <i data-lucide="plus" class="w-4 h-4"></i> Agregar Línea
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left" id="orderItemsTable">
                    <thead class="bg-slate-50 rounded-lg">
                        <tr>
                            <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest w-2/3">Producto (SKU)</th>
                            <th class="px-4 py-3 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center w-1/4">Cantidad</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100" id="orderItemsContainer">
                        <!-- Se llena mediante JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flex items-center justify-end gap-4 pt-4">
            <a href="{{ route('client.orders.index') }}" class="px-6 py-3 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition-all">
                Descartar
            </a>
            <button type="submit" class="px-8 py-3 bg-blue-600 text-white rounded-xl font-black shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all flex items-center gap-2">
                <i data-lucide="send" class="w-4 h-4"></i>
                Procesar Pedido
            </button>
        </div>
    </form>
</div>

<script>
    const countrySelect = document.getElementById('country_select');
    const stateSelect = document.getElementById('state_select');
    const products = @json($products);
    let itemCount = 0;

    /**
     * Carga de estados dinámica
     */
    async function updateStates() {
        const countryId = countrySelect.options[countrySelect.selectedIndex].dataset.id;
        if (!countryId) return;

        stateSelect.innerHTML = '<option value="">Cargando...</option>';
        
        try {
            // Se usa la ruta definida en web.php: states.get
            const response = await fetch(`/portal/states/${countryId}`);
            const states = await response.json();
            
            stateSelect.innerHTML = '';
            if (states.length === 0) {
                stateSelect.innerHTML = '<option value="">Sin estados disponibles</option>';
            } else {
                states.forEach(state => {
                    const option = document.createElement('option');
                    option.value = state.name;
                    option.textContent = state.name;
                    stateSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error("Error cargando estados:", error);
            stateSelect.innerHTML = '<option value="">Error al cargar</option>';
        }
    }

    /**
     * Agregar fila de producto
     */
    function addOrderRow() {
        const container = document.getElementById('orderItemsContainer');
        const newRow = document.createElement('tr');
        newRow.className = 'item-row group animate-in fade-in slide-in-from-top-2 duration-200';
        
        let optionsHtml = '<option value="" disabled selected>Seleccionar SKU...</option>';
        products.forEach(p => {
            optionsHtml += `<option value="${p.id}">${p.sku} - ${p.name} (Disponible: ${p.stock_available})</option>`;
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
                <button type="button" onclick="removeOrderRow(this)" class="text-slate-300 hover:text-rose-500 transition-colors p-2">
                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                </button>
            </td>
        `;

        container.appendChild(newRow);
        itemCount++;
        lucide.createIcons();
        updateDeleteButtons();
    }

    function removeOrderRow(btn) {
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

    // Inicialización
    document.addEventListener('DOMContentLoaded', () => {
        updateStates();
        addOrderRow();
        countrySelect.addEventListener('change', updateStates);
    });
</script>
@endsection