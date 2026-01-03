@extends('layouts.admin')

@section('title', 'Nueva Orden de Salida')
@section('header_title', 'Registrar Pedido')

@section('content')

    <div class="max-w-6xl mx-auto">
        
        <!-- Alertas de validación -->
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
                
                <!-- COLUMNA IZQUIERDA (2/3) -->
                <div class="lg:col-span-2 space-y-6">
                    
                    <!-- Información de la Orden -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="font-bold text-slate-700 text-sm flex items-center gap-2 uppercase tracking-wider">
                                <i class="fa-solid fa-file-invoice text-custom-primary"></i> Datos del Pedido
                            </h3>
                        </div>
                        <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dueño de Mercancía (Cliente) *</label>
                                <select name="client_id" id="client_id" required onchange="handleClientChange()" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none bg-white transition font-bold text-slate-700">
                                    <option value="">-- Seleccionar Cliente --</option>
                                    @foreach($clients as $client)
                                        <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                                    @endforeach
                                </select>
                                <p class="text-[9px] text-slate-400 mt-1 uppercase tracking-tighter">Esto filtrará los productos disponibles.</p>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Número de Orden # *</label>
                                <input type="text" name="order_number" value="{{ $nextOrderNumber }}" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm font-bold text-slate-700 bg-slate-50">
                            </div>
                        </div>
                    </div>

                    <!-- Información del Destinatario -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50">
                            <h3 class="font-bold text-slate-700 text-sm flex items-center gap-2 uppercase tracking-wider">
                                <i class="fa-solid fa-user-tag text-custom-primary"></i> Destinatario y Entrega
                            </h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nombre Completo / Razón Social *</label>
                                    <input type="text" name="customer_name" required placeholder="Ej: Juan Pérez" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm outline-none focus:border-custom-primary">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Cédula o RIF (Requerido por Courier) *</label>
                                    <input type="text" name="customer_id_number" required placeholder="Ej: V-12345678" class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm font-mono focus:border-custom-primary uppercase">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">País *</label>
                                    <select name="country" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm bg-white">
                                        <option value="Venezuela" selected>Venezuela</option>
                                        <option value="Internacional">Exportación Internacional</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Estado / Provincia *</label>
                                    <select name="state" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm bg-white">
                                        <option value="">-- Seleccionar --</option>
                                        @foreach($states as $state)
                                            <option value="{{ $state }}">{{ $state }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Ciudad *</label>
                                    <input type="text" name="city" required class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm">
                                </div>
                            </div>

                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dirección Detallada *</label>
                                <textarea name="shipping_address" rows="2" required placeholder="Calle, edificio, punto de referencia..." class="w-full px-3 py-2.5 border border-slate-300 rounded-xl text-sm"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido del Pedido (Filtrado Dinámicamente) -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                            <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Productos y Cantidades</h3>
                            <button type="button" onclick="addProductRow()" class="text-[10px] bg-custom-primary text-white px-3 py-1.5 rounded-lg font-bold hover:brightness-95 transition flex items-center gap-1 shadow-md shadow-blue-500/20">
                                <i class="fa-solid fa-plus"></i> Agregar SKU
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
                                    <!-- Aquí se inyectan las filas -->
                                </tbody>
                            </table>
                        </div>
                        <div id="emptyItems" class="p-12 text-center text-slate-400">
                            <i class="fa-solid fa-cart-plus text-4xl mb-3 opacity-20"></i>
                            <p class="text-xs">Seleccione un cliente y agregue productos a la lista.</p>
                        </div>
                    </div>
                </div>

                <!-- COLUMNA DERECHA (1/3) -->
                <div class="space-y-6">
                    <div class="bg-slate-800 text-white rounded-2xl p-6 shadow-xl sticky top-6 border border-white/5">
                        <h3 class="font-bold text-lg mb-6 border-b border-white/10 pb-4">Control de Operación</h3>
                        
                        <div class="mb-6 space-y-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Método de Envío sugerido</label>
                                <input type="text" name="shipping_method" placeholder="Ej: Zoom, MRW, Motorizado" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-xl text-xs text-white outline-none focus:border-blue-500">
                            </div>
                            
                            <div>
                                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-2">Instrucciones Especiales</label>
                                <textarea name="notes" rows="4" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded-xl text-xs text-white outline-none focus:border-blue-500" placeholder="Notas de picking o empaque..."></textarea>
                            </div>
                        </div>

                        <div class="p-4 bg-white/5 rounded-xl border border-white/10 mb-8">
                            <div class="flex justify-between items-center text-xs mb-2">
                                <span class="text-slate-400">Total SKUs:</span>
                                <span id="totalSKUCount" class="font-bold text-blue-400">0</span>
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-slate-400">Unidades:</span>
                                <span id="totalUnitsCount" class="font-bold text-blue-400">0</span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3">
                            <button type="submit" class="w-full bg-custom-primary text-white py-3.5 rounded-xl font-bold shadow-lg flex items-center justify-center gap-2 transition hover:brightness-110 active:scale-95">
                                <i class="fa-solid fa-check-circle"></i> Procesar Pedido
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

    <!-- Plantilla de Fila -->
    <template id="productRowTemplate">
        <tr class="group hover:bg-slate-50 transition">
            <td class="px-6 py-4 text-center text-slate-400 font-mono text-xs row-index">1</td>
            <td class="px-6 py-4">
                <select name="items[INDEX][product_id]" required onchange="handleProductSelect(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm bg-white focus:ring-2 ring-custom-primary outline-none transition sku-selector">
                    <!-- Opciones cargadas por JS segun cliente -->
                </select>
                <div class="mt-1 flex items-center gap-2">
                    <span class="text-[9px] font-bold text-slate-400 uppercase">Disponible:</span>
                    <span class="text-[9px] font-black text-custom-primary stock-display">0 unidades</span>
                </div>
            </td>
            <td class="px-6 py-4">
                <input type="number" name="items[INDEX][qty]" min="1" required value="1" oninput="updateCalculations(this)" class="w-full px-3 py-2 border border-slate-200 rounded-lg text-sm text-center font-bold qty-input">
            </td>
            <td class="px-6 py-4 text-center">
                <button type="button" onclick="removeRow(this)" class="text-slate-300 hover:text-red-500 transition">
                    <i class="fa-solid fa-trash-can"></i>
                </button>
            </td>
        </tr>
    </template>

@endsection

@section('scripts')
<script>
    // Lista maestra de productos inyectada de forma segura para evitar ParseErrors
    // SE ELIMINA EL FILTRO where('is_active', true) ya que la columna no existe en la base de datos
    const allProducts = {!! \App\Models\Product::withSum('inventory as total_stock', 'quantity')->get()->toJson() !!};
    let rowIndex = 0;

    /**
     * Maneja el cambio de cliente
     */
    function handleClientChange() {
        const container = document.getElementById('itemsContainer');
        
        if (container.children.length > 0) {
            if (confirm("Si cambia de cliente, se eliminarán los productos actuales de la lista. ¿Desea continuar?")) {
                container.innerHTML = '';
                document.getElementById('emptyItems').classList.remove('hidden');
                updateCalculations();
            }
        }
    }

    /**
     * Al seleccionar un SKU, actualizamos el límite de la cantidad según el stock
     */
    function handleProductSelect(selectElement) {
        const productId = selectElement.value;
        const row = selectElement.closest('tr');
        const stockDisplay = row.querySelector('.stock-display');
        const qtyInput = row.querySelector('.qty-input');
        
        const product = allProducts.find(p => p.id == productId);
        
        if (product) {
            const stock = parseInt(product.total_stock) || 0;
            stockDisplay.innerText = `${stock} unidades`;
            qtyInput.max = stock; // Limitamos el input al stock real
            
            if (parseInt(qtyInput.value) > stock) {
                qtyInput.value = stock;
            }
        } else {
            stockDisplay.innerText = '0 unidades';
            qtyInput.max = 0;
        }
        updateCalculations();
    }

    /**
     * Agrega una nueva fila de producto filtrada por cliente y con STOCK > 0
     */
    function addProductRow() {
        const clientId = document.getElementById('client_id').value;
        
        if (!clientId) {
            alert("Por favor, seleccione primero el Dueño de la Mercancía (Cliente).");
            return;
        }

        // Filtramos: 1. Que pertenezca al cliente. 2. Que tenga STOCK > 0.
        const filteredProducts = allProducts.filter(p => p.client_id == clientId && (parseInt(p.total_stock) > 0));
        
        if (filteredProducts.length === 0) {
            alert("Este cliente no tiene productos con stock disponible en bodega.");
            return;
        }

        rowIndex++;
        const container = document.getElementById('itemsContainer');
        const emptyState = document.getElementById('emptyItems');
        const template = document.getElementById('productRowTemplate');
        
        emptyState.classList.add('hidden');

        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('tr');
        row.innerHTML = row.innerHTML.replace(/INDEX/g, rowIndex);
        row.querySelector('.row-index').innerText = container.children.length + 1;
        
        // Poblar el selector de SKUs
        const select = row.querySelector('.sku-selector');
        select.innerHTML = '<option value="">-- Seleccionar SKU --</option>';
        filteredProducts.forEach(p => {
            const option = document.createElement('option');
            option.value = p.id;
            option.textContent = `${p.sku} | ${p.name} (Stock: ${parseInt(p.total_stock)})`;
            select.appendChild(option);
        });

        container.appendChild(row);
        updateCalculations();
    }

    function removeRow(button) {
        const row = button.closest('tr');
        row.remove();
        
        const container = document.getElementById('itemsContainer');
        if (container.children.length === 0) {
            document.getElementById('emptyItems').classList.remove('hidden');
        }
        
        document.querySelectorAll('#itemsContainer tr').forEach((r, idx) => {
            r.querySelector('.row-index').innerText = idx + 1;
        });
        
        updateCalculations();
    }

    function updateCalculations(input = null) {
        // Validación visual de exceso de stock si se disparó desde un input
        if (input && input.max && parseInt(input.value) > parseInt(input.max)) {
            input.value = input.max;
        }

        const rows = document.querySelectorAll('#itemsContainer tr');
        let totalUnits = 0;
        
        rows.forEach(row => {
            const qtyInput = row.querySelector('.qty-input');
            if(qtyInput.value) totalUnits += parseInt(qtyInput.value);
        });

        document.getElementById('totalSKUCount').innerText = rows.length;
        document.getElementById('totalUnitsCount').innerText = totalUnits;
    }
</script>
@endsection