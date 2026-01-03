@extends('layouts.admin')

@section('title', request('view') == 'map' ? 'Mapa de Planta' : 'Infraestructura')
@section('header_title', request('view') == 'map' ? 'Visor de Bodegas' : 'Configuración Logística')

@section('content')

{{-- Alertas de Sistema --}}
@if(session('success'))
<div class="bg-emerald-50 border-l-4 border-emerald-500 text-emerald-700 p-3 mb-4 rounded-xl shadow-sm animate-fade-in flex items-center justify-between">
    <p class="text-xs font-bold"><i class="fa-solid fa-check-circle mr-2"></i> {{ session('success') }}</p>
    <i class="fa-solid fa-xmark text-[10px] cursor-pointer opacity-50" onclick="this.parentElement.remove()"></i>
</div>
@endif

@if(request('view') == 'map')
    {{-- ================================================================= --}}
    {{-- MODO MAPA INTERACTIVO: Visualización de Estructura Física         --}}
    {{-- ================================================================= --}}
    <div class="flex flex-col h-full space-y-4">
        <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4">
            <div>
                <h2 class="font-black text-slate-700 flex items-center gap-2 text-base">
                    <i class="fa-solid fa-layer-group text-custom-primary"></i> Planta Física y Almacenamiento
                </h2>
                <p class="text-[9px] text-slate-400 font-bold uppercase tracking-widest">Navegación técnica de bines</p>
            </div>
            <div class="flex gap-2">
                <a id="printWhLabelsBtn" href="#" target="_blank" class="hidden text-[9px] font-black uppercase tracking-widest text-slate-600 bg-white border border-slate-300 px-3 py-1.5 rounded-lg hover:bg-slate-50 transition flex items-center gap-2">
                    <i class="fa-solid fa-print"></i> Rotular
                </a>
                <a href="{{ route('admin.branches.index') }}" class="text-[9px] font-black uppercase tracking-widest text-white bg-slate-700 px-4 py-1.5 rounded-lg hover:bg-slate-800 transition flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-4 min-h-0">
            <!-- Navegador Lateral -->
            <div class="lg:col-span-3 flex flex-col gap-3 overflow-y-auto pr-2 custom-scrollbar">
                @foreach($branches as $branch)
                    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-2.5 bg-slate-50 border-b border-slate-200 font-black text-slate-600 text-[9px] uppercase tracking-widest flex items-center gap-2">
                            <i class="fa-solid fa-building text-custom-primary"></i> {{ $branch->name }}
                        </div>
                        <div class="divide-y divide-slate-50">
                            @foreach($branch->warehouses as $warehouse)
                                <button onclick="loadMap('{{ $warehouse->id }}', '{{ $warehouse->name }}', '{{ $warehouse->code }}', {{ $warehouse->rows }}, {{ $warehouse->cols }}, {{ $warehouse->levels ?? 1 }})" 
                                        class="w-full text-left p-3 hover:bg-blue-50 transition flex justify-between items-center group focus:bg-blue-100 outline-none border-l-4 border-transparent focus:border-custom-primary">
                                    <div class="flex items-center gap-2.5">
                                        <i class="fa-solid fa-warehouse text-slate-300 group-hover:text-custom-primary"></i>
                                        <div>
                                            <span class="text-xs font-bold text-slate-700 block leading-none">{{ $warehouse->name }}</span>
                                            <span class="text-[9px] text-slate-400 font-mono">{{ $warehouse->code }}</span>
                                        </div>
                                    </div>
                                    <i class="fa-solid fa-chevron-right text-[10px] text-slate-300"></i>
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Canvas del Mapa -->
            <div class="lg:col-span-9 bg-white rounded-2xl shadow-sm border border-slate-200 flex flex-col relative overflow-hidden bg-[radial-gradient(#e2e8f0_1px,transparent_1px)] [background-size:20px_20px]">
                <div id="empty-map-state" class="absolute inset-0 flex flex-col items-center justify-center bg-white/80 z-10">
                    <i class="fa-solid fa-map-location-dot text-3xl text-slate-200 mb-2 animate-pulse"></i>
                    <h3 class="font-black text-slate-800 text-sm uppercase">Seleccione una Bodega</h3>
                </div>

                <div id="map-header" class="hidden p-4 border-b border-slate-100 flex justify-between items-center bg-white/95 z-20">
                    <div>
                        <h3 id="current-warehouse-name" class="font-black text-slate-800 text-lg">Mapa</h3>
                        <p id="current-warehouse-details" class="text-[9px] text-slate-400 font-bold uppercase tracking-[0.2em]">---</p>
                    </div>
                    <div class="flex gap-4 text-[9px] font-black text-slate-500 uppercase tracking-widest">
                        <span class="flex items-center gap-1"><div class="w-2.5 h-2.5 bg-blue-600 rounded-sm"></div> Lado A</span>
                        <span class="flex items-center gap-1"><div class="w-2.5 h-2.5 bg-indigo-600 rounded-sm"></div> Lado B</span>
                    </div>
                </div>

                <div id="map-container" class="hidden flex-1 overflow-auto p-8 custom-scrollbar">
                    <div id="warehouse-layout" class="flex flex-col gap-8 mx-auto w-fit p-4 pb-40"></div>
                </div>
            </div>
        </div>
    </div>

