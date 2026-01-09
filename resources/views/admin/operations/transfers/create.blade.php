@extends('layouts.admin')

@section('title', 'Nuevo Traslado Multiproducto')
@section('header_title', 'Orden de Traslado Interno')

@section('content')
<div class="max-w-7xl mx-auto">
    
    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-sm">
            <p class="font-bold">Error en la solicitud:</p>
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('admin.transfers.store') }}" method="POST" id="transferForm">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <div class="lg:col-span-3 space-y-6">
                
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center gap-2 text-slate-700 font-bold text-xs uppercase tracking-widest">
                        <i class="fa-solid fa-route text-custom-primary"></i> 1. Definición de Ruta
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-4 p-5 bg-blue-50/50 rounded-2xl border border-blue-100">
                            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest flex items-center gap-2">
                                <i class="fa-solid fa-arrow-up-from-bracket"></i> Bodega de Salida
                            </p>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Sucursal Origen</label>
                                    <select name="origin_branch_id" id="src_branch" required class="w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-white outline-none focus:ring-2 ring-blue-500">
                                        <option value="">-- Seleccionar Sede --</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" data-warehouses='@json($branch->warehouses)'>{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Bodega Origen</label>
                                    <select id="src_warehouse" required class="w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-white outline-none focus:ring-2 ring-blue-500" disabled>
                                        <option value="">-- Seleccione Sede Primero --</option>
                                    </select>
                                    <p class="text-[9px] text-slate-400 mt-1">El stock se descontará de aquí.</p>
                                </div>
                            </div>
                        </div>

                        <div class="space-y-4 p-5 bg-emerald-50/50 rounded-2xl border border-emerald-100">
                            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest flex items-center gap-2">
                                <i class="fa-solid fa-truck-loading"></i> Bodega de Destino
                            </p>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Sucursal Destino</label>
                                    <select name="destination_branch_id" id="dest_branch" required class="w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-white outline-none focus:ring-2 ring-emerald-500">
                                        <option value="">-- Seleccionar Sede --</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" data-warehouses='@json($branch->warehouses)'>{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Bodega Destino</label>
                                    <select id="dest_warehouse" required class="w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-white outline-none focus:ring-2 ring-emerald-500" disabled>
                                        <option value="">-- Seleccione Sede Primero --</option>
                                    </select>
                                    <p class="text-[9px] text-slate-400 mt-1">La mercancía ingresará aquí.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                        <span class="text-slate-700 font-bold text-xs uppercase tracking-widest">
                            <i class="fa-solid fa-boxes-stacked text-custom-primary mr-1"></i> 2. Items a Trasladar
                        </span>
                        <button type="button" onclick="addItemRow()" id="add-btn" disabled class="bg-custom-primary text-white px-4 py-1.5 rounded-lg text-[10px] font-black uppercase hover:brightness-110 transition flex items-center gap-2 disabled:bg-slate-300 disabled:cursor-not-allowed">
                            <i class="fa-solid fa-plus"></i> Añadir SKU
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm" id="itemsTable">
                            <thead class="bg-slate-50/50 text-slate-400 font-bold text-[10px] uppercase border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 w-1/3">Producto (SKU)</th>
                                    <th class="px-6 py-4">Bin Origen (Stock)</th>
                                    <th class="px-6 py-4 text-center w-24">Cantidad</th>
                                    <th class="px-6 py-4">Bin Destino</th>
                                    <th class="px-6 py-4 text-right w-16"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100" id="itemsContainer">
                                </tbody>
                        </table>
                    </div>

                    <div id="emptyItems" class="p-20 text-center text-slate-300">
                        <i class="fa-solid fa-cart-flatbed text-5xl mb-4 opacity-20"></i>
                        <p class="font-bold text-sm">No hay productos en la lista</p>
                        <p class="text-xs uppercase tracking-tighter">Seleccione las bodegas para comenzar</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl p-6 border border-slate-200">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Motivo / Observaciones</label>
                    <textarea name="notes" rows="2" class="w-full p-4 border border-slate-200 rounded-xl text-sm outline-none focus:border-custom-primary bg-slate-50" placeholder="Ej: Reabastecimiento de zona de picking..."></textarea>
                </div>
            </div>

            <div class="space-y-6">
                <div class="bg-slate-900 text-white rounded-[2rem] p-8 shadow-2xl sticky top-8 border border-white/5">
                    <h3 class="text-xl font-black mb-8 flex items-center gap-3">
                        <i class="fa-solid fa-file-contract text-blue-400"></i> Resumen
                    </h3>

                    <div class="space-y-6">
                        <div class="p-4 bg-white/5 rounded-2xl border border-white/10 space-y-4">
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-slate-500 uppercase font-bold">Total SKUs:</span>
                                <span id="summary-skus" class="text-blue-400 font-black">0</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-slate-500 uppercase font-bold">Total Unidades:</span>
                                <span id="summary-units" class="text-emerald-400 font-black">0</span>
                            </div>
                            <div class="pt-2 border-t border-white/10 flex justify-between items-center text-xs">
                                <span class="text-slate-500 uppercase font-bold">Tipo:</span>
                                <span id="transfer_type_label" class="text-white font-black uppercase text-[9px]">PENDIENTE</span>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-custom-primary text-white py-4 rounded-2xl font-black text-sm shadow-xl shadow-blue-500/20 hover:brightness-110 active:scale-95 transition flex items-center justify-center gap-3">
                            <i class="fa-solid fa-shuffle"></i> PROCESAR ORDEN
                        </button>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-6">
                    <p class="text-amber-800 font-bold text-xs flex items-center gap-2 mb-2">
                        <i class="fa-solid fa-triangle-exclamation"></i> Importante
                    </p>
                    <p class="text-[10px] text-amber-700 leading-relaxed uppercase font-medium">
                        El stock se moverá inmediatamente de la ubicación origen a la destino al confirmar. Asegúrese de que el movimiento físico se realice al mismo tiempo.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<template id="itemTemplate">
    <tr class="group hover:bg-slate-50/50 transition item-row">
        <td class="px-6 py-4">
            <select name="items[INDEX][product_id]" required class="w-full p-2 border border-slate-200 rounded-lg text-xs bg-white outline-none focus:ring-1 ring-blue-500 js-product-select">
                <option value="">-- Seleccionar SKU --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->sku }} - {{ Str::limit($product->name, 30) }}</option>
                @endforeach
            </select>
        </td>
        <td class="px-6 py-4">
            <select name="items[INDEX][origin_location_id]" required class="w-full p-2 border border-slate-200 rounded-lg text-xs bg-white outline-none js-from-loc" disabled>
                <option value="">-- Primero SKU --</option>
            </select>
        </td>
        <td class="px-6 py-4 text-center">
            <input type="number" name="items[INDEX][quantity]" value="1" min="1" required class="w-full p-2 border border-slate-200 rounded-lg text-xs text-center font-bold js-qty" oninput="updateSummary()">
        </td>
        <td class="px-6 py-4">
            <select name="items[INDEX][destination_location_id]" required class="w-full p-2 border border-slate-200 rounded-lg text-xs bg-white outline-none js-to-loc">
                <option value="">-- Cargando --</option>
            </select>
        </td>
        <td class="px-6 py-4 text-right">
            <button type="button" onclick="removeItemRow(this)" class="text-slate-300 hover:text-red-500 transition"><i class="fa-solid fa-trash-can"></i></button>
        </td>
    </tr>
