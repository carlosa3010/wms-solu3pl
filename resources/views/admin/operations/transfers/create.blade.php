@extends('layouts.admin')

@section('title', 'Nuevo Traslado Multiproducto')
@section('header_title', 'Orden de Traslado Interno')

@section('content')
<div class="max-w-7xl mx-auto">
    
    <form action="{{ route('admin.transfers.store') }}" method="POST" id="transferForm">
        @csrf
        
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            
            <!-- SECCIÓN IZQUIERDA: INFRAESTRUCTURA Y DETALLE -->
            <div class="lg:col-span-3 space-y-6">
                
                <!-- CARD 1: CABECERA DE RUTA (ORIGEN Y DESTINO) -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center gap-2 text-slate-700 font-bold text-xs uppercase tracking-widest">
                        <i class="fa-solid fa-route text-custom-primary"></i> 1. Definición de Ruta
                    </div>
                    <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- ORIGEN GENERAL -->
                        <div class="space-y-4 p-5 bg-blue-50/50 rounded-2xl border border-blue-100">
                            <p class="text-[10px] font-black text-blue-600 uppercase tracking-widest flex items-center gap-2">
                                <i class="fa-solid fa-arrow-up-from-bracket"></i> Bodega de Salida
                            </p>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Sucursal Origen</label>
                                    <select id="src_branch" class="w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-white outline-none focus:ring-2 ring-blue-500">
                                        <option value="">-- Seleccionar Sede --</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Bodega Origen</label>
                                    <select name="src_warehouse_id" id="src_warehouse" required class="w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-white outline-none focus:ring-2 ring-blue-500" disabled>
                                        <option value="">-- Seleccione Sede Primero --</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- DESTINO GENERAL -->
                        <div class="space-y-4 p-5 bg-emerald-50/50 rounded-2xl border border-emerald-100">
                            <p class="text-[10px] font-black text-emerald-600 uppercase tracking-widest flex items-center gap-2">
                                <i class="fa-solid fa-truck-loading"></i> Bodega de Destino
                            </p>
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Sucursal Destino</label>
                                    <select id="dest_branch" class="w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-white outline-none focus:ring-2 ring-emerald-500">
                                        <option value="">-- Seleccionar Sede --</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Bodega Destino</label>
                                    <select name="dest_warehouse_id" id="dest_warehouse" required class="w-full p-2.5 border border-slate-200 rounded-xl text-sm bg-white outline-none focus:ring-2 ring-emerald-500" disabled>
                                        <option value="">-- Seleccione Sede Primero --</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CARD 2: LISTADO DE PRODUCTOS (MULTI-SKU) -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                        <span class="text-slate-700 font-bold text-xs uppercase tracking-widest"><i class="fa-solid fa-boxes-stacked text-custom-primary mr-1"></i> 2. Items a Trasladar</span>
                        <button type="button" onclick="addItemRow()" class="bg-custom-primary text-white px-4 py-1.5 rounded-lg text-[10px] font-black uppercase hover:brightness-110 transition flex items-center gap-2">
                            <i class="fa-solid fa-plus"></i> Añadir SKU
                        </button>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm" id="itemsTable">
                            <thead class="bg-slate-50/50 text-slate-400 font-bold text-[10px] uppercase border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4">Producto (SKU)</th>
                                    <th class="px-6 py-4">Bin Origen (Stock)</th>
                                    <th class="px-6 py-4 text-center">Cantidad</th>
                                    <th class="px-6 py-4">Bin Destino</th>
                                    <th class="px-6 py-4 text-right"></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100" id="itemsContainer">
                                <!-- Filas inyectadas por JS -->
                            </tbody>
                        </table>
                    </div>

                    <div id="emptyItems" class="p-20 text-center text-slate-300">
                        <i class="fa-solid fa-cart-flatbed text-5xl mb-4 opacity-20"></i>
                        <p class="font-bold text-sm">No hay productos en la lista</p>
                        <p class="text-xs uppercase tracking-tighter">Haga clic en "Añadir SKU" para comenzar el traslado</p>
                    </div>
                </div>

                <!-- Notas -->
                <div class="bg-white rounded-2xl p-6 border border-slate-200">
                    <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Motivo / Observaciones del Traslado</label>
                    <textarea name="reason" rows="2" class="w-full p-4 border border-slate-200 rounded-xl text-sm outline-none focus:border-custom-primary bg-slate-50" placeholder="Ej: Reabastecimiento de zona de picking..."></textarea>
                </div>
            </div>

            <!-- COLUMNA DERECHA: RESUMEN Y ACCIÓN -->
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
                        El stock se reservará automáticamente en las ubicaciones de destino. Imprima las etiquetas de bulto para asegurar la identificación durante el tránsito.
                    </p>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- TEMPLATE PARA FILAS DINÁMICAS -->
