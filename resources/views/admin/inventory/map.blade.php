@extends('layouts.admin')

@section('title', request('view') == 'map' ? 'Mapa de Planta' : 'Infraestructura')
@section('header_title', request('view') == 'map' ? 'Visor de Bodegas' : 'Configuración Logística')

@section('content')

{{-- ALERTAS DE ÉXITO/ERROR --}}
@if(session('success'))
<div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded shadow-sm animate-fade-in">
    <p class="font-bold text-sm"><i class="fa-solid fa-check-circle mr-2"></i> Operación Exitosa</p>
    <p class="text-xs">{{ session('success') }}</p>
</div>
@endif

@if(request('view') == 'map')
    {{-- ========================================== --}}
    {{-- MODO MAPA INTERACTIVO (?view=map)          --}}
    {{-- ========================================== --}}
    <div class="flex flex-col h-full space-y-4">
        <!-- Barra Superior -->
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex justify-between items-center">
            <div>
                <h2 class="font-bold text-slate-700 flex items-center gap-2">
                    <i class="fa-solid fa-layer-group text-custom-primary"></i> Planta Física
                </h2>
                <p class="text-xs text-slate-500">Navegue por la estructura: Sucursal > Bodega > Pasillo > Rack</p>
            </div>
            <div class="flex gap-2">
                <!-- BOTÓN DE IMPRESIÓN (Se activa con JS) -->
                <a id="printWhLabelsBtn" href="#" target="_blank" class="hidden text-xs font-bold text-slate-600 bg-white border border-slate-300 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition flex items-center gap-2">
                    <i class="fa-solid fa-print"></i> Rotular Bodega
                </a>

                <a href="{{ route('admin.bintypes.index') }}" class="text-xs font-bold text-slate-600 bg-white border border-slate-300 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition flex items-center gap-2">
                    <i class="fa-solid fa-cubes"></i> Configurar Tipos de Bines
                </a>

                {{-- FIX: Ruta de retorno dinámica. Si no se pasa $backRoute, usa la de sucursales por defecto --}}
                <a href="{{ isset($backRoute) ? route($backRoute) : route('admin.branches.index') }}" class="text-xs font-bold text-white bg-slate-700 px-3 py-1.5 rounded-lg hover:bg-slate-800 transition flex items-center gap-2">
                    <i class="fa-solid fa-arrow-left"></i> Volver a Gestión
                </a>
            </div>
        </div>

        <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-6 min-h-0">
            <!-- Navegador Lateral -->
            <div class="lg:col-span-3 flex flex-col gap-4 overflow-y-auto pr-2 custom-scrollbar max-h-[calc(100vh-220px)]">
                @forelse($branches as $branch)
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-3 bg-slate-100 border-b border-slate-200 font-bold text-slate-700 text-xs uppercase tracking-wider flex justify-between items-center">
                            <span><i class="fa-solid fa-building mr-1"></i> {{ $branch->name }}</span>
                        </div>
                        <div class="divide-y divide-slate-50">
                            @foreach($branch->warehouses as $warehouse)
                                <button onclick="loadMap('{{ $warehouse->id }}', '{{ $warehouse->name }}', '{{ $warehouse->code }}', {{ $warehouse->rows }}, {{ $warehouse->cols }}, {{ $warehouse->levels ?? 1 }})" 
                                        class="w-full text-left p-3 hover:bg-blue-50 transition flex justify-between items-center group focus:bg-blue-100 focus:outline-none border-l-4 border-transparent focus:border-custom-primary">
                                    <div class="flex items-center gap-3">
                                        <i class="fa-solid fa-warehouse text-slate-300 group-hover:text-custom-primary"></i>
                                        <div>
                                            <span class="text-xs font-bold text-slate-600 group-hover:text-custom-primary block">{{ $warehouse->name }}</span>
                                            <span class="text-[9px] text-slate-400">{{ $warehouse->rows }} Pasillos | {{ $warehouse->cols }} Racks/Lado</span>
                                        </div>
                                    </div>
                                    <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="p-4 text-center text-slate-400 text-xs italic">No hay infraestructura configurada.</div>
                @endforelse
            </div>

            <!-- Lienzo del Mapa -->
            <div class="lg:col-span-9 bg-white rounded-xl shadow-sm border border-slate-200 flex flex-col relative overflow-hidden">
                <div id="empty-map-state" class="absolute inset-0 flex flex-col items-center justify-center bg-slate-50/50 z-10">
                    <div class="w-20 h-20 bg-white rounded-full shadow-sm flex items-center justify-center mb-4">
                        <i class="fa-solid fa-map text-4xl text-slate-300 animate-pulse"></i>
                    </div>
                    <h3 class="font-bold text-slate-700">Seleccione una Bodega</h3>
                    <p class="text-sm text-slate-500">Visualice la distribución de pasillos y racks.</p>
                </div>

                <div id="map-header" class="hidden p-3 border-b border-slate-100 flex justify-between items-center bg-white z-20 shadow-sm">
                    <div>
                        <h3 id="current-warehouse-name" class="font-bold text-slate-800 text-lg">Mapa</h3>
                        <p id="current-warehouse-details" class="text-[10px] text-slate-500">---</p>
                    </div>
                    <div class="flex gap-3 text-[10px] uppercase font-bold text-slate-500">
                        <span class="flex items-center gap-1"><div class="w-3 h-3 bg-blue-600 rounded-sm"></div> Rack Lado A (Izq)</span>
                        <span class="flex items-center gap-1"><div class="w-3 h-3 bg-indigo-600 rounded-sm"></div> Rack Lado B (Der)</span>
                        <span class="flex items-center gap-1"><div class="w-10 h-3 bg-slate-100 border-dashed border-slate-300 border-b-2"></div> Pasillo</span>
                    </div>
                </div>

                <div id="map-container" class="hidden flex-1 overflow-auto p-8 bg-slate-100 relative cursor-grab active:cursor-grabbing">
                    <!-- Contenedor Flexible para los Pasillos -->
                    <div id="warehouse-layout" class="flex flex-col gap-8 mx-auto w-fit p-4 pb-20">
                        <!-- Los pasillos se generan aquí con JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>