</template>

@endsection

@section('scripts')
<script>
    let rowIndex = 0;

    // --- 1. LÓGICA DE INFRAESTRUCTURA ---
    
    const srcBranch = document.getElementById('src_branch');
    const destBranch = document.getElementById('dest_branch');
    const srcWhSelect = document.getElementById('src_warehouse');
    const destWhSelect = document.getElementById('dest_warehouse');
    const addBtn = document.getElementById('add-btn');

    // Eventos para cargar bodegas al cambiar sucursal
    srcBranch.addEventListener('change', function() {
        populateWarehouses(this, srcWhSelect);
        checkFormReady();
    });

    destBranch.addEventListener('change', function() {
        populateWarehouses(this, destWhSelect);
        checkFormReady();
    });

    srcWhSelect.addEventListener('change', checkFormReady);
    destWhSelect.addEventListener('change', checkFormReady);

    function populateWarehouses(branchSelect, warehouseSelect) {
        // Limpiar
        warehouseSelect.innerHTML = '<option value="">-- Seleccionar Bodega --</option>';
        warehouseSelect.disabled = true;

        const selectedOption = branchSelect.options[branchSelect.selectedIndex];
        
        // Obtener data del atributo data-warehouses
        if (selectedOption.value) {
            const warehouses = JSON.parse(selectedOption.getAttribute('data-warehouses'));
            
            if (warehouses && warehouses.length > 0) {
                warehouses.forEach(wh => {
                    warehouseSelect.innerHTML += `<option value="${wh.id}">${wh.name}</option>`;
                });
                warehouseSelect.disabled = false;
                
                // Auto-seleccionar si solo hay una
                if(warehouses.length === 1) {
                    warehouseSelect.selectedIndex = 1;
                    checkFormReady();
                }
            } else {
                warehouseSelect.innerHTML = '<option value="">Sin bodegas</option>';
            }
        }
    }

    function checkFormReady() {
        const src = srcWhSelect.value;
        const dest = destWhSelect.value;
        
        // Habilitar botón de agregar si tenemos origen y destino
        if (src && dest) {
            addBtn.disabled = false;
            addBtn.classList.remove('bg-slate-300', 'cursor-not-allowed');
            addBtn.classList.add('bg-custom-primary', 'hover:brightness-110');
            
            // Actualizar etiqueta de tipo
            const label = document.getElementById('transfer_type_label');
            if (src === dest) {
                label.innerText = "MOVIMIENTO INTERNO";
                label.className = "text-blue-400 font-black uppercase text-[9px]";
            } else {
                label.innerText = "TRASLADO ENTRE BODEGAS";
                label.className = "text-amber-400 font-black uppercase text-[9px]";
            }
        } else {
            addBtn.disabled = true;
        }
    }

    // --- 2. LÓGICA DE PRODUCTOS ---

    function addItemRow() {
        const srcWhId = srcWhSelect.value;
        const destWhId = destWhSelect.value;

        if(!srcWhId || !destWhId) {
            alert("Seleccione las bodegas de origen y destino primero.");
            return;
        }

        document.getElementById('emptyItems').classList.add('hidden');
        
        const template = document.getElementById('itemTemplate');
        const clone = template.content.cloneNode(true);
        const container = document.getElementById('itemsContainer');
        
        // Crear fila única
        const row = clone.querySelector('tr');
        row.innerHTML = row.innerHTML.replace(/INDEX/g, rowIndex++);
        
        container.appendChild(row);
        
        // Inicializar fila
        const productSelect = row.querySelector('.js-product-select');
        const destLocSelect = row.querySelector('.js-to-loc');

        // Cargar bines de destino (todos los de la bodega destino)
        loadDestinationBins(destWhId, destLocSelect);

        // Evento al seleccionar producto
        productSelect.addEventListener('change', function() {
            loadSourceStock(this, srcWhId);
        });

        updateSummary();
    }

    function removeItemRow(btn) {
        btn.closest('tr').remove();
        if(document.querySelectorAll('.item-row').length === 0) {
            document.getElementById('emptyItems').classList.remove('hidden');
        }
        updateSummary();
    }

    // --- 3. AJAX PARA DATOS ---

    function loadDestinationBins(warehouseId, selectElement) {
        selectElement.innerHTML = '<option value="">Cargando...</option>';
        
        fetch(`{{ url('admin/inventory/get-bins') }}?warehouse_id=${warehouseId}`)
            .then(res => res.json())
            .then(data => {
                selectElement.innerHTML = '<option value="">-- Seleccionar --</option>';
                data.forEach(loc => {
                    selectElement.innerHTML += `<option value="${loc.id}">${loc.code}</option>`;
                });
            })
            .catch(err => {
                console.error(err);
                selectElement.innerHTML = '<option value="">Error</option>';
            });
    }

    function loadSourceStock(productSelect, warehouseId) {
        const row = productSelect.closest('tr');
        const fromSelect = row.querySelector('.js-from-loc');
        const qtyInput = row.querySelector('.js-qty');
        const productId = productSelect.value;

        fromSelect.innerHTML = '<option value="">Buscando stock...</option>';
        fromSelect.disabled = true;

        if (!productId) return;

        // AJAX para traer stock real disponible
        fetch(`{{ url('admin/inventory/get-sources') }}?product_id=${productId}&warehouse_id=${warehouseId}`)
            .then(res => res.json())
            .then(data => {
                if (data.length === 0) {
                    fromSelect.innerHTML = '<option value="">SIN STOCK</option>';
                    alert("Este producto no tiene stock en la bodega de origen seleccionada.");
                } else {
                    fromSelect.innerHTML = '<option value="">-- Seleccionar Bin --</option>';
                    data.forEach(item => {
                        // El controlador devuelve { id, code, quantity, text }
                        fromSelect.innerHTML += `<option value="${item.id}" data-max="${item.quantity}">${item.text}</option>`;
                    });
                    fromSelect.disabled = false;
                }
            })
            .catch(err => {
                console.error(err);
                fromSelect.innerHTML = '<option value="">Error API</option>';
            });

        // Evento al seleccionar el bin de origen para validar cantidad
        fromSelect.onchange = function() {
            const opt = this.options[this.selectedIndex];
            const max = opt.getAttribute('data-max');
            if (max) {
                qtyInput.max = max;
                if (parseInt(qtyInput.value) > parseInt(max)) qtyInput.value = max;
            }
        };
    }

    function updateSummary() {
        const rows = document.querySelectorAll('.item-row');
        let totalUnits = 0;
        rows.forEach(r => {
            const q = r.querySelector('.js-qty').value;
            totalUnits += parseInt(q || 0);
        });

        document.getElementById('summary-skus').innerText = rows.length;
        document.getElementById('summary-units').innerText = totalUnits;
    }

</script>
@endsection