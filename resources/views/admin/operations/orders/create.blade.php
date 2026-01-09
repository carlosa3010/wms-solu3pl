@extends('layouts.admin')

@section('title', 'Nueva Orden de Salida')
@section('header_title', 'Registrar Pedido')

@section('content')

    <div class="max-w-6xl mx-auto">
        
        @if($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <p class="font-bold text-sm">Hay errores en el formulario:</p>
                <ul class="list-disc list-inside text-xs mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.orders.store') }}" method="POST" id="orderForm">
            @csrf
            
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-2 space-y-6">
                    
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="font-bold text-slate-700 text-sm flex items-center gap-2 uppercase tracking-wider">
                                <i data-lucide="file-text" class="w-4 h-4 text-indigo-600"></i> Datos del Pedido
                            </h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dueño de Mercancía (Cliente) *</label>
                                <select name="client_id" id="client_id" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-indigo-500 outline-none bg-white transition font-bold text-slate-700">
                                    <option value="">-- Seleccionar Cliente --</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-[9px] text-slate-400 mt-1 uppercase tracking-tighter">Esto filtrará los productos disponibles.</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Número de Orden # *</label>
                                <input type="text" name="order_number" value="{{ $nextOrderNumber ?? '' }}" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm font-bold text-slate-700 bg-slate-50" readonly>
                                <p class="text-[9px] text-slate-400 mt-1 uppercase tracking-tighter">Generado automáticamente.</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50">
                            <h3 class="font-bold text-slate-700 text-sm flex items-center gap-2 uppercase tracking-wider">
                                <i data-lucide="user" class="w-4 h-4 text-indigo-600"></i> Destinatario y Entrega
                            </h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nombre Completo / Razón Social *</label>
                                    <input type="text" name="customer_name" required placeholder="Ej: Juan Pérez" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm outline-none focus:border-indigo-500 transition-colors">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cédula o RIF *</label>
                                    <input type="text" name="customer_id_number" required placeholder="Ej: V-12345678" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm font-mono focus:border-indigo-500 uppercase transition-colors">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Teléfono de Contacto *</label>
                                    <input type="tel" name="customer_phone" required placeholder="+58..." class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm outline-none focus:border-indigo-500 transition-colors">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Correo Electrónico (Opcional)</label>
                                    <input type="email" name="customer_email" placeholder="cliente@correo.com" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm outline-none focus:border-indigo-500 transition-colors">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">País *</label>
                                    <select name="country" id="customer_country" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm bg-white outline-none focus:border-indigo-500 transition-colors">
                                        <option value="">-- Seleccionar --</option>
                                        @foreach($countries as $country)
                                            <option value="{{ $country->name }}" data-id="{{ $country->id }}">{{ $country->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Estado / Provincia *</label>
                                    <select name="state" id="customer_state" required disabled class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm bg-white disabled:bg-slate-100 disabled:cursor-not-allowed outline-none focus:border-indigo-500 transition-colors">
                                        <option value="">Primero seleccione país</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Ciudad *</label>
                                    <input type="text" name="city" required placeholder="Ciudad" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm outline-none focus:border-indigo-500 transition-colors">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                <div class="md:col-span-3">
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dirección Exacta *</label>
                                    <input type="text" name="shipping_address" required placeholder="Calle, edificio, punto de referencia..." class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm outline-none focus:border-indigo-500 transition-colors">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Código Postal</label>
                                    <input type="text" name="customer_zip" required placeholder="C.P." class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm outline-none focus:border-indigo-500 transition-colors">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Productos y Cantidades</h3>
                            <button type="button" id="addProductBtn" disabled onclick="addProductRow()" class="text-[10px] bg-indigo-600 text-white px-3 py-1.5 rounded-lg font-bold hover:brightness-95 transition flex items-center gap-1 shadow-md shadow-indigo-500/20 disabled:bg-slate-300 disabled:shadow-none disabled:cursor-not-allowed">
                                <i data-lucide="plus" class="w-3 h-3"></i> Agregar SKU
                            </button>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm" id="itemsTable">
                                <thead class="bg-slate-50 text-slate-400 font-bold text-[10px] uppercase border-b border-slate-100">
                                    <tr>
                                        <th class="px-6 py-3 w-12 text-center">#</th>
                                        <th class="px-6 py-3">Producto (SKU con Stock)</th>
                                        <th class="px-6 py-3 w-32 text-center">Cant. Pedida</th>
                                        <th class="px-6 py-3 w-12 text-center"></th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100" id="itemsContainer">
                                    </tbody>
                            </table>
                        </div>
                        <div id="emptyItems" class="p-12 text-center text-slate-400">
                            <i data-lucide="shopping-cart" class="w-12 h-12 mx-auto mb-3 opacity-20"></i>
                            <p class="text-xs">Seleccione un cliente y agregue productos a la lista.</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-6">
                    <div class="bg-slate-800 text-white rounded-2xl p-6 shadow-xl sticky top-6 border border-white/5">
                        <h3 class="font-bold text-lg mb-6 border-b border-white/10 pb-4 flex items-center gap-2">
                            <i data-lucide="settings" class="w-5 h-5 text-indigo-400"></i> Control Operativo
                        </h3>
                        
                        <div class="mb-6 space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Referencia Externa (Opcional)</label>
                                <input type="text" name="external_ref" placeholder="Ej: Shopify #1020" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-xl text-xs text-white outline-none focus:border-indigo-500 transition-colors placeholder:text-slate-500">
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Método de Envío</label>
                                <select name="shipping_method" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-xl text-xs text-white outline-none focus:border-indigo-500 transition-colors">
                                    <option value="">Seleccione Courier...</option>
                                    @foreach($shippingMethods as $sm)
                                        <option value="{{ $sm->name }}">{{ $sm->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Instrucciones Especiales</label>
                                <textarea name="notes" rows="4" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-xl text-xs text-white outline-none focus:border-indigo-500 transition-colors placeholder:text-slate-500" placeholder="Notas de picking o empaque..."></textarea>
                            </div>
                        </div>

                        <div class="p-4 bg-white/5 rounded-xl border border-white/10 mb-8">
                            <div class="flex justify-between items-center text-xs mb-2">
                                <span class="text-slate-400">Total SKUs:</span>
                                <span id="totalSKUCount" class="font-bold text-indigo-400">0</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-slate-400">Unidades:</span>
                                <span id="totalUnitsCount" class="font-bold text-indigo-400">0</span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <button type="submit" class="w-full bg-indigo-600 text-white py-3.5 rounded-xl font-bold shadow-lg flex items-center justify-center gap-2 transition hover:brightness-110 active:scale-95">
                                <i data-lucide="check-circle" class="w-4 h-4"></i> Procesar Pedido
                            </button>
                            <a href="{{ route('admin.orders.index') }}" class="w-full bg-slate-700 text-slate-300 py-3 rounded-xl font-bold text-center text-sm hover:bg-slate-600 transition">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <template id="productRowTemplate">
        <tr class="group hover:bg-slate-50 transition">
            <td class="px-6 py-4 text-center text-slate-400 font-mono text-xs row-index">1</td>
            <td class="px-6 py-4">
                <select name="items[INDEX][product_id]" required onchange="handleProductSelect(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 ring-indigo-500 outline-none transition sku-selector">
                    <option value="">-- Seleccionar SKU --</option>
                </select>
                <div class="mt-1 flex items-center gap-2">
                    <span class="text-[9px] font-bold text-slate-400 uppercase">Disponible:</span>
                    <span class="text-[9px] font-black text-indigo-600 stock-display">0 unidades</span>
                </div>
            </td>
            <td class="px-6 py-4">
                <input type="number" name="items[INDEX][qty]" min="1" required value="1" oninput="updateCalculations(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm text-center font-bold qty-input">
            </td>
            <td class="px-6 py-4 text-center">
                <button type="button" onclick="removeRow(this)" class="text-slate-300 hover:text-red-500 transition">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                </button>
            </td>
        </tr>
    </template>

@endsection

@section('scripts')
<script>
    let clientProducts = [];
    let rowIndex = 0;

    document.addEventListener('DOMContentLoaded', function() {
        lucide.createIcons();
        const countrySelect = document.getElementById('customer_country');
        const stateSelect = document.getElementById('customer_state');
        const clientSelect = document.getElementById('client_id');

        // 1. Manejo dinámico de Estados (Usando ruta absoluta)
        countrySelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const countryId = selectedOption.getAttribute('data-id');
            
            stateSelect.innerHTML = '<option value="">Cargando...</option>';
            stateSelect.disabled = true;

            if (countryId) {
                const url = `{{ url('/admin/inventory/get-states') }}/${countryId}`;
                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('Error al obtener estados');
                        return response.json();
                    })
                    .then(data => {
                        stateSelect.innerHTML = '<option value="">-- Seleccionar Estado --</option>';
                        data.forEach(s => {
                            stateSelect.innerHTML += `<option value="${s.name}">${s.name}</option>`;
                        });
                        stateSelect.disabled = false;
                    })
                    .catch(error => {
                        console.error('AJAX Error:', error);
                        stateSelect.innerHTML = '<option value="">Error al cargar estados</option>';
                    });
            } else {
                stateSelect.innerHTML = '<option value="">Primero seleccione país</option>';
            }
        });

        // 2. Manejo dinámico de Productos por Cliente
        clientSelect.addEventListener('change', function() {
            const clientId = this.value;
            const container = document.getElementById('itemsContainer');
            const addBtn = document.getElementById('addProductBtn');
            
            if(container.children.length > 0) {
                if(!confirm("Cambiar el cliente eliminará los productos actuales de la lista. ¿Continuar?")) {
                    return;
                }
                container.innerHTML = '';
                document.getElementById('emptyItems').classList.remove('hidden');
                updateCalculations();
            }

            if (clientId) {
                // CORREGIDO: Ruta para obtener productos
                const url = `{{ url('/admin/orders/get-client-products') }}/${clientId}`;
                
                fetch(url)
                    .then(response => {
                        if (!response.ok) throw new Error('Error al obtener productos');
                        return response.json();
                    })
                    .then(data => {
                        clientProducts = data;
                        addBtn.disabled = false; // Habilitamos el botón
                        if(data.length === 0) {
                             alert("Este cliente no tiene productos con stock disponible.");
                        }
                    })
                    .catch(error => {
                        console.error('AJAX Error:', error);
                        alert("Error al cargar los productos del cliente.");
                        addBtn.disabled = true;
                    });
            } else {
                clientProducts = [];
                addBtn.disabled = true;
            }
        });
    });

    function addProductRow() {
        if(clientProducts.length === 0) return;

        rowIndex++;
        const container = document.getElementById('itemsContainer');
        const emptyState = document.getElementById('emptyItems');
        const template = document.getElementById('productRowTemplate');
        
        emptyState.classList.add('hidden');

        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('tr');
        
        // Reemplazar index en nombres de campos
        row.innerHTML = row.innerHTML.replace(/INDEX/g, rowIndex);
        
        // Cargar SKUs del cliente actual
        const select = row.querySelector('.sku-selector');
        clientProducts.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = `${p.sku} | ${p.name}`;
            select.appendChild(opt);
        });

        container.appendChild(row);
        updateRowNumbers();
        lucide.createIcons();
        updateCalculations();
    }

    function handleProductSelect(select) {
        const prodId = select.value;
        const row = select.closest('tr');
        const display = row.querySelector('.stock-display');
        const input = row.querySelector('.qty-input');
        
        const prod = clientProducts.find(p => p.id == prodId);
        if(prod) {
            display.innerText = `${prod.stock_available} unidades`;
            input.max = prod.stock_available;
            if(parseInt(input.value) > prod.stock_available) input.value = prod.stock_available;
        } else {
            display.innerText = '0 unidades';
            input.max = 0;
        }
        updateCalculations();
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        const container = document.getElementById('itemsContainer');
        if (container.children.length === 0) {
            document.getElementById('emptyItems').classList.remove('hidden');
        }
        updateRowNumbers();
        updateCalculations();
    }

    function updateRowNumbers() {
        document.querySelectorAll('#itemsContainer tr').forEach((r, idx) => {
            r.querySelector('.row-index').innerText = idx + 1;
        });
    }

    function updateCalculations(input = null) {
        if (input && input.max && parseInt(input.value) > parseInt(input.max)) {
            input.value = input.max;
        }

        const rows = document.querySelectorAll('#itemsContainer tr');
        let totalUnits = 0;
        rows.forEach(r => {
            const q = r.querySelector('.qty-input').value;
            if(q) totalUnits += parseInt(q);
        });

        document.getElementById('totalSKUCount').innerText = rows.length;
        document.getElementById('totalUnitsCount').innerText = totalUnits;
    }
</script>
@endsection