@else

    {{-- ========================================== --}}
    {{-- MODO GESTIÓN (CRUD) - Default              --}}
    {{-- ========================================== --}}
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-end gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Sucursales y Bodegas</h2>
                <p class="text-sm text-slate-500">Defina la estructura física: Sede -> Bodegas -> Mapa.</p>
            </div>
            <div class="flex gap-3">
                <!-- Acceso directo al módulo de Bines -->
                <a href="{{ route('admin.bintypes.index') }}" class="bg-white border border-slate-300 text-slate-600 px-4 py-2.5 rounded-xl font-bold hover:bg-slate-50 transition flex items-center gap-2 text-sm shadow-sm">
                    <i class="fa-solid fa-cubes"></i> Tipos de Bines
                </a>
                <button onclick="openBranchModal()" class="bg-custom-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:brightness-95 transition flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Nueva Sucursal
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($branches as $branch)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition-all group relative">
                    
                    <!-- Botones Acciones Sucursal -->
                    <div class="absolute top-4 right-4 flex gap-2">
                        <button onclick="editBranch({{ $branch }})" class="text-slate-300 hover:text-custom-primary transition bg-slate-50 p-2 rounded-full hover:bg-blue-50" title="Editar Sede">
                            <i class="fa-solid fa-pen-to-square"></i>
                        </button>
                        <!-- Formulario Eliminar Sucursal -->
                        <form action="{{ route('admin.branches.destroy', $branch->id) }}" method="POST" onsubmit="return confirm('¿ESTÁ SEGURO? Se eliminarán todas las bodegas y el inventario asociado a esta sucursal.');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-slate-300 hover:text-red-600 transition bg-slate-50 p-2 rounded-full hover:bg-red-50" title="Eliminar Sede">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </div>

                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 bg-blue-50 text-custom-primary rounded-2xl flex items-center justify-center text-xl">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-lg text-slate-800 leading-tight">{{ $branch->name }}</h3>
                            <span class="text-xs font-mono text-slate-400 bg-slate-100 px-1.5 rounded">{{ $branch->code }}</span>
                        </div>
                    </div>

                    <div class="text-sm text-slate-500 mb-6 flex items-start gap-2 min-h-[40px]">
                        <i class="fa-solid fa-location-dot mt-1 text-slate-300"></i>
                        <span>{{ $branch->address ?? 'Sin dirección' }}, {{ $branch->city }}</span>
                    </div>

                    <!-- Lista de Bodegas -->
                    <div class="bg-slate-50 rounded-xl p-4 mb-6 border border-slate-100">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Bodegas Internas</span>
                            <span class="bg-white text-slate-600 text-[10px] font-bold px-2 py-0.5 rounded-full border border-slate-200">{{ $branch->warehouses->count() }}</span>
                        </div>
                        
                        @if($branch->warehouses->count() > 0)
                            <div class="space-y-2 max-h-32 overflow-y-auto custom-scrollbar pr-1">
                                @foreach($branch->warehouses as $warehouse)
                                    <div class="flex justify-between items-center text-xs bg-white p-2 rounded border border-slate-200 group/wh">
                                        <div>
                                            <span class="font-bold text-slate-600 truncate max-w-[100px] block">{{ $warehouse->name }}</span>
                                            <span class="text-[9px] text-slate-400">{{ $warehouse->rows }}x{{ $warehouse->cols }} | {{ $warehouse->levels ?? 1 }} Niveles</span>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <!-- Editar Bodega -->
                                            <button onclick="editWarehouse({{ $warehouse }})" class="text-slate-400 hover:text-custom-primary p-1 hover:bg-slate-100 rounded" title="Configurar">
                                                <i class="fa-solid fa-gear"></i>
                                            </button>
                                            
                                            <!-- Eliminar Bodega -->
                                            <form action="{{ route('admin.warehouses.destroy', $warehouse->id) }}" method="POST" onsubmit="return confirm('¿Eliminar bodega {{ $warehouse->name }}? Esta acción es irreversible.');" class="inline">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-slate-400 hover:text-red-500 p-1 hover:bg-red-50 rounded" title="Eliminar Bodega">
                                                    <i class="fa-solid fa-xmark"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-2 text-xs text-slate-400 italic">No hay bodegas creadas.</div>
                        @endif
                    </div>

                    <!-- Acciones -->
                    <div class="grid grid-cols-2 gap-3">
                        <button onclick="openWarehouseModal('{{ $branch->id }}')" class="py-2 px-3 bg-white border border-slate-200 text-slate-600 rounded-lg text-xs font-bold hover:bg-slate-50 transition">
                            <i class="fa-solid fa-warehouse mr-1"></i> + Bodega
                        </button>
                        
                        {{-- FIX: Enlace dinámico a la vista de mapa usando la misma ruta base --}}
                        <a href="{{ request()->fullUrlWithQuery(['view' => 'map']) }}" class="py-2 px-3 bg-custom-primary/10 text-custom-primary border border-transparent rounded-lg text-xs font-bold hover:bg-custom-primary hover:text-white transition text-center flex items-center justify-center">
                            Ver Planta <i class="fa-solid fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center">
                    <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-slate-100 mb-4">
                        <i class="fa-solid fa-city text-4xl text-slate-300"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-700">No hay infraestructura</h3>
                    <button onclick="openBranchModal()" class="mt-4 bg-custom-primary text-white px-6 py-3 rounded-xl font-bold">Crear Primera Sucursal</button>
                </div>
            @endforelse
        </div>
    </div>
