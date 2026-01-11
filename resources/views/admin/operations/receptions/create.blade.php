@extends('layouts.admin')

@section('title', 'Nueva Recepción')
@section('header_title', 'Registrar ASN (Aviso de Recepción)')

@section('content')

    <div class="max-w-5xl mx-auto">
        
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

        <form action="{{ route('admin.receptions.store') }}" method="POST" id="asnForm">
            @csrf
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
                <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700 text-lg flex items-center gap-2">
                        <i class="fa-solid fa-file-signature text-custom-primary"></i> Datos Generales
                    </h3>
                    <div class="bg-blue-100 text-blue-700 px-3 py-1 rounded-lg text-xs font-bold font-mono">
                        {{ $nextId ?? 'ASN-NEW' }}
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">ASN Number <span class="text-red-500">*</span></label>
                        <input type="text" name="asn_number" value="{{ $nextId ?? '' }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none font-bold text-slate-700 bg-slate-50" readonly>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cliente (Dueño) <span class="text-red-500">*</span></label>
                        <select name="client_id" id="client_id" required onchange="confirmClientChange()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none bg-white font-bold text-slate-700">
                            <option value="">-- Seleccionar Cliente --</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Fecha Llegada <span class="text-red-500">*</span></label>
                        <input type="date" name="expected_arrival_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Transportista</label>
                        <input type="text" name="carrier_name" placeholder="Ej: DHL, Propio..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Total Cajas / Bultos <span class="text-red-500">*</span></label>
                        <input type="number" name="total_packages" min="1" value="1" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none font-bold text-slate-700" placeholder="Cant. bultos físicos">
                        <p class="text-[9px] text-slate-400 mt-1">Dato clave para facturación de INBOUND.</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Guía / Tracking</label>
                        <input type="text" name="tracking_number" placeholder="Opcional" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none">
                    </div>

                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Ref. Documento</label>
                        <input type="text" name="document_ref" placeholder="Factura #123" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none">
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700 text-lg">Contenido de la Recepción</h3>
                    <button type="button" onclick="addProductRow()" id="btnAddProduct" disabled class="bg-slate-100 text-slate-400 px-3 py-1.5 rounded-lg text-xs font-bold transition flex items-center gap-2 cursor-not-allowed">
                        <i class="fa-solid fa-plus"></i> Agregar SKU
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm" id="itemsTable">
                        <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-3 w-16 text-center">#</th>
                                <th class="px-6 py-3">Producto (SKU)</th>
                                <th class="px-6 py-3 w-32 text-center">Cantidad Esperada</th>
                                <th class="px-6 py-3 w-16 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="itemsContainer">
                            </tbody>
                    </table>
                </div>
                
                <div id="emptyState" class="p-12 text-center text-slate-400">
                    <i class="fa-solid fa-cart-flatbed text-4xl mb-3 opacity-50"></i>
                    <p class="text-sm font-bold">La lista de productos está vacía.</p>
                    <p class="text-[10px] uppercase tracking-tighter">Seleccione un cliente para cargar sus productos.</p>
                </div>
            </div>

            <div class="mb-8">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Notas de Recepción</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none" placeholder="Instrucciones especiales para el equipo de bodega..."></textarea>
            </div>

            <div class="flex items-center justify-end gap-4">
                <a href="{{ route('admin.receptions.index') }}" class="text-slate-500 hover:text-slate-700 font-bold text-sm">Cancelar</a>
                <button type="submit" class="bg-custom-primary text-white px-8 py-3 rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:brightness-95 transition active:scale-95">
                    Guardar ASN
                </button>
            </div>
        </form>
    </div>

    <script>
        let productIndex = 0;
        let productsList = [];
        const btnAdd = document.getElementById('btnAddProduct');

        function confirmClientChange() {
            const container = document.getElementById('itemsContainer');
            
            // Si ya hay items, confirmar limpieza
            if(container.children.length > 0) {
                if(!confirm('Si cambia de cliente, se vaciará la lista de productos actual. ¿Continuar?')) {
                    // Revertir selección (opcional, requiere guardar previous value)
                    return; 
                }
                container.innerHTML = '';
                checkEmptyState();
            }
            
            loadClientProducts();
        }

        function loadClientProducts() {
            const clientId = document.getElementById('client_id').value;
            
            if(!clientId) {
                productsList = [];
                btnAdd.disabled = true;
                btnAdd.classList.add('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
                btnAdd.classList.remove('bg-blue-100', 'text-custom-primary', 'hover:bg-blue-200');
                return;
            }

            // Usamos la API interna corregida (sin 'is_active')
            fetch(`{{ url('admin/api/client-products') }}/${clientId}`)
                .then(response => {
                    if(!response.ok) throw new Error("Error HTTP " + response.status);
                    return response.json();
                })
                .then(data => {
                    productsList = data;
                    
                    // Habilitar botón de agregar
                    btnAdd.disabled = false;
                    btnAdd.classList.remove('bg-slate-100', 'text-slate-400', 'cursor-not-allowed');
                    btnAdd.classList.add('bg-blue-100', 'text-custom-primary', 'hover:bg-blue-200', 'cursor-pointer');

                    if(data.length === 0) {
                        alert("Este cliente no tiene productos registrados.");
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Error al cargar productos del cliente.");
                });
        }

        function addProductRow() {
            if(productsList.length === 0) {
                alert('No hay productos disponibles para este cliente.');
                return;
            }

            const container = document.getElementById('itemsContainer');
            const rowId = `row-${productIndex}`;
            
            let optionsHtml = '<option value="">-- Seleccionar SKU --</option>';
            productsList.forEach(p => {
                optionsHtml += `<option value="${p.id}">${p.sku} - ${p.name}</option>`;
            });

            const html = `
                <tr id="${rowId}" class="animate-fade-in group hover:bg-slate-50 transition">
                    <td class="px-6 py-3 text-center text-slate-400 font-mono text-xs">${productIndex + 1}</td>
                    <td class="px-6 py-3">
                        <select name="items[${productIndex}][product_id]" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none bg-white">
                            ${optionsHtml}
                        </select>
                    </td>
                    <td class="px-6 py-3 text-center">
                        <input type="number" name="items[${productIndex}][qty]" min="1" value="1" required class="w-full text-center px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none font-bold text-slate-700">
                    </td>
                    <td class="px-6 py-3 text-center">
                        <button type="button" onclick="removeRow('${rowId}')" class="text-slate-300 hover:text-red-500 transition p-2 rounded-full hover:bg-red-50">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;

            container.insertAdjacentHTML('beforeend', html);
            productIndex++;
            checkEmptyState();
        }

        function removeRow(id) {
            document.getElementById(id).remove();
            checkEmptyState();
        }

        function checkEmptyState() {
            const container = document.getElementById('itemsContainer');
            const emptyState = document.getElementById('emptyState');
            if (container.children.length === 0) {
                emptyState.classList.remove('hidden');
            } else {
                emptyState.classList.add('hidden');
            }
        }
    </script>

@endsection