<template id="itemTemplate">
    <tr class="group hover:bg-slate-50/50 transition item-row">
        <td class="px-6 py-4 w-1/4">
            <select name="items[INDEX][product_id]" required class="w-full p-2 border border-slate-200 rounded-lg text-xs bg-white outline-none focus:ring-1 ring-blue-500 js-product-select">
                <option value="">-- SKU --</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}">{{ $product->sku }}</option>
                @endforeach
            </select>
            <p class="text-[8px] text-slate-400 mt-1 uppercase font-bold js-product-name">---</p>
        </td>
        <td class="px-6 py-4">
            <select name="items[INDEX][from_location_id]" required class="w-full p-2 border border-slate-200 rounded-lg text-xs bg-white outline-none js-from-loc">
                <option value="">-- Seleccionar --</option>
            </select>
        </td>
        <td class="px-6 py-4 text-center w-24">
            <input type="number" name="items[INDEX][quantity]" value="1" min="1" required class="w-full p-2 border border-slate-200 rounded-lg text-xs text-center font-bold js-qty">
        </td>
        <td class="px-6 py-4">
            <select name="items[INDEX][to_location_id]" required class="w-full p-2 border border-slate-200 rounded-lg text-xs bg-white outline-none js-to-loc">
                <option value="">-- Seleccionar --</option>
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
    const branches = @json($branches); // Inyectamos data de infraestructura para carga rápida
    let rowIndex = 0;

    // --- LÓGICA DE INFRAESTRUCTURA ---
    
    document.getElementById('src_branch').addEventListener('change', (e) => populateWarehouses(e.target.value, 'src_warehouse'));
    document.getElementById('dest_branch').addEventListener('change', (e) => populateWarehouses(e.target.value, 'dest_warehouse'));

    function populateWarehouses(branchId, targetSelectId) {
        const select = document.getElementById(targetSelectId);
        select.innerHTML = '<option value="">-- Seleccionar Bodega --</option>';
        select.disabled = !branchId;

        if (branchId) {
            const branch = branches.find(b => b.id == branchId);
            branch.warehouses.forEach(wh => {
                select.innerHTML += `<option value="${wh.id}">${wh.name}</option>`;
            });
        }
        updateTransferType();
    }

    function updateTransferType() {
        const srcWh = document.getElementById('src_warehouse').value;
        const destWh = document.getElementById('dest_warehouse').value;
        const label = document.getElementById('transfer_type_label');

        if(srcWh && destWh) {
            label.innerText = srcWh === destWh ? "INTERNO (MISMA BODEGA)" : "MOV. ENTRE BODEGAS";
            label.className = srcWh === destWh ? "text-blue-400 font-black" : "text-amber-400 font-black";
        }
    }

    // --- LÓGICA DE PRODUCTOS ---

    function addItemRow() {
        const srcWh = document.getElementById('src_warehouse').value;
        const destWh = document.getElementById('dest_warehouse').value;

        if(!srcWh || !destWh) {
            alert("Seleccione las bodegas de origen y destino primero.");
            return;
        }

        document.getElementById('emptyItems').classList.add('hidden');
        
        const template = document.getElementById('itemTemplate');
        const clone = template.content.cloneNode(true);
        const container = document.getElementById('itemsContainer');
        
        const row = clone.querySelector('tr');
        row.innerHTML = row.innerHTML.replace(/INDEX/g, rowIndex++);
        
        container.appendChild(row);
        
        // Vincular eventos a la nueva fila
        const productSelect = row.querySelector('.js-product-select');
        productSelect.addEventListener('change', function() {
            loadSourceBins(this);
            loadDestBins(this);
        });

        row.querySelector('.js-qty').addEventListener('input', updateSummary);
        
        updateSummary();
    }

    function removeItemRow(btn) {
        btn.closest('tr').remove();
        if(document.querySelectorAll('.item-row').length === 0) {
            document.getElementById('emptyItems').classList.remove('hidden');
        }
        updateSummary();
    }

    /**
     * CARGA BINES DE ORIGEN: Basado en SKU + Bodega Origen (STOCK > 0)
     */
    function loadSourceBins(select) {
        const row = select.closest('tr');
        const productId = select.value;
        const warehouseId = document.getElementById('src_warehouse').value;
        const fromLocSelect = row.querySelector('.js-from-loc');
        const productNameLabel = row.querySelector('.js-product-name');

        if (!productId) return;

        fromLocSelect.innerHTML = '<option value="">Cargando...</option>';

        // Llamada AJAX corregida
        fetch(`{{ url('admin/inventory/get-sources') }}?product_id=${productId}&warehouse_id=${warehouseId}`)
            .then(res => res.json())
            .then(data => {
                fromLocSelect.innerHTML = '<option value="">-- Seleccionar Bin --</option>';
                if(data.length === 0) {
                    fromLocSelect.innerHTML = '<option value="">SIN STOCK</option>';
                    return;
                }
                data.forEach(loc => {
                    const qty = loc.stock[0].quantity;
                    fromLocSelect.innerHTML += `<option value="${loc.id}" data-max="${qty}">${loc.code} (${qty} uds)</option>`;
                });
            });

        // Actualizar nombre visual del producto
        const productsList = @json($products);
        const prod = productsList.find(p => p.id == productId);
        productNameLabel.innerText = prod ? prod.name : '---';
    }

    /**
     * CARGA BINES DE DESTINO: Basado en Bodega Destino
     */
    function loadDestBins(select) {
        const row = select.closest('tr');
        const warehouseId = document.getElementById('dest_warehouse').value;
        const toLocSelect = row.querySelector('.js-to-loc');

        toLocSelect.innerHTML = '<option value="">Cargando...</option>';

        fetch(`{{ url('admin/inventory/get-bins') }}?warehouse_id=${warehouseId}`)
            .then(res => res.json())
            .then(data => {
                toLocSelect.innerHTML = '<option value="">-- Seleccionar Bin --</option>';
                data.forEach(loc => {
                    toLocSelect.innerHTML += `<option value="${loc.id}">${loc.code}</option>`;
                });
            });
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