@endif

{{-- ========================================== --}}
{{-- MODALES DE CREACIÓN Y EDICIÓN              --}}
{{-- ========================================== --}}

<!-- MODAL SUCURSAL -->
<div id="branchModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 id="branchModalTitle" class="font-bold text-slate-700">Nueva Sucursal</h3>
            <button onclick="closeModal('branchModal')" class="text-slate-400 hover:text-red-500"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form id="branchForm" action="{{ route('admin.branches.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="_method" id="branchMethod" value="POST">
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nombre Sede *</label>
                <input type="text" name="name" id="branchName" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm outline-none focus:border-custom-primary">
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Código *</label>
                    <input type="text" name="code" id="branchCode" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm font-mono uppercase outline-none focus:border-custom-primary">
                </div>
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Ciudad *</label>
                    <input type="text" name="city" id="branchCity" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm outline-none focus:border-custom-primary">
                </div>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dirección</label>
                <textarea name="address" id="branchAddress" rows="2" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm outline-none focus:border-custom-primary"></textarea>
            </div>
            <button type="submit" id="branchSubmitBtn" class="w-full bg-custom-primary text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-blue-500/20">Registrar Sucursal</button>
        </form>
    </div>
</div>

<!-- MODAL BODEGA -->
<div id="warehouseModal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center backdrop-blur-sm p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden animate-fade-in">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
            <h3 id="whModalTitle" class="font-bold text-slate-700">Nueva Bodega</h3>
            <button onclick="closeModal('warehouseModal')" class="text-slate-400 hover:text-red-500 text-xl"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form id="whForm" action="{{ route('admin.warehouses.store') }}" method="POST" class="p-6 space-y-4">
            @csrf
            <input type="hidden" name="_method" id="whMethod" value="POST">
            
            <div id="branchSelectGroup">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Sucursal Padre *</label>
                <select name="branch_id" id="modal_branch_select" required class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm outline-none bg-white">
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Nombre Bodega *</label>
                    <input type="text" name="name" id="whName" required placeholder="Ej: Zona A" class="w-full px-4 py-2 border border-slate-300 rounded-lg text-sm outline-none focus:border-custom-primary">
                </div>
                <div class="col-span-1">
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Código *</label>
                    <input type="text" name="code" id="whCode" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm font-mono uppercase text-center outline-none focus:border-custom-primary" placeholder="B-01">
                </div>
            </div>

            <hr class="border-slate-100">
            
            <!-- Configuración del Mapa -->
            <div>
                <p class="text-[10px] text-custom-primary font-bold uppercase tracking-widest mb-2"><i class="fa-solid fa-ruler-combined mr-1"></i> Estructura Física</p>
                <div class="grid grid-cols-3 gap-3">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1" title="Cantidad de pasillos caminables">Pasillos (Total)</label>
                        <input type="number" name="rows" id="whRows" value="5" min="1" max="50" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-center outline-none focus:border-custom-primary">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1" title="Cuantas columnas de racks tiene cada pasillo">Racks por Lado</label>
                        <input type="number" name="cols" id="whCols" value="10" min="1" max="50" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-center outline-none focus:border-custom-primary">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 mb-1" title="Altura de los racks">Niveles (Altura)</label>
                        <input type="number" name="levels" id="whLevels" value="1" min="1" max="10" required class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm text-center outline-none focus:border-custom-primary">
                    </div>
                </div>
                <p class="text-[9px] text-slate-400 mt-2 leading-tight">Nota: Cada pasillo tendrá racks a izquierda y derecha. Podrás configurar los bines de cada posición en el mapa.</p>
            </div>

            <button type="submit" id="whSubmitBtn" class="w-full bg-custom-primary text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-blue-500/20">Guardar Configuración</button>
        </form>
    </div>