@else

    {{-- ================================================================= --}}
    {{-- MODO GESTIÓN: Listado de Sucursales (TAMAÑO REDUCIDO)              --}}
    {{-- ================================================================= --}}
    <div class="space-y-6">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4 bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
            <div>
                <h2 class="text-2xl font-black text-slate-800 tracking-tight">Sucursales y Bodegas</h2>
                <p class="text-slate-500 text-xs font-medium">Gestión de red física y capacidad de almacenamiento.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.bintypes.index') }}" class="bg-slate-50 border border-slate-200 text-slate-600 px-4 py-2 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-white transition flex items-center gap-2">
                    <i class="fa-solid fa-cubes text-blue-500"></i> Bines
                </a>
                <button onclick="openBranchModal()" class="bg-blue-600 text-white px-5 py-2 rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-blue-500/30 hover:bg-blue-700 transition flex items-center gap-2">
                    <i class="fa-solid fa-plus"></i> Sucursal
                </button>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @forelse($branches as $branch)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-xl transition-all group relative flex flex-col border-t-4 border-t-blue-500">
                    
                    <div class="absolute top-4 right-4 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button onclick="editBranch({{ json_encode($branch) }})" class="text-slate-400 hover:text-amber-500 p-1.5 rounded-lg bg-white shadow-sm border" title="Editar"><i class="fa-solid fa-pen text-[10px]"></i></button>
                        <form action="{{ route('admin.branches.destroy', $branch->id) }}" method="POST" onsubmit="return confirm('¿Borrar sucursal?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-slate-400 hover:text-rose-600 p-1.5 rounded-lg bg-white shadow-sm border" title="Eliminar"><i class="fa-solid fa-trash text-[10px]"></i></button>
                        </form>
                    </div>

                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center text-lg shrink-0 shadow-sm border border-blue-100">
                            <i class="fa-solid fa-building"></i>
                        </div>
                        <div class="min-w-0">
                            <h3 class="font-black text-sm text-slate-800 leading-none truncate">{{ $branch->name }}</h3>
                            <span class="text-[9px] font-black text-blue-500 uppercase tracking-tighter mt-1 block">{{ $branch->code }}</span>
                        </div>
                    </div>

                    <div class="text-[11px] text-slate-500 mb-4 space-y-1.5">
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-location-dot text-blue-400 w-3 text-center"></i>
                            <span class="font-bold text-slate-700 truncate">{{ $branch->city }}, {{ $branch->state }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i class="fa-solid fa-globe text-slate-300 w-3 text-center"></i>
                            <span class="uppercase font-bold text-[9px] tracking-widest">{{ $branch->country }}</span>
                        </div>
                    </div>

                    <div class="bg-slate-50 rounded-xl p-3 mb-4 border border-slate-100 flex-1">
                        <div class="flex justify-between items-center mb-2">
                            <span class="text-[8px] font-black text-slate-400 uppercase tracking-[0.1em]">Bodegas</span>
                            <span class="bg-blue-600 text-white text-[9px] font-black px-1.5 rounded">{{ $branch->warehouses->count() }}</span>
                        </div>
                        <div class="space-y-1 max-h-24 overflow-y-auto custom-scrollbar pr-1">
                            @foreach($branch->warehouses as $wh)
                                <div class="flex justify-between items-center bg-white p-2 rounded-lg border border-slate-100 hover:border-blue-200 transition-colors">
                                    <span class="text-[10px] font-bold text-slate-600 truncate">{{ $wh->name }}</span>
                                    <button onclick="editWarehouse({{ $wh }})" class="text-slate-300 hover:text-blue-500"><i class="fa-solid fa-gear text-[9px]"></i></button>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="openWarehouseModal('{{ $branch->id }}')" class="py-2 bg-white border border-slate-200 text-slate-700 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all">
                            + Bodega
                        </button>
                        <a href="{{ request()->fullUrlWithQuery(['view' => 'map']) }}" class="py-2 bg-slate-800 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-black transition-all text-center">
                            Mapa
                        </a>
                    </div>
                </div>
            @empty
                <div class="col-span-full py-12 text-center bg-white rounded-3xl border-2 border-dashed border-slate-200 flex flex-col items-center">
                    <i class="fa-solid fa-city text-3xl text-slate-200 mb-3"></i>
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">Sin Infraestructura</p>
                    <button onclick="openBranchModal()" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-xl text-[10px] font-black uppercase">Crear Primera Sede</button>
                </div>
            @endforelse
        </div>
    </div>
@endif

{{-- ================================================================= --}}
{{-- MODALES DE GESTIÓN (Sincronizados)                                --}}
{{-- ================================================================= --}}

<!-- Modal Sucursal -->
<div id="branchModal" class="hidden fixed inset-0 bg-slate-900/60 z-[100] flex items-center justify-center backdrop-blur-md p-4">
    <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 id="branchModalTitle" class="font-black text-slate-800 text-base uppercase tracking-tighter">Nueva Sucursal</h3>
            <button onclick="closeModal('branchModal')" class="text-slate-400 hover:text-rose-500"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form id="branchForm" action="{{ route('admin.branches.store') }}" method="POST" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="_method" id="branchMethod" value="POST">
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Nombre Comercial *</label>
                    <input type="text" name="name" id="branchName" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:border-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Código Sede *</label>
                    <input type="text" name="code" id="branchCode" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono uppercase focus:border-blue-500 outline-none">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">País *</label>
                    <select name="country" id="branchCountry" onchange="loadStates(this.options[this.selectedIndex].dataset.id)" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        <option value="">Seleccionar...</option>
                        @isset($countries)
                            @foreach($countries as $country)
                                <option value="{{ $country->name }}" data-id="{{ $country->id }}">{{ $country->name }}</option>
                            @endforeach
                        @endisset
                    </select>
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Estado *</label>
                    <select name="state" id="branchState" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                        <option value="">Cargando...</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Ciudad *</label>
                    <input type="text" name="city" id="branchCity" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Código Postal</label>
                    <input type="text" name="zip" id="branchZip" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                </div>
            </div>

            <div>
                <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Dirección Física</label>
                <textarea name="address" id="branchAddress" rows="2" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-none focus:border-blue-500 font-medium"></textarea>
            </div>

            <button type="submit" id="branchSubmitBtn" class="w-full bg-blue-600 text-white py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-blue-500/40">Guardar Sucursal</button>
        </form>
    </div>
</div>

<!-- Modal Bodega -->
<div id="warehouseModal" class="hidden fixed inset-0 bg-slate-900/60 z-[100] flex items-center justify-center backdrop-blur-md p-4">
    <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <h3 id="whModalTitle" class="font-black text-slate-800 text-base uppercase tracking-tighter">Nueva Bodega</h3>
            <button onclick="closeModal('warehouseModal')" class="text-slate-400 hover:text-rose-500"><i class="fa-solid fa-xmark text-xl"></i></button>
        </div>
        <form id="whForm" action="{{ route('admin.warehouses.store') }}" method="POST" class="p-6 space-y-5">
            @csrf
            <input type="hidden" name="_method" id="whMethod" value="POST">
            <input type="hidden" name="branch_id" id="modal_branch_id">
            
            <div class="grid grid-cols-3 gap-3">
                <div class="col-span-2">
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Nombre *</label>
                    <input type="text" name="name" id="whName" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold">
                </div>
                <div>
                    <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Código *</label>
                    <input type="text" name="code" id="whCode" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs font-mono uppercase text-center">
                </div>
            </div>

            <div class="p-4 bg-slate-50 rounded-2xl border border-slate-100">
                <p class="text-[8px] font-black text-indigo-500 uppercase tracking-widest text-center mb-3">Dimensiones Planta</p>
                <div class="grid grid-cols-3 gap-4">
                    <div>
                        <label class="block text-[8px] font-black text-slate-400 text-center mb-1">PASILLOS</label>
                        <input type="number" name="rows" id="whRows" value="5" min="1" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs text-center font-black">
                    </div>
                    <div>
                        <label class="block text-[8px] font-black text-slate-400 text-center mb-1">RACKS/L</label>
                        <input type="number" name="cols" id="whCols" value="10" min="1" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs text-center font-black">
                    </div>
                    <div>
                        <label class="block text-[8px] font-black text-slate-400 text-center mb-1">NIVELES</label>
                        <input type="number" name="levels" id="whLevels" value="1" min="1" required class="w-full px-3 py-2 border border-slate-200 rounded-lg text-xs text-center font-black">
                    </div>
                </div>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-indigo-500/40">Guardar Bodega</button>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    /**
     * AJAX: Carga de estados dinámica
     */
    async function loadStates(countryId, selectedStateName = null) {
        const stateSelect = document.getElementById('branchState');
        if (!countryId) {
            stateSelect.innerHTML = '<option value="">Elegir País...</option>';
            return;
        }

        stateSelect.innerHTML = '<option value="">Cargando...</option>';
        
        try {
            const response = await fetch(`/portal/states/${countryId}`);
            const states = await response.json();
            
            stateSelect.innerHTML = '<option value="">Seleccionar Estado...</option>';
            states.forEach(state => {
                const option = document.createElement('option');
                option.value = state.name;
                option.textContent = state.name;
                if (selectedStateName && state.name === selectedStateName) option.selected = true;
                stateSelect.appendChild(option);
            });
        } catch (error) {
            stateSelect.innerHTML = '<option value="">Error al cargar</option>';
        }
    }

    /**
     * Gestión de Sucursales
     */
    function openBranchModal() {
        const form = document.getElementById('branchForm');
        form.reset();
        form.action = "{{ route('admin.branches.store') }}";
        document.getElementById('branchMethod').value = "POST";
        document.getElementById('branchModalTitle').innerText = "Nueva Sucursal";
        document.getElementById('branchModal').classList.remove('hidden');
        document.getElementById('branchState').innerHTML = '<option value="">Seleccione país primero...</option>';
    }

    function editBranch(branch) {
        document.getElementById('branchName').value = branch.name;
        document.getElementById('branchCode').value = branch.code;
        document.getElementById('branchCity').value = branch.city;
        document.getElementById('branchAddress').value = branch.address;
        document.getElementById('branchZip').value = branch.zip || '';
        
        const countrySelect = document.getElementById('branchCountry');
        countrySelect.value = branch.country;
        
        // CORRECCIÓN: Detectar ID y disparar AJAX para edición
        const countryOption = Array.from(countrySelect.options).find(o => o.value === branch.country);
        if (countryOption) {
            loadStates(countryOption.dataset.id, branch.state);
        }

        document.getElementById('branchForm').action = `/admin/branches/${branch.id}`;
        document.getElementById('branchMethod').value = "PUT";
        document.getElementById('branchModalTitle').innerText = "Editar Sucursal";
        document.getElementById('branchModal').classList.remove('hidden');
    }

    /**
     * Gestión de Bodegas
     */
    function openWarehouseModal(branchId) {
        const form = document.getElementById('whForm');
        form.reset();
        document.getElementById('modal_branch_id').value = branchId;
        document.getElementById('whModalTitle').innerText = "Añadir Bodega";
        document.getElementById('whMethod').value = "POST";
        document.getElementById('whForm').action = "{{ route('admin.warehouses.store') }}";
        document.getElementById('warehouseModal').classList.remove('hidden');
    }

    function editWarehouse(wh) {
        document.getElementById('whName').value = wh.name;
        document.getElementById('whCode').value = wh.code;
        document.getElementById('whRows').value = wh.rows;
        document.getElementById('whCols').value = wh.cols;
        document.getElementById('whLevels').value = wh.levels || 1;
        document.getElementById('modal_branch_id').value = wh.branch_id;
        document.getElementById('whForm').action = `/admin/warehouses/${wh.id}`;
        document.getElementById('whMethod').value = "PUT";
        document.getElementById('whModalTitle').innerText = "Editar Bodega";
        document.getElementById('warehouseModal').classList.remove('hidden');
    }

    /**
     * Lógica de Mapa
     */
    @if(request('view') == 'map')
    function loadMap(id, name, code, aisles, racksPerSide, levels) {
        document.getElementById('empty-map-state').classList.add('hidden');
        document.getElementById('map-header').classList.remove('hidden');
        document.getElementById('map-container').classList.remove('hidden');
        document.getElementById('current-warehouse-name').innerText = name;
        document.getElementById('current-warehouse-details').innerText = `${code} | ${aisles} PASILLOS | ${levels} NIVELES`;

        const printBtn = document.getElementById('printWhLabelsBtn');
        printBtn.href = `/admin/warehouses/${id}/labels`;
        printBtn.classList.remove('hidden');

        const layout = document.getElementById('warehouse-layout');
        layout.innerHTML = ''; 

        for (let p = 1; p <= aisles; p++) {
            const aisleContainer = document.createElement('div');
            aisleContainer.className = "flex flex-col bg-white p-6 rounded-[2.5rem] shadow-lg border border-slate-100 ring-4 ring-slate-50";
            aisleContainer.innerHTML = `<div class="text-[9px] font-black text-slate-400 mb-4 uppercase tracking-[0.3em] text-center border-b pb-2 italic">Pasillo P-${p.toString().padStart(2,'0')}</div>`;

            const rowA = document.createElement('div');
            rowA.className = "flex gap-1.5 justify-center mb-1.5";
            for (let c = 1; c <= racksPerSide; c++) rowA.appendChild(createRackCell(p, 'A', c));
            aisleContainer.appendChild(rowA);

            const path = document.createElement('div');
            path.className = "h-10 bg-slate-100/50 border-y border-dashed border-slate-200 rounded-xl flex items-center justify-center mb-1.5 relative overflow-hidden";
            path.innerHTML = '<div class="flex gap-10 opacity-5"><i class="fa-solid fa-arrow-right"></i><i class="fa-solid fa-arrow-right"></i></div>';
            aisleContainer.appendChild(path);

            const rowB = document.createElement('div');
            rowB.className = "flex gap-1.5 justify-center";
            for (let c = 1; c <= racksPerSide; c++) rowB.appendChild(createRackCell(p, 'B', c));
            aisleContainer.appendChild(rowB);

            layout.appendChild(aisleContainer);
        }
    }

    function createRackCell(aisle, side, col) {
        const cell = document.createElement('div');
        const colorClass = side === 'A' ? 'bg-blue-600 border-blue-700' : 'bg-indigo-600 border-indigo-700';
        cell.className = `w-10 h-10 rounded-xl border ${colorClass} text-white flex items-center justify-center cursor-pointer transition-all hover:scale-125 shadow-md font-black font-mono text-[8px]`;
        cell.innerHTML = `<span>${side}${col}</span>`;
        return cell;
    }
    @endif
</script>
@endsection