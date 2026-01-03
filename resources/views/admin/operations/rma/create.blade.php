@extends('layouts.admin')

@section('title', 'Nueva Devolución')
@section('header_title', 'Registrar RMA')

@section('content')

    <div class="max-w-5xl mx-auto">
        <form action="{{ route('admin.rma.store') }}" method="POST" id="rmaForm">
            @csrf
            
            <!-- TARJETA 1: DATOS DE ORIGEN Y REFERENCIA -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
                <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700 text-lg flex items-center gap-2">
                        <i class="fa-solid fa-rotate-left text-custom-primary"></i> Información de la Devolución
                    </h3>
                    <div class="bg-amber-100 text-amber-700 px-3 py-1 rounded-lg text-xs font-bold font-mono">
                        {{ $rmaNumber }}
                        <input type="hidden" name="rma_number" value="{{ $rmaNumber }}">
                    </div>
                </div>
                
                <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Cliente (Dueño) -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cliente (Dueño SKU) <span class="text-red-500">*</span></label>
                        <select name="client_id" id="client_id" required onchange="handleClientChange()" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none bg-white font-bold text-slate-700">
                            <option value="">-- Seleccionar Cliente --</option>
                            @foreach($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Pedido Original (Opcional) -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Pedido Original (Ref)</label>
                        <input type="text" name="order_ref" placeholder="Ej: ORD-10234" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none">
                        <input type="hidden" name="order_id" id="order_id">
                    </div>

                    <!-- Nombre del Cliente Final -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nombre del Remitente <span class="text-red-500">*</span></label>
                        <input type="text" name="customer_name" required placeholder="Nombre del comprador" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none">
                    </div>

                    <!-- Motivo de Devolución -->
                    <div class="md:col-span-3">
                        <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Motivo de la Devolución <span class="text-red-500">*</span></label>
                        <select name="reason" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 ring-custom-primary outline-none bg-white">
                            <option value="">-- Seleccione un motivo --</option>
                            <option value="defective">Producto Defectuoso / Garantía</option>
                            <option value="wrong_item">Ítem Incorrecto / Error de Despacho</option>
                            <option value="delivery_failure">Fallo de Entrega (Dirección/Ausente)</option>
                            <option value="customer_return">Retracto / Devolución Comercial</option>
                            <option value="damaged_shipping">Dañado durante el transporte</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- TARJETA 2: PRODUCTOS A DEVOLVER -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-6">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700 text-lg">Productos Retornados</h3>
                    <button type="button" onclick="addProductRow()" class="bg-slate-100 text-custom-primary px-3 py-1.5 rounded-lg text-xs font-bold hover:bg-slate-200 transition flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Agregar ítem
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm" id="itemsTable">
                        <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                            <tr>
                                <th class="px-6 py-3 w-16 text-center">#</th>
                                <th class="px-6 py-3">Producto / SKU</th>
                                <th class="px-6 py-3 w-32 text-center">Cantidad</th>
                                <th class="px-6 py-3 w-16 text-center"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100" id="itemsContainer">
                            <!-- Filas dinámicas -->
                        </tbody>
                    </table>
                </div>
                
                <div id="emptyState" class="p-12 text-center text-slate-400">
                    <i class="fa-solid fa-box-open text-4xl mb-3 opacity-30"></i>
                    <p class="text-sm font-bold">Sin productos seleccionados.</p>
                    <p class="text-[10px] uppercase">Seleccione un cliente y añada los productos que regresan a bodega.</p>
                </div>
            </div>

            <!-- Notas Internas -->
            <div class="mb-8">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Instrucciones de Inspección / Notas</label>
                <textarea name="notes" rows="3" class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none" placeholder="Indique si requiere revisión técnica o cuarentena inmediata..."></textarea>
            </div>

            <!-- Botones Acción -->
            <div class="flex justify-end gap-4 border-t border-slate-200 pt-6">
                <a href="{{ route('admin.rma.index') }}" class="px-6 py-3 rounded-xl border border-slate-300 text-slate-600 font-bold text-sm hover:bg-slate-50 transition">Cancelar</a>
                <button type="submit" class="bg-custom-primary text-white px-8 py-3 rounded-xl font-bold text-sm hover:shadow-lg hover:brightness-95 transition">
                    <i class="fa-solid fa-check-double mr-2"></i> Registrar Solicitud RMA
                </button>
            </div>
        </form>
    </div>

    <!-- Template para filas dinámicas -->
    <template id="productRowTemplate">
        <tr class="group hover:bg-slate-50 transition">
            <td class="px-6 py-3 text-center text-slate-400 font-mono text-xs row-index">1</td>
            <td class="px-6 py-3">
                <select name="items[INDEX][product_id]" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 ring-custom-primary outline-none">
                    <option value="">-- Buscar SKU --</option>
                </select>
            </td>
            <td class="px-6 py-3">
                <input type="number" name="items[INDEX][qty]" min="1" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm text-center font-bold focus:ring-2 ring-custom-primary outline-none">
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
     * Valida el cambio de cliente
     */
    function handleClientChange() {
        const container = document.getElementById('itemsContainer');
        const currentClientId = document.getElementById('client_id').value;

        if (container.children.length > 0) {
            if (confirm("Al cambiar de cliente se limpiará la lista de productos actual. ¿Desea continuar?")) {
                container.innerHTML = '';
                document.getElementById('emptyState').style.display = 'block';
                rowCount = 0;
                lastClientId = currentClientId;
            } else {
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
            alert("Debe seleccionar un cliente antes de agregar productos.");
            return;
        }

        rowCount++;
        const container = document.getElementById('itemsContainer');
        const emptyState = document.getElementById('emptyState');
        const template = document.getElementById('productRowTemplate');
        
        emptyState.style.display = 'none';

        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('tr');
        
        row.querySelector('.row-index').innerText = rowCount;
        row.innerHTML = row.innerHTML.replace(/INDEX/g, rowCount);

        const select = row.querySelector('select');
        const filtered = allProducts.filter(p => p.client_id == clientId);

        if (filtered.length === 0) {
            alert("Este cliente no posee productos en el catálogo.");
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
            document.querySelectorAll('.row-index').forEach((el, i) => el.innerText = i + 1);
        }
    }
</script>
@endsection