</div>

{{-- PANEL LATERAL (Mapa) --}}
@if(request('view') == 'map')
<div id="rackDetailPanel" class="fixed inset-y-0 right-0 w-96 bg-white shadow-2xl z-50 transform translate-x-full transition-transform duration-300 flex flex-col border-l border-slate-200">
    <div class="p-6 bg-slate-900 text-white flex justify-between items-start">
        <div>
            <h3 class="font-bold text-xl mb-1">Configurar Rack</h3>
            <p id="panel-coords" class="text-xs text-blue-400 font-mono uppercase tracking-widest">---</p>
        </div>
        <button onclick="closeRackPanel()" class="text-white/50 hover:text-white"><i class="fa-solid fa-xmark text-xl"></i></button>
    </div>
    
    <div class="flex-1 p-6 overflow-y-auto bg-slate-50 relative">
        <!-- Loader Visual -->
        <div id="panel-loader" class="absolute inset-0 bg-white/80 z-20 flex items-center justify-center hidden">
            <i class="fa-solid fa-circle-notch fa-spin text-custom-primary text-2xl"></i>
        </div>

        <!-- Info de Ubicación -->
        <div class="bg-white p-4 rounded-xl border border-slate-200 mb-6 shadow-sm">
            <h4 class="text-xs font-bold text-slate-500 uppercase mb-3">Detalle de Ubicación</h4>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div class="p-2 bg-slate-50 rounded border border-slate-100">
                    <span class="block text-[9px] text-slate-400 uppercase">Pasillo</span>
                    <span id="detail-aisle" class="font-bold text-slate-700">P-01</span>
                </div>
                <div class="p-2 bg-slate-50 rounded border border-slate-100">
                    <span class="block text-[9px] text-slate-400 uppercase">Lado</span>
                    <span id="detail-side" class="font-bold text-slate-700">Lado A</span>
                </div>
                <div class="col-span-2 p-2 bg-slate-50 rounded border border-slate-100">
                    <span class="block text-[9px] text-slate-400 uppercase">Posición (Columna)</span>
                    <span id="detail-col" class="font-bold text-slate-700">R-05</span>
                </div>
            </div>
        </div>

        <!-- Generador de Etiquetas -->
        <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 mb-6">
            <div class="flex items-center gap-2 mb-2">
                <i class="fa-solid fa-qrcode text-blue-600"></i>
                <span class="text-xs font-bold text-blue-700 uppercase">Vista Previa Etiqueta</span>
            </div>
            <div class="bg-white p-2 rounded border border-blue-200 text-center">
                <p id="qr-preview-code" class="font-mono text-xs font-bold text-slate-800 tracking-wider">CODE</p>
                <div class="h-8 bg-slate-800 mx-auto mt-2 w-32 rounded-sm opacity-20"></div>
            </div>
            <p class="text-[9px] text-blue-500 mt-2 text-center">Formato: BODEGA-PASILLO-LADO-RACK-NIVEL-BIN</p>
        </div>

        <!-- Configuración de Niveles -->
        <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Configuración de Niveles</h4>
        <div id="rack-levels-container" class="space-y-4">
            <!-- Se llena con JS -->
        </div>
    </div>
    
    <div class="p-4 border-t border-slate-200 bg-white">
        <button onclick="saveRackConfiguration()" class="w-full bg-custom-primary text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-blue-500/20 active:scale-95 transition">Guardar Configuración</button>
    </div>
