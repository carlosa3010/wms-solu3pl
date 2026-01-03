@extends('layouts.client_layout')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    
    <!-- Encabezado -->
    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-2xl font-black text-slate-800">Editar Pedido #{{ $order->order_number }}</h2>
            <p class="text-sm text-slate-500">Modifica los detalles de envío o los productos de esta orden.</p>
        </div>
        <a href="{{ route('client.orders.index') }}" class="text-sm font-bold text-slate-500 hover:text-slate-800 flex items-center gap-2">
            <i data-lucide="x" class="w-4 h-4"></i> Cancelar cambios
        </a>
    </div>

    <form action="{{ route('client.orders.update', $order->id) }}" method="POST" id="orderForm" class="space-y-8">
        @csrf
        @method('PUT')
        
        <!-- Datos del Destinatario -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2 mb-6 flex items-center gap-2">
                <i data-lucide="user" class="w-4 h-4 text-blue-500"></i> Datos del Destinatario
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Nombre Completo / Empresa <span class="text-rose-500">*</span></label>
                    <input type="text" name="customer_name" value="{{ old('customer_name', $order->customer_name) }}" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
                </div>
                
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Dirección Exacta de Entrega <span class="text-rose-500">*</span></label>
                    <input type="text" name="customer_address" value="{{ old('customer_address', $order->customer_address) }}" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">País <span class="text-rose-500">*</span></label>
                    <select name="customer_country" id="country_select" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
                        @foreach($countries as $country)
                            <option value="{{ $country->name }}" data-id="{{ $country->id }}" {{ old('customer_country', $order->customer_country) == $country->name ? 'selected' : '' }}>
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
                    <input type="text" name="customer_city" value="{{ old('customer_city', $order->customer_city) }}" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Código Postal</label>
                    <input type="text" name="customer_zip" value="{{ old('customer_zip', $order->customer_zip) }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Teléfono de Contacto <span class="text-rose-500">*</span></label>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone', $order->customer_phone) }}" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Email de Notificación <span class="text-rose-500">*</span></label>
                    <input type="email" name="customer_email" value="{{ old('customer_email', $order->customer_email) }}" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">
                </div>
            </div>
        </div>

        <!-- Logística y Referencias -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 pb-2 mb-6 flex items-center gap-2">
                <i data-lucide="truck" class="w-4 h-4 text-blue-500"></i> Logística del Pedido
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Tu Referencia (PO / ID Tienda)</label>
                    <input type="text" name="reference_number" value="{{ old('reference_number', $order->reference_number) }}" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-bold text-slate-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Método de Envío Preferido</label>
                    <select name="shipping_method" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">
                        @foreach($shippingMethods as $method)
                            <option value="{{ $method->code }}" {{ (old('shipping_method', $order->shipping_method) == $method->code) ? 'selected' : '' }}>
                                {{ $method->name }} ({{ $method->description }})
                            </option>
                        @endforeach
                        @if($shippingMethods->isEmpty())
                            <option value="standard" {{ $order->shipping_method == 'standard' ? 'selected' : '' }}>Envío Estándar</option>
                        @endif
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 mb-1.5 uppercase">Notas de Despacho</label>
                    <textarea name="notes" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-2 focus:ring-blue-500 outline-none font-medium text-slate-600">{{ old('notes', $order->notes) }}</textarea>
                </div>
            </div>
        </div>

        <!-- Productos (Items) -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-4">
                <h3 class="text-sm font-black text-slate-400 uppercase tracking-widest flex items-center gap-2">
                    <i data-lucide="package" class="w-4 h-4 text-blue-500"></i> Productos Seleccionados
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
                        <!-- Se llenará dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Acciones -->
        <div class="flex items-center justify-end gap-4 pt-4">
            <a href="{{ route('client.orders.index') }}" class="px-6 py-3 text-sm font-bold text-slate-500 hover:bg-slate-100 rounded-xl transition-all">
                Cancelar
            </a>
            <button type="submit" class="px-8 py-3 bg-blue-600 text-white rounded-xl font-black shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all flex items-center gap-2">
                <i data-lucide="save" class="w-4 h-4"></i>
                Actualizar Pedido
            </button>
        </div>
    </form>
</div>

<script>
    const countrySelect = document.getElementById('country_select');
    const stateSelect = document.getElementById('state_select');
    const products = @json($products);
    const existingItems = @json($order->items);
    const currentSavedState = "{{ old('customer_state', $order->customer_state) }}";
    let itemCount = 0;

    /**
     * Carga de estados dinámica con pre-selección para edición
     */
    async function updateStates(isInitialLoad = false) {
        const countryId = countrySelect.options[countrySelect.selectedIndex].dataset.id;
        if (!countryId) return;

        stateSelect.innerHTML = '<option value="">Cargando...</option>';
        
        try {
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
                    // Pre-seleccionar el estado si coincide con el guardado
                    if (isInitialLoad && state.name === currentSavedState) {
                        option.selected = true;
                    }
                    stateSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error("Error cargando estados:", error);
            stateSelect.innerHTML = '<option value="">Error al cargar</option>';
        }
    }

    /**
     * Agregar fila de producto con datos opcionales (para precarga)
     */
    function addOrderRow(productId = null, quantity = null) {
        const container = document.getElementById('orderItemsContainer');
        const newRow = document.createElement('tr');
        newRow.className = 'item-row group animate-in fade-in slide-in-from-top-2 duration-200';
        
        let optionsHtml = '<option value="" disabled selected>Seleccionar SKU...</option>';
        products.forEach(p => {
            const isSelected = (productId && parseInt(productId) === p.id) ? 'selected' : '';
            optionsHtml += `<option value="${p.id}" ${isSelected}>${p.sku} - ${p.name} (Disponible: ${p.stock_available})</option>`;
        });

        const qtyValue = quantity ? quantity : '';

        newRow.innerHTML = `
            <td class="p-2">
                <select name="items[${itemCount}][product_id]" required class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:border-blue-500 outline-none">
                    ${optionsHtml}
                </select>
            </td>
            <td class="p-2">
                <input type="number" name="items[${itemCount}][quantity]" value="${qtyValue}" required min="1" class="w-full px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:border-blue-500 outline-none text-center font-bold" placeholder="0">
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

    // Inicialización de la página de edición
    document.addEventListener('DOMContentLoaded', () => {
        // Cargar estados iniciales (basados en el país guardado)
        updateStates(true);
        
        // Precargar items existentes del pedido
        if (existingItems && existingItems.length > 0) {
            existingItems.forEach(item => {
                addOrderRow(item.product_id, item.quantity);
            });
        } else {
            addOrderRow();
        }

        // Listener para cambios de país
        countrySelect.addEventListener('change', () => updateStates(false));
    });
</script>
@endsection