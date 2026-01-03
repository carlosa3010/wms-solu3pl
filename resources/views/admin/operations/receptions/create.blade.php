@extends('layouts.admin')

@section('title', 'Nueva Recepción')
@section('header_title', 'Registrar ASN')

@section('content')

    <div class="max-w-5xl mx-auto">
        
        <form action="{{ route('admin.receptions.store') }}" method="POST" id="asnForm">
            @csrf
            
            <!-- TARJETA CABECERA -->
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
                    <!-- Número ASN -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">ASN Number <span class="text-red-50">*</span></label>
                        <input type="text" name="asn_number" value="{{ $nextId ?? '' }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none font-bold text-slate-700">
                    </div>

                    <!-- Cliente -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cliente (Dueño) <span class="text-red-50">*</span></label>
                        <select name="client_id" id="client_id" required onchange="confirmClientChange()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none bg-white font-bold text-slate-700">
                            <option value="">-- Seleccionar Cliente --</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Fecha Estimada -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Fecha Llegada <span class="text-red-50">*</span></label>
                        <input type="date" name="expected_arrival_date" value="{{ date('Y-m-d') }}" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none">
                    </div>

                    <!-- Transportista -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Transportista</label>
                        <input type="text" name="carrier_name" placeholder="Ej: DHL, Propio..." class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none">
                    </div>

                    <!-- Tracking -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Guía / Tracking</label>
                        <input type="text" name="tracking_number" placeholder="Opcional" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none">
                    </div>

                    <!-- Referencia Doc -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Ref. Documento</label>
                        <input type="text" name="document_ref" placeholder="Factura #123" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none">
                    </div>
                </div>
            </div>

            <!-- TARJETA DETALLE (Productos) -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700 text-lg">Contenido de la Recepción</h3>
                    <button type="button" onclick="addProductRow()" class="bg-slate-100 text-custom-primary px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-200 transition flex items-center gap-2">
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
                            <!-- Filas dinámicas -->
                        </tbody>
                    </table>
                </div>
                
                <div id="emptyState" class="p-12 text-center text-slate-400">
                    <i class="fa-solid fa-cart-flatbed text-4xl mb-3 opacity-50"></i>
                    <p class="text-sm font-bold">La lista de productos está vacía.</p>
                    <p class="text-[10px] uppercase tracking-tighter">Seleccione un cliente y añada los productos a recibir.</p>
                </div>
            </div>

            <!-- Notas -->
            <div class="mb-8">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Observaciones de Recepción</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none" placeholder="Instrucciones para el equipo de bodega..."></textarea>
            </div>

            <!-- Botones Acción -->
            <div class="flex justify-end gap-4 border-t border-slate-200 pt-6">
                <a href="{{ route('admin.receptions.index') }}" class="px-6 py-3 rounded-xl border border-slate-300 text-slate-600 font-bold text-sm hover:bg-slate-50 transition">Cancelar</a>
                <button type="submit" class="bg-custom-primary text-white px-8 py-3 rounded-xl font-bold text-sm hover:shadow-lg hover:brightness-95 transition">
                    <i class="fa-solid fa-save mr-2"></i> Guardar ASN
                </button>
            </div>

        </form>
    </div>

    <!-- Template para filas dinámicas -->
    <template id="productRowTemplate">
        <tr class="group hover:bg-slate-50 transition">
            <td class="px-6 py-3 text-center text-slate-400 font-mono text-xs row-index">1</td>
            <td class="px-6 py-3">
                <select name="items[INDEX][product_id]" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white outline-none focus:ring-2 ring-custom-primary">
                    <option value="">-- Buscar SKU --</option>
                </select>
            </td>
            <td class="px-6 py-3">
                <input type="number" name="items[INDEX][qty]" min="1" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm text-center font-bold outline-none focus:ring-2 ring-custom-primary">
            </td>
            <td class="px-6 py-3 text-center">
                <button type="button" onclick="removeRow(this)" class="text-slate-300 hover:text-red-500 transition">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        </tr>
    </template>

@endsection

@section('scripts')
<script>
    // Recuperamos productos asegurando que incluimos client_id para filtrar
    const allProducts = {!! json_encode(\App\Models\Product::select('id', 'name', 'sku', 'client_id')->get()) !!};
    let rowCount = 0;
    let lastClientId = '';

    /**
     * Valida el cambio de cliente para no mezclar productos
     */
    function confirmClientChange() {
        const container = document.getElementById('itemsContainer');
        const currentClientId = document.getElementById('client_id').value;

        if (container.children.length > 0) {
            if (confirm("Al cambiar de cliente se eliminarán los productos agregados a la lista actual. ¿Desea continuar?")) {
                container.innerHTML = '';
                document.getElementById('emptyState').style.display = 'block';
                rowCount = 0;
                lastClientId = currentClientId;
            } else {
                // Revertir selección
                document.getElementById('client_id').value = lastClientId;
            }
        } else {
            lastClientId = currentClientId;
        }
    }

    /**
     * Agrega una fila filtrando por el cliente seleccionado
     */
    function addProductRow() {
        const clientId = document.getElementById('client_id').value;
        
        if (!clientId) {
            alert("Por favor, seleccione un cliente (dueño) antes de agregar productos.");
            return;
        }

        rowCount++;
        const container = document.getElementById('itemsContainer');
        const emptyState = document.getElementById('emptyState');
        const template = document.getElementById('productRowTemplate');
        
        emptyState.style.display = 'none';

        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('tr');
        
        // Configurar índices
        row.querySelector('.row-index').innerText = rowCount;
        row.innerHTML = row.innerHTML.replace(/INDEX/g, rowCount);

        const select = row.querySelector('select');
        
        // Filtrar productos que pertenecen al cliente
        const filtered = allProducts.filter(p => p.client_id == clientId);

        if (filtered.length === 0) {
            alert("Este cliente no tiene productos registrados en el catálogo maestro.");
            return;
        }

        filtered.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.text = `${p.sku} | ${p.name}`;
            select.appendChild(opt);
        });

        container.appendChild(row);
    }

    function removeRow(btn) {
        btn.closest('tr').remove();
        const container = document.getElementById('itemsContainer');
        
        if (container.children.length === 0) {
            document.getElementById('emptyState').style.display = 'block';
            rowCount = 0;
        } else {
            // Re-numerar filas visualmente
            document.querySelectorAll('.row-index').forEach((el, i) => el.innerText = i + 1);
        }
    }
</script>
@endsection