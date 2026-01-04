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
    <div class="flex flex-col h-[calc(100vh-140px)] space-y-4">
        {{-- Barra Superior del Mapa --}}
        <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4 shrink-0">
            <div>
                <h2 class="font-black text-slate-700 flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-map text-indigo-600"></i> Planta Física y Almacenamiento
                </h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest pl-6">Navegación técnica de bines</p>
            </div>
            <div class="flex gap-2">
                <a id="printWhLabelsBtn" href="#" target="_blank" class="hidden text-[10px] font-black uppercase tracking-widest text-slate-600 bg-white border border-slate-300 px-3 py-2 rounded-lg hover:bg-slate-50 transition flex items-center gap-2">
                    <i class="fa-solid fa-print"></i> Rotular
                </a>
                <a href="{{ route('admin.branches.index') }}" class="text-[10px] font-black uppercase tracking-widest text-white bg-slate-800 px-4 py-2 rounded-lg hover:bg-slate-700 transition flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        {{-- LAYOUT PRINCIPAL DEL MAPA (GRID 12 COLUMNAS) --}}
        <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-4 min-h-0 overflow-hidden">
            
            <!-- COLUMNA 1: Navegador Lateral (Lista de Bodegas) [3 COLS] -->
            <div class="lg:col-span-3 flex flex-col gap-3 overflow-y-auto pr-1 custom-scrollbar bg-white rounded-xl shadow-sm border border-slate-200 h-full">
                <div class="p-3 bg-slate-50 border-b border-slate-100 sticky top-0 z-10">
                    <h3 class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Sucursales Disponibles</h3>
                </div>
                
                <div class="p-2 space-y-4">
                    @foreach($branches as $branch)
                        <div class="rounded-lg overflow-hidden">
                            <div class="px-2 py-1.5 font-bold text-slate-700 text-xs flex items-center gap-2 mb-1">
                                <i class="fa-solid fa-building text-indigo-500"></i> {{ $branch->name }}
                            </div>
                            <div class="space-y-1 ml-2 border-l-2 border-slate-100 pl-2">
                                @foreach($branch->warehouses as $warehouse)
                                    <button onclick="loadMap('{{ $warehouse->id }}', '{{ $warehouse->name }}', '{{ $warehouse->code }}', {{ $warehouse->rows }}, {{ $warehouse->cols }}, {{ $warehouse->levels ?? 1 }})" 
                                            class="w-full text-left p-2 rounded-lg hover:bg-indigo-50 transition flex justify-between items-center group focus:bg-indigo-50 outline-none border border-transparent focus:border-indigo-200">
                                            <div class="flex items-center gap-2">
                                                <i class="fa-solid fa-warehouse text-slate-300 group-hover:text-indigo-500 text-xs"></i>
                                                <div>
                                                    <span class="text-[11px] font-bold text-slate-600 group-hover:text-indigo-700 block leading-tight">{{ $warehouse->name }}</span>
                                                    <span class="text-[9px] text-slate-400 font-mono">{{ $warehouse->code }}</span>
                                                </div>
                                            </div>
                                            <i class="fa-solid fa-chevron-right text-[9px] text-slate-300 group-hover:text-indigo-400 opacity-0 group-hover:opacity-100"></i>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- COLUMNA 2: Canvas del Mapa (Centro) [6 COLS] -->
            <div class="lg:col-span-6 bg-slate-50 rounded-xl shadow-inner border border-slate-200 flex flex-col relative overflow-hidden group">
                <!-- Estado Vacío -->
                <div id="empty-map-state" class="absolute inset-0 flex flex-col items-center justify-center bg-white z-10">
                    <div class="bg-indigo-50 p-6 rounded-full mb-4 animate-pulse">
                        <i class="fa-solid fa-map-location-dot text-4xl text-indigo-300"></i>
                    </div>
                    <h3 class="font-black text-slate-700 text-sm uppercase tracking-wide">Seleccione una Bodega</h3>
                    <p class="text-[10px] text-slate-400 mt-1">Navegue por el menú izquierdo para visualizar</p>
                </div>

                <!-- Cabecera del Mapa Activo -->
                <div id="map-header" class="hidden absolute top-0 left-0 right-0 p-3 bg-white/90 backdrop-blur-sm border-b border-slate-200 flex justify-between items-center z-20 shadow-sm">
                    <div>
                        <h3 id="current-warehouse-name" class="font-black text-slate-700 text-sm">Mapa</h3>
                        <p id="current-warehouse-details" class="text-[9px] text-slate-400 font-mono font-bold">---</p>
                    </div>
                    <div class="flex gap-3 text-[9px] font-black text-slate-500 uppercase tracking-widest bg-slate-100 px-3 py-1.5 rounded-full">
                        <span class="flex items-center gap-1.5"><div class="w-2 h-2 bg-blue-500 rounded-full"></div> Lado A</span>
                        <span class="flex items-center gap-1.5"><div class="w-2 h-2 bg-indigo-500 rounded-full"></div> Lado B</span>
                    </div>
                </div>

                <!-- Contenedor del Grid (Zoomable) -->
                <div id="map-container" class="hidden flex-1 overflow-auto p-8 custom-scrollbar relative bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:20px_20px]">
                    <div id="warehouse-layout" class="flex flex-col gap-6 mx-auto w-fit p-4 pb-40 transition-transform origin-top-left duration-200">
                        <!-- El mapa se genera aquí vía JS -->
                    </div>
                </div>
                
                <!-- Loader -->
                <div id="map-loader" class="hidden absolute inset-0 bg-white/80 z-50 flex items-center justify-center backdrop-blur-sm">
                    <div class="flex flex-col items-center">
                        <i class="fa-solid fa-circle-notch fa-spin text-indigo-500 text-2xl mb-2"></i>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Cargando Estructura...</span>
                    </div>
                </div>
            </div>

            <!-- COLUMNA 3: Panel de Configuración de Rack (Derecha) [3 COLS] -->
            <div id="rack-config-panel" class="hidden lg:col-span-3 bg-white rounded-xl shadow-xl border border-slate-200 flex flex-col overflow-hidden relative h-full animate-in slide-in-from-right duration-300">
                <!-- Estado Vacío Panel -->
                <div id="empty-panel-state" class="absolute inset-0 flex flex-col items-center justify-center bg-white z-10 p-6 text-center">
                    <i class="fa-solid fa-arrow-pointer text-2xl text-slate-200 mb-3"></i>
                    <p class="text-[11px] font-bold text-slate-400 uppercase leading-relaxed">Seleccione un rack en el mapa para ver sus detalles</p>
                </div>

                <!-- Contenido del Panel -->
                <div id="panel-content" class="flex flex-col h-full hidden">
                    <div class="p-4 bg-slate-800 text-white flex justify-between items-start shrink-0">
                        <div>
                            <h3 class="font-black text-xs uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-sliders text-indigo-400"></i> Configuración
                            </h3>
                            <p id="panel-rack-id" class="text-[10px] text-slate-400 font-mono mt-1">RACK SELECCIONADO</p>
                        </div>
                        <button onclick="closeRackConfig()" class="text-slate-400 hover:text-white transition bg-slate-700/50 p-1 rounded hover:bg-slate-700">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </button>
                    </div>

                    <div class="flex-1 overflow-y-auto custom-scrollbar p-4 space-y-5">
                        
                        {{-- Visor de Nomenclatura --}}
                        <div class="bg-indigo-50 p-3 rounded-xl border border-indigo-100">
                            <div class="flex justify-between items-center mb-1">
                                <label class="text-[9px] font-black text-indigo-400 uppercase tracking-widest">Vista Previa (Nomenclatura)</label>
                                <span class="text-[9px] font-bold text-indigo-600 bg-white px-1.5 rounded border border-indigo-100">Ej: Nivel 1</span>
                            </div>
                            <div id="nomenclature-preview" class="font-mono text-xs font-black text-indigo-700 bg-white px-3 py-2 rounded-lg border border-indigo-200 text-center shadow-sm tracking-wide">
                                ---
                            </div>
                            <p class="text-[8px] text-indigo-400 mt-1.5 text-center leading-tight">
                                Bodega • Pasillo • Lado • Rack • Nivel • Bin
                            </p>
                        </div>

                        {{-- Formulario Dinámico --}}
                        <form id="rack-config-form" onsubmit="saveRackConfig(event)">
                            <div class="flex justify-between items-center mb-3">
                                <h4 class="text-[10px] font-black text-slate-600 uppercase tracking-widest border-l-2 border-indigo-500 pl-2">Estructura Vertical</h4>
                                <span id="total-positions-badge" class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded text-[9px] font-bold border border-slate-200">0 Posiciones</span>
                            </div>

                            <div id="levels-container" class="space-y-3 min-h-[50px]">
                                <!-- Los niveles se inyectan aquí con JS -->
                            </div>

                            <div class="mt-4 flex gap-2">
                                <button type="button" onclick="addLevel()" class="flex-1 py-2.5 border border-dashed border-slate-300 text-slate-500 rounded-xl text-[10px] font-black uppercase hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-300 transition-all flex items-center justify-center gap-2">
                                    <i class="fa-solid fa-plus bg-slate-200 text-slate-500 rounded-full p-0.5 w-4 h-4 flex items-center justify-center text-[8px]"></i>
                                    Agregar Nivel
                                </button>
                                <button type="button" onclick="removeLevel()" class="w-10 py-2.5 border border-dashed border-slate-300 text-slate-400 rounded-xl hover:bg-rose-50 hover:text-rose-500 hover:border-rose-200 transition flex items-center justify-center" title="Quitar último nivel">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                            </div>
                        </form>
                    </div>

                    <div class="p-4 border-t border-slate-100 bg-slate-50 shrink-0">
                        <!-- FIX: ID Agregado para selección JS -->
                        <button id="btn-save-rack" onclick="document.getElementById('rack-config-form').requestSubmit()" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-indigo-500/30 hover:bg-indigo-700 hover:scale-[1.02] transition-all flex items-center justify-center gap-2">
                            <i class="fa-solid fa-save"></i> Guardar Estructura
                        </button>
                    </div>
                </div>
                
                {{-- Loader del Panel --}}
                <div id="rack-loader" class="hidden absolute inset-0 bg-white/80 z-20 flex items-center justify-center backdrop-blur-sm">
                    <div class="flex flex-col items-center">
                        <i class="fa-solid fa-circle-notch fa-spin text-indigo-500 text-2xl mb-2"></i>
                        <span class="text-[9px] font-bold text-slate-400 uppercase">Cargando Rack...</span>
                    </div>
                </div>
            </div>

        </div>
    </div>