</div>
@endif

@endsection

@section('scripts')
<script>
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    // --- SUCURSAL ---
    function openBranchModal() {
        const form = document.getElementById('branchForm');
        form.reset();
        form.action = "{{ route('admin.branches.store') }}";
        document.getElementById('branchMethod').value = "POST";
        document.getElementById('branchModalTitle').innerText = "Nueva Sucursal";
        document.getElementById('branchSubmitBtn').innerText = "Registrar Sucursal";
        document.getElementById('branchModal').classList.remove('hidden');
    }

    function editBranch(branch) {
        document.getElementById('branchName').value = branch.name;
        document.getElementById('branchCode').value = branch.code;
        document.getElementById('branchCity').value = branch.city;
        document.getElementById('branchAddress').value = branch.address;
        
        document.getElementById('branchForm').action = `/admin/branches/${branch.id}`;
        document.getElementById('branchMethod').value = "PUT";
        
        document.getElementById('branchModalTitle').innerText = "Editar Sucursal";
        document.getElementById('branchSubmitBtn').innerText = "Guardar Cambios";
        document.getElementById('branchModal').classList.remove('hidden');
    }

    // --- BODEGA ---
    function openWarehouseModal(branchId = null) {
        const form = document.getElementById('whForm');
        form.reset();
        form.action = "{{ route('admin.warehouses.store') }}";
        document.getElementById('whMethod').value = "POST";
        
        if(branchId) document.getElementById('modal_branch_select').value = branchId;
        
        document.getElementById('whModalTitle').innerText = "Nueva Bodega";
        document.getElementById('whSubmitBtn').innerText = "Generar Mapa";
        document.getElementById('warehouseModal').classList.remove('hidden');
    }

    function editWarehouse(wh) {
        document.getElementById('whName').value = wh.name;
        document.getElementById('whCode').value = wh.code;
        document.getElementById('whRows').value = wh.rows;
        document.getElementById('whCols').value = wh.cols;
        document.getElementById('whLevels').value = wh.levels || 1;
        document.getElementById('modal_branch_select').value = wh.branch_id;

        document.getElementById('whForm').action = `/admin/warehouses/${wh.id}`;
        document.getElementById('whMethod').value = "PUT";

        document.getElementById('whModalTitle').innerText = "Configurar Bodega";
        document.getElementById('whSubmitBtn').innerText = "Actualizar Dimensiones";
        document.getElementById('warehouseModal').classList.remove('hidden');
    }

    // --- MAPA ---
    @if(request('view') == 'map')
    let currentLevels = 1;
    let currentWhCode = '';
    let currentWhId = null;
    let selectedRack = { aisle: 0, side: '', col: 0 };
    
    // Objeto JS con los tipos de bines desde PHP para usarlos en el panel
    const binTypes = @json($binTypes ?? []);

    function loadMap(id, name, code, aisles, racksPerSide, levels) {
        document.getElementById('empty-map-state').classList.add('hidden');
        document.getElementById('map-header').classList.remove('hidden');
        document.getElementById('map-container').classList.remove('hidden');
        
        document.getElementById('current-warehouse-name').innerText = name;
        document.getElementById('current-warehouse-details').innerText = `${aisles} Pasillos | ${racksPerSide} Racks/Lado | ${levels} Niveles`;
        currentLevels = levels;
        currentWhCode = code;
        currentWhId = id;

        // ACTIVAR BOTÓN DE IMPRESIÓN
        const printBtn = document.getElementById('printWhLabelsBtn');
        printBtn.href = `/admin/warehouses/${id}/labels`; // Construir URL dinámica
        printBtn.classList.remove('hidden');
        printBtn.title = `Imprimir etiquetas para ${name}`;

        const layout = document.getElementById('warehouse-layout');
        layout.innerHTML = ''; 

        for (let p = 1; p <= aisles; p++) {
            const aisleContainer = document.createElement('div');
            aisleContainer.className = "flex flex-col bg-white p-2 rounded-xl shadow-sm border border-slate-200";
            aisleContainer.innerHTML = `<div class="text-[10px] font-bold text-slate-400 mb-1 uppercase tracking-wider text-center">Pasillo ${p}</div>`;

            const rowA = document.createElement('div');
            rowA.className = "flex gap-1 justify-center mb-1";
            for (let c = 1; c <= racksPerSide; c++) rowA.appendChild(createRackCell(p, 'A', c));
            aisleContainer.appendChild(rowA);

            const path = document.createElement('div');
            path.className = "h-8 bg-slate-100 border-y border-dashed border-slate-300 rounded flex items-center justify-center mb-1 relative overflow-hidden";
            path.innerHTML = '<div class="absolute inset-0 flex items-center justify-center opacity-10"><i class="fa-solid fa-arrow-right text-slate-400 text-xs mr-4"></i><i class="fa-solid fa-arrow-right text-slate-400 text-xs"></i></div>';
            aisleContainer.appendChild(path);

            const rowB = document.createElement('div');
            rowB.className = "flex gap-1 justify-center";
            for (let c = 1; c <= racksPerSide; c++) rowB.appendChild(createRackCell(p, 'B', c));
            aisleContainer.appendChild(rowB);

            layout.appendChild(aisleContainer);
        }
    }

    function createRackCell(aisle, side, col) {
        const cell = document.createElement('div');
        const colorClass = side === 'A' ? 'bg-blue-600 border-blue-700' : 'bg-indigo-600 border-indigo-700';
        cell.className = `w-8 h-8 rounded-md border ${colorClass} text-white flex flex-col items-center justify-center cursor-pointer transition-all hover:scale-110 hover:shadow-lg hover:z-10 shadow-sm`;
        cell.title = `Pasillo ${aisle} - Lado ${side} - Rack ${col}`;
        cell.onclick = () => openRackPanel(aisle, side, col);
        cell.innerHTML = `<span class="text-[7px] font-mono font-bold">${side}${col}</span>`;
        return cell;
    }

    // --- AJAX LOGIC ---

    function openRackPanel(aisle, side, col) {
        selectedRack = { aisle, side, col };
        const panel = document.getElementById('rackDetailPanel');
        const container = document.getElementById('rack-levels-container');
        const loader = document.getElementById('panel-loader');
        
        panel.classList.remove('translate-x-full');
        loader.classList.remove('hidden');

        document.getElementById('panel-coords').innerText = `RACK: P${aisle}-${side}-${col}`;
        document.getElementById('detail-aisle').innerText = `P-${aisle.toString().padStart(2, '0')}`;
        document.getElementById('detail-side').innerText = `Lado ${side}`;
        document.getElementById('detail-col').innerText = `R-${col.toString().padStart(2, '0')}`;
        
        const baseCode = `${currentWhCode}-P${aisle.toString().padStart(2,'0')}-${side}-R${col.toString().padStart(2,'0')}`;
        document.getElementById('qr-preview-code').innerText = baseCode + "-XX-XX";

        fetch(`{{ route('admin.inventory.rack_details') }}?warehouse_id=${currentWhId}&aisle=${aisle}&side=${side}&rack_col=${col}`)
            .then(res => res.json())
            .then(data => {
                renderLevels(container, baseCode, data.levels);
            })
            .catch(err => {
                console.error(err);
                renderLevels(container, baseCode, {});
            })
            .finally(() => {
                loader.classList.add('hidden');
            });
    }

    function renderLevels(container, baseCode, savedData) {
        container.innerHTML = '';
        
        let binOptions = '<option value="">-- Seleccionar --</option>';
        binTypes.forEach(bt => {
            binOptions += `<option value="${bt.id}">${bt.name} (${bt.length}x${bt.width})</option>`;
        });

        const levelsToRender = currentLevels; 

        for(let l = levelsToRender; l >= 1; l--) {
            const levelCode = `${baseCode}-N${l.toString().padStart(2,'0')}`;
            const savedLevel = savedData[l] || null;
            const savedQty = savedLevel ? savedLevel.bins_count : 1;
            const savedType = savedLevel ? savedLevel.bin_type_id : '';

            const levelRow = document.createElement('div');
            levelRow.className = "bg-white p-3 rounded-lg border border-slate-200 hover:border-blue-300 transition group rack-level-row";
            levelRow.setAttribute('data-level', l);
            
            levelRow.innerHTML = `
                <div class="flex justify-between items-center mb-2">
                    <div class="flex items-center gap-2">
                        <span class="w-5 h-5 rounded bg-slate-800 text-white flex items-center justify-center text-[10px] font-bold">${l}</span>
                        <span class="text-xs font-bold text-slate-700">Nivel ${l}</span>
                        ${savedLevel ? '<span class="text-[9px] bg-green-100 text-green-700 px-1 rounded font-bold">GUARDADO</span>' : ''}
                    </div>
                    <span class="text-[9px] text-slate-400 font-mono">${levelCode}</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[8px] uppercase font-bold text-slate-400 mb-1">Cant. Bines</label>
                        <input type="number" value="${savedQty}" min="1" class="w-full text-xs border border-slate-200 rounded p-1 text-center focus:border-blue-500 outline-none js-bin-qty">
                    </div>
                    <div>
                        <label class="block text-[8px] uppercase font-bold text-slate-400 mb-1">Tipo de Bin</label>
                        <select class="w-full text-[10px] border border-slate-200 rounded p-1 bg-slate-50 outline-none js-bin-type">
                            ${binOptions}
                        </select>
                    </div>
                </div>
            `;
            if(savedType) {
                levelRow.querySelector('.js-bin-type').value = savedType;
            }
            container.appendChild(levelRow);
        }
    }

    function saveRackConfiguration() {
        if(!currentWhId) return;

        const btn = document.querySelector('button[onclick="saveRackConfiguration()"]');
        const originalText = btn.innerText;
        btn.innerText = "Guardando...";
        btn.disabled = true;

        const levelsData = [];
        document.querySelectorAll('.rack-level-row').forEach(row => {
            levelsData.push({
                level: row.getAttribute('data-level'),
                bins_count: row.querySelector('.js-bin-qty').value,
                bin_type_id: row.querySelector('.js-bin-type').value || null
            });
        });

        fetch("{{ route('admin.inventory.save_rack') }}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({
                warehouse_id: currentWhId,
                aisle: selectedRack.aisle,
                side: selectedRack.side,
                rack_col: selectedRack.col,
                levels_config: levelsData
            })
        })
        .then(res => res.json())
        .then(data => {
            alert("¡Guardado! Los bines han sido generados en el sistema.");
            closeRackPanel();
        })
        .catch(err => {
            console.error(err);
            alert("Error al guardar. Revisa la consola.");
        })
        .finally(() => {
            btn.innerText = originalText;
            btn.disabled = false;
        });
    }

    function closeRackPanel() {
        document.getElementById('rackDetailPanel').classList.add('translate-x-full');
    }
    @endif
</script>
@endsection