@else
    {{-- ================================================================= --}}
    {{-- MODO GESTIÓN: Listado de Sucursales (Mantenido Igual)             --}}
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
    // Variables Globales
    let currentWhId = null;
    let currentWhCode = null;
    let currentWhDefaultLevelsCount = 1; // FIX: Variable para almacenar la configuración de la bodega
    let currentAisle = null;
    let currentSide = null;
    let currentRack = null;
    let currentLevels = [];
    const binTypes = @json($binTypes ?? []);

    // Funciones Modales (Gestión)
    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

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

    function openBranchModal() {
        const form = document.getElementById('branchForm');
        form.reset();
        form.action = "{{ route('admin.branches.store') }}";
        document.getElementById('branchMethod').value = 'POST';
        document.getElementById('branchModalTitle').innerText = 'Nueva Sucursal';
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
        
        const countryOption = Array.from(countrySelect.options).find(o => o.value === branch.country);
        if (countryOption) {
            loadStates(countryOption.dataset.id, branch.state);
        }

        document.getElementById('branchForm').action = `/admin/branches/${branch.id}`;
        document.getElementById('branchMethod').value = 'PUT';
        document.getElementById('branchModalTitle').innerText = 'Editar Sucursal';
        document.getElementById('branchModal').classList.remove('hidden');
    }

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
     * =========================================================
     * LÓGICA DEL MAPA Y CONFIGURACIÓN DE RACKS
     * =========================================================
     */
    @if(request('view') == 'map')

    function loadMap(id, name, code, aisles, racksPerSide, levels) {
        currentWhId = id;
        currentWhCode = code;
        currentWhDefaultLevelsCount = levels; // FIX: Guardamos los niveles por defecto de la bodega

        // UI Updates
        document.getElementById('empty-map-state').classList.add('hidden');
        document.getElementById('map-header').classList.remove('hidden');
        document.getElementById('map-container').classList.remove('hidden');
        
        // Reset Right Panel (Mostrar panel vacío)
        document.getElementById('rack-config-panel').classList.remove('hidden');
        document.getElementById('empty-panel-state').classList.remove('hidden');
        document.getElementById('panel-content').classList.add('hidden');

        // Header Info
        document.getElementById('current-warehouse-name').innerText = name;
        document.getElementById('current-warehouse-details').innerText = `${code} | ${aisles} PASILLOS | ${racksPerSide} RACKS/LADO | ${levels} NIVELES/RACK`;

        // Update Labels Button
        const printBtn = document.getElementById('printWhLabelsBtn');
        printBtn.href = `/admin/warehouses/${id}/labels`;
        printBtn.classList.remove('hidden');

        // Generate Grid
        const layout = document.getElementById('warehouse-layout');
        layout.innerHTML = ''; 

        for (let p = 1; p <= aisles; p++) {
            const aisleContainer = document.createElement('div');
            aisleContainer.className = "flex flex-col bg-white p-4 rounded-[2rem] shadow-md border border-slate-100 ring-2 ring-slate-50 transition-all hover:shadow-lg";
            
            // Header Pasillo
            aisleContainer.innerHTML = `<div class="text-[9px] font-black text-slate-400 mb-3 uppercase tracking-[0.2em] text-center border-b border-slate-100 pb-1">Pasillo P-${p.toString().padStart(2,'0')}</div>`;

            // Lado A
            const rowA = document.createElement('div');
            rowA.className = "flex gap-1.5 justify-center mb-2";
            for (let c = 1; c <= racksPerSide; c++) rowA.appendChild(createRackCell(p, 'A', c));
            aisleContainer.appendChild(rowA);

            // Camino Central (Visual)
            const path = document.createElement('div');
            path.className = "h-8 bg-slate-50 border-y border-dashed border-slate-200 rounded-lg flex items-center justify-center mb-2 overflow-hidden";
            path.innerHTML = '<div class="flex gap-8 opacity-10"><i class="fa-solid fa-chevron-right text-xs"></i><i class="fa-solid fa-chevron-right text-xs"></i></div>';
            aisleContainer.appendChild(path);

            // Lado B
            const rowB = document.createElement('div');
            rowB.className = "flex gap-1.5 justify-center";
            for (let c = 1; c <= racksPerSide; c++) rowB.appendChild(createRackCell(p, 'B', c));
            aisleContainer.appendChild(rowB);

            layout.appendChild(aisleContainer);
        }
    }

    function createRackCell(aisle, side, col) {
        const cell = document.createElement('div');
        const colorClass = side === 'A' ? 'bg-blue-500 border-blue-600 shadow-blue-200' : 'bg-indigo-500 border-indigo-600 shadow-indigo-200';
        
        cell.className = `w-8 h-8 rounded-lg border ${colorClass} text-white flex items-center justify-center cursor-pointer transition-all hover:scale-110 hover:z-10 shadow-sm font-black font-mono text-[9px] active:scale-95`;
        cell.innerHTML = `<span>${col}</span>`;
        cell.title = `Pasillo ${aisle} - Lado ${side} - Rack ${col}`;
        
        cell.onclick = () => openRackConfig(aisle, side, col);
        
        return cell;
    }

    async function openRackConfig(aisle, side, col) {
        currentAisle = aisle;
        currentSide = side;
        currentRack = col;

        // UI Setup
        document.getElementById('empty-panel-state').classList.add('hidden');
        document.getElementById('panel-content').classList.remove('hidden');
        document.getElementById('rack-loader').classList.remove('hidden');
        
        document.getElementById('panel-rack-id').innerText = `P${aisle.toString().padStart(2,'0')} - LADO ${side} - RACK ${col.toString().padStart(2,'0')}`;
        
        currentLevels = [];
        renderLevelsForm();

        try {
            // Intenta cargar la configuración existente del rack desde la DB
             const res = await fetch(`/admin/warehouses/rack-details?warehouse_id=${currentWhId}&aisle=${aisle}&side=${side}&rack_col=${col}`);
             if(res.ok) {
                 const data = await res.json();
                 
                 // FIX LÓGICA DB VS DEFAULT:
                 // Si la DB retorna niveles configurados, úsalos.
                 if(data.levels && data.levels.length > 0) {
                     currentLevels = data.levels.map(l => ({ bins: l.bins_count, type: l.bin_type_id }));
                 } else {
                    // Si NO hay configuración en DB (es nuevo), generar estructura basada en la configuración global de la Bodega
                    currentLevels = [];
                    for(let i=0; i < currentWhDefaultLevelsCount; i++) {
                        // Por defecto 3 bines, o 1. Usaremos 3 como base estándar editable.
                        currentLevels.push({ bins: 3, type: null }); 
                    }
                 }
             } else {
                 // Error de red o 404, usar default de la bodega
                 currentLevels = [];
                 for(let i=0; i < currentWhDefaultLevelsCount; i++) {
                     currentLevels.push({ bins: 3, type: null }); 
                 }
             }

            renderLevelsForm();
            
        } catch (error) {
            console.error('Error loading rack details:', error);
            // Fallback en error: Usar configuración de bodega
            currentLevels = [];
            for(let i=0; i < currentWhDefaultLevelsCount; i++) {
                currentLevels.push({ bins: 3, type: null }); 
            }
            renderLevelsForm();
        } finally {
            setTimeout(() => {
                document.getElementById('rack-loader').classList.add('hidden');
            }, 300);
        }
    }

    function closeRackConfig() {
        document.getElementById('empty-panel-state').classList.remove('hidden');
        document.getElementById('panel-content').classList.add('hidden');
    }

    function renderLevelsForm() {
        const container = document.getElementById('levels-container');
        container.innerHTML = '';
        let totalPositions = 0;
        
        currentLevels.forEach((level, index) => {
            totalPositions += parseInt(level.bins);
            const levelNum = index + 1;
            
            const div = document.createElement('div');
            div.className = "bg-slate-50 border border-slate-200 p-2.5 rounded-lg animate-in fade-in slide-in-from-left-2 duration-200";
            
            // Opciones de Tipos de Bin
            let options = `<option value="">Estándar</option>`;
            binTypes.forEach(bt => {
                options += `<option value="${bt.id}" ${level.type == bt.id ? 'selected' : ''}>${bt.name} (${bt.width}x${bt.height}x${bt.depth})</option>`;
            });

            div.innerHTML = `
                <div class="flex justify-between items-center mb-1.5">
                    <span class="text-[9px] font-black text-slate-500 uppercase flex items-center gap-1">
                        <div class="w-1.5 h-1.5 rounded-full bg-indigo-400"></div> Nivel ${levelNum}
                    </span>
                </div>
                <div class="grid grid-cols-5 gap-2">
                    <div class="col-span-2">
                        <label class="block text-[8px] text-slate-400 font-bold mb-0.5">BINES</label>
                        <input type="number" min="1" max="20" value="${level.bins}" 
                            onchange="updateLevelData(${index}, 'bins', this.value)"
                            class="w-full px-2 py-1 bg-white border border-slate-200 rounded text-[10px] font-bold text-center focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500 outline-none">
                    </div>
                    <div class="col-span-3">
                        <label class="block text-[8px] text-slate-400 font-bold mb-0.5">TAMAÑO</label>
                        <select onchange="updateLevelData(${index}, 'type', this.value)"
                            class="w-full px-2 py-1 bg-white border border-slate-200 rounded text-[10px] font-bold focus:border-indigo-500 outline-none">
                            ${options}
                        </select>
                    </div>
                </div>
            `;
            container.appendChild(div);
        });

        document.getElementById('total-positions-badge').innerText = `${totalPositions} Posiciones`;
        updateNomenclaturePreview();
    }

    function addLevel() {
        const lastLevel = currentLevels[currentLevels.length - 1];
        currentLevels.push({ bins: lastLevel ? lastLevel.bins : 3, type: lastLevel ? lastLevel.type : null });
        renderLevelsForm();
    }

    function removeLevel() {
        if(currentLevels.length > 0) {
            currentLevels.pop();
            renderLevelsForm();
        }
    }

    function updateLevelData(index, field, value) {
        currentLevels[index][field] = value;
        if(field === 'bins') renderLevelsForm(); // Re-renderizar para actualizar total badge
    }

    function updateNomenclaturePreview() {
        if (!currentWhCode) return;
        
        const p = currentAisle.toString().padStart(2,'0');
        const s = currentSide;
        const r = currentRack.toString().padStart(2,'0');
        
        // Ejemplo para Nivel 1, Bin 1
        const n = "01";
        const b = "01";
        
        const preview = `${currentWhCode}-${p}-${s}-${r}-${n}-${b}`;
        document.getElementById('nomenclature-preview').innerText = preview;
    }

    async function saveRackConfig(e) {
        e.preventDefault();
        
        // FIX: Usar getElementById porque el botón está fuera del form y e.submitter puede fallar
        const btn = document.getElementById('btn-save-rack');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Guardando...';
        btn.disabled = true;

        try {
            const payload = {
                warehouse_id: currentWhId,
                aisle: currentAisle,
                side: currentSide,
                rack_col: currentRack,
                levels: currentLevels.map((lvl, idx) => ({
                    level: idx + 1,
                    bins_count: parseInt(lvl.bins),
                    bin_type_id: lvl.type
                }))
            };

            const response = await fetch("{{ route('admin.warehouses.save_rack') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: JSON.stringify(payload)
            });

            if (!response.ok) throw new Error('Error en la respuesta del servidor');

            const result = await response.json();
            
            const successDiv = document.createElement('div');
            successDiv.className = "fixed bottom-4 right-4 bg-emerald-600 text-white px-4 py-2 rounded-lg shadow-lg text-xs font-bold animate-in slide-in-from-bottom-5 z-50";
            successDiv.innerHTML = '<i class="fa-solid fa-check mr-2"></i> Configuración Guardada';
            document.body.appendChild(successDiv);
            setTimeout(() => successDiv.remove(), 3000);

        } catch (error) {
            console.error(error);
            alert('Error al guardar la configuración del rack');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }
    @endif
</script>
@endsection