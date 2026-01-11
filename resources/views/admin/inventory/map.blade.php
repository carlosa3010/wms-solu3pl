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

@if($errors->any())
<div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 mb-4 rounded-xl shadow-sm">
    <ul class="list-disc pl-5 text-xs">
        @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

@if(request('view') == 'map')
    {{-- ================================================================= --}}
    {{-- MODO MAPA INTERACTIVO: Visualización de Estructura Física         --}}
    {{-- ================================================================= --}}
    
    <div class="flex flex-col h-[calc(100vh-140px)] space-y-4" x-data="warehouseMap">
        
        {{-- Barra Superior del Mapa --}}
        <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-200 flex flex-col md:flex-row justify-between items-center gap-4 shrink-0">
            <div>
                <h2 class="font-black text-slate-700 flex items-center gap-2 text-sm">
                    <i class="fa-solid fa-map text-indigo-600"></i> Planta Física y Almacenamiento
                </h2>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest pl-6">Navegación técnica de bines</p>
            </div>
            
            {{-- Selector de Bodega --}}
            <div class="flex-grow max-w-md mx-4">
                <select class="form-select w-full text-xs font-bold border-slate-200 rounded-lg focus:ring-indigo-500" x-model="selectedWarehouseId" @change="loadMapData()">
                    <option value="">-- Seleccionar Bodega --</option>
                    @foreach($branches as $branch)
                        <optgroup label="{{ $branch->name }}">
                            @foreach($branch->warehouses as $wh)
                                <option value="{{ $wh->id }}" 
                                    data-rows="{{ $wh->rows }}" 
                                    data-cols="{{ $wh->cols }}" 
                                    data-levels="{{ $wh->levels }}"
                                    {{ isset($selectedWarehouse) && $selectedWarehouse->id == $wh->id ? 'selected' : '' }}>
                                    {{ $wh->name }} ({{ $wh->code }})
                                </option>
                            @endforeach
                        </optgroup>
                    @endforeach
                </select>
            </div>

            <div class="flex gap-2">
                {{-- Botón Imprimir Etiquetas --}}
                <a x-bind:href="'/admin/warehouses/' + selectedWarehouseId + '/labels'" 
                   x-show="selectedWarehouseId"
                   target="_blank" 
                   class="text-[10px] font-black uppercase tracking-widest text-slate-600 bg-white border border-slate-300 px-3 py-2 rounded-lg hover:bg-slate-50 transition flex items-center gap-2">
                    <i class="fa-solid fa-print"></i> Etiquetas
                </a>
                
                {{-- Botón Generar Estructura (Automático) --}}
                <button @click="openGeneratorModal()" 
                        :disabled="!selectedWarehouseId" 
                        class="text-[10px] font-black uppercase tracking-widest text-white bg-indigo-600 px-4 py-2 rounded-lg hover:bg-indigo-700 transition flex items-center gap-2 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fa-solid fa-wand-magic-sparkles"></i> Generar Estructura
                </button>

                <a href="{{ route('admin.branches.index') }}" class="text-[10px] font-black uppercase tracking-widest text-white bg-slate-800 px-4 py-2 rounded-lg hover:bg-slate-700 transition flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-arrow-left"></i> Volver
                </a>
            </div>
        </div>

        {{-- Contenedor Principal (Grid Mapa + Panel Lateral) --}}
        <div class="flex-1 grid grid-cols-1 lg:grid-cols-12 gap-4 min-h-0 overflow-hidden">
            
            {{-- Canvas del Mapa (Izquierda/Centro) --}}
            <div class="lg:col-span-9 bg-slate-50 rounded-xl shadow-inner border border-slate-200 relative overflow-hidden group">
                
                {{-- Loading State --}}
                <div x-show="loading" class="absolute inset-0 bg-white/80 z-50 flex items-center justify-center backdrop-blur-sm" style="display: none;">
                    <div class="flex flex-col items-center">
                        <i class="fa-solid fa-circle-notch fa-spin text-indigo-500 text-2xl mb-2"></i>
                        <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">Cargando Estructura...</span>
                    </div>
                </div>

                {{-- Empty State --}}
                <div x-show="!loading && !selectedWarehouseId" class="absolute inset-0 flex flex-col items-center justify-center bg-white z-10">
                    <div class="bg-indigo-50 p-6 rounded-full mb-4 animate-pulse">
                        <i class="fa-solid fa-warehouse text-4xl text-indigo-300"></i>
                    </div>
                    <h3 class="font-black text-slate-700 text-sm uppercase tracking-wide">Seleccione una Bodega</h3>
                    <p class="text-[10px] text-slate-400 mt-1">Utilice el selector superior para visualizar el mapa</p>
                </div>

                {{-- Mapa Visual --}}
                <div x-show="!loading && mapData" class="absolute inset-0 overflow-auto p-8 custom-scrollbar bg-[radial-gradient(#cbd5e1_1px,transparent_1px)] [background-size:20px_20px]" style="display: none;">
                    <div class="flex flex-col gap-10 mx-auto w-full max-w-5xl p-4 pb-40">
                        
                        {{-- Iteramos sobre Pasillos --}}
                        <template x-for="(sides, aisleKey) in mapData" :key="aisleKey">
                            <div class="flex flex-col bg-white p-6 rounded-[2rem] shadow-md border border-slate-100 ring-4 ring-slate-50 transition-all hover:shadow-lg w-full">
                                
                                {{-- ENCABEZADO DEL PASILLO (NUEVO) --}}
                                <div class="flex items-center justify-between border-b border-slate-100 pb-3 mb-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center font-black text-lg shadow-sm border border-indigo-100">
                                            <span x-text="aisleKey"></span>
                                        </div>
                                        <div>
                                            <h4 class="font-black text-slate-700 text-sm uppercase tracking-wider">Pasillo <span x-text="aisleKey"></span></h4>
                                            <p class="text-[10px] text-slate-400 font-medium">Zona de Racks</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-[9px] font-bold text-slate-300 bg-slate-50 px-2 py-1 rounded-full uppercase tracking-wider">Almacenamiento</span>
                                    </div>
                                </div>

                                {{-- LADO A (ARRIBA) --}}
                                <div class="flex gap-4 items-center">
                                    <div class="w-8 flex flex-col items-center justify-center text-[10px] font-black text-slate-300 border-r border-slate-100 pr-2">
                                        <span>LADO</span>
                                        <span class="text-lg text-blue-500">A</span>
                                    </div>
                                    <div class="flex-1 flex flex-row-reverse gap-3 overflow-x-auto pb-2 custom-scrollbar">
                                        {{-- Usamos getSortedRacks para garantizar orden numérico correcto --}}
                                        <template x-for="rack in getSortedRacks(sides['A'] || sides['Izquierda'])" :key="rack.key">
                                            {{-- Componente Rack Inline --}}
                                            <div class="flex flex-col items-center gap-1 cursor-pointer transition-transform hover:-translate-y-1 hover:z-10 group/rack" 
                                                 @click="openRackEditor(aisleKey, 'A', rack.key)" :title="'Configurar Rack ' + rack.key">
                                                
                                                <div class="w-10 h-10 rounded-xl border-2 bg-blue-500 border-blue-600 shadow-blue-200 text-white flex items-center justify-center shadow-md font-black font-mono text-xs group-hover/rack:bg-blue-600 transition-colors">
                                                    <span x-text="rack.key.replace(/^0+/, '')"></span>
                                                </div>
                                                
                                                <div class="flex flex-col-reverse gap-[2px]">
                                                    <template x-for="level in Object.keys(rack.levels).sort()" :key="level">
                                                        <div class="w-8 h-1.5 bg-slate-200 rounded-full transition-colors" 
                                                             :class="rack.levels[level].some(b => b.has_stock) ? 'bg-emerald-400' : 'group-hover/rack:bg-slate-300'"></div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>

                                {{-- CORREDOR CENTRAL (Horizontal) --}}
                                <div class="h-12 w-full bg-slate-50 border-y-2 border-dashed border-slate-200 my-4 flex items-center justify-between px-6 rounded-lg overflow-hidden relative">
                                    <div class="flex gap-20 w-full justify-center opacity-20">
                                        <template x-for="i in 6">
                                            <i class="fa-solid fa-chevron-right text-slate-400"></i>
                                        </template>
                                    </div>
                                    <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 bg-white px-4 py-1 rounded-full border border-slate-200 shadow-sm z-10">
                                        <span class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em]">
                                            <i class="fa-solid fa-road mr-1 text-slate-300"></i> Pasillo <span x-text="aisleKey" class="text-indigo-600"></span>
                                        </span>
                                    </div>
                                </div>

                                {{-- LADO B (ABAJO) --}}
                                <div class="flex gap-4 items-center">
                                    <div class="w-8 flex flex-col items-center justify-center text-[10px] font-black text-slate-300 border-r border-slate-100 pr-2">
                                        <span>LADO</span>
                                        <span class="text-lg text-indigo-500">B</span>
                                    </div>
                                    <div class="flex-1 flex flex-row-reverse gap-3 overflow-x-auto pt-2 custom-scrollbar">
                                        <template x-for="rack in getSortedRacks(sides['B'] || sides['Derecha'])" :key="rack.key">
                                            {{-- Componente Rack Inline --}}
                                            <div class="flex flex-col-reverse items-center gap-1 cursor-pointer transition-transform hover:translate-y-1 hover:z-10 group/rack"
                                                 @click="openRackEditor(aisleKey, 'B', rack.key)" :title="'Configurar Rack ' + rack.key">
                                                
                                                <div class="w-10 h-10 rounded-xl border-2 bg-indigo-500 border-indigo-600 shadow-indigo-200 text-white flex items-center justify-center shadow-md font-black font-mono text-xs group-hover/rack:bg-indigo-600 transition-colors">
                                                    <span x-text="rack.key.replace(/^0+/, '')"></span>
                                                </div>

                                                <div class="flex flex-col gap-[2px]">
                                                    <template x-for="level in Object.keys(rack.levels).sort()" :key="level">
                                                        <div class="w-8 h-1.5 bg-slate-200 rounded-full transition-colors" 
                                                             :class="rack.levels[level].some(b => b.has_stock) ? 'bg-emerald-400' : 'group-hover/rack:bg-slate-300'"></div>
                                                    </template>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- Panel Lateral de Edición (Derecha) --}}
            <div class="lg:col-span-3 bg-white rounded-xl shadow-xl border border-slate-200 flex flex-col relative h-full overflow-hidden" 
                 x-show="editingRack.active" 
                 x-transition:enter="transition ease-out duration-300 transform" 
                 x-transition:enter-start="translate-x-full" 
                 x-transition:enter-end="translate-x-0" 
                 x-transition:leave="transition ease-in duration-200 transform" 
                 x-transition:leave-start="translate-x-0" 
                 x-transition:leave-end="translate-x-full"
                 style="display: none;">
                
                <div class="p-4 bg-slate-800 text-white flex justify-between items-start shrink-0">
                    <div>
                        <h3 class="font-black text-xs uppercase tracking-wider flex items-center gap-2"><i class="fa-solid fa-sliders text-indigo-400"></i> Configurar Rack</h3>
                        <p class="text-[10px] text-slate-400 font-mono mt-1" x-text="'P'+editingRack.aisle + ' - ' + editingRack.side + ' - R' + editingRack.rack"></p>
                    </div>
                    <button @click="editingRack.active = false" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark"></i></button>
                </div>

                <div class="flex-1 overflow-y-auto p-4 custom-scrollbar space-y-4">
                    <p class="text-[10px] text-slate-500 mb-2">Personaliza los niveles de este rack.</p>
                    <template x-for="(levelConfig, index) in editingRack.levelConfigs" :key="index">
                        <div class="bg-slate-50 border border-slate-200 p-2.5 rounded-lg">
                            <div class="flex justify-between items-center mb-1"><span class="text-[9px] font-black text-slate-500 uppercase">Nivel <span x-text="levelConfig.level"></span></span></div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[8px] text-slate-400 font-bold">Bines</label>
                                    <input type="number" x-model="levelConfig.bins_count" min="1" class="w-full px-2 py-1 bg-white border border-slate-200 rounded text-[10px] font-bold">
                                </div>
                                <div>
                                    <label class="block text-[8px] text-slate-400 font-bold">Tipo</label>
                                    <select x-model="levelConfig.bin_type_id" class="w-full px-2 py-1 bg-white border border-slate-200 rounded text-[10px] font-bold">
                                        @foreach($binTypes as $type) <option value="{{ $type->id }}">{{ $type->name }}</option> @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="p-4 border-t border-slate-100 bg-slate-50">
                    <button @click="saveSingleRack" :disabled="submitting" class="w-full py-3 bg-indigo-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg hover:bg-indigo-700 flex items-center justify-center gap-2">
                        <i x-show="submitting" class="fa-solid fa-circle-notch fa-spin"></i> Guardar Rack
                    </button>
                </div>
            </div>
        </div>

        {{-- Modal Generador Masivo (SIMPLIFICADO) --}}
        <div class="fixed inset-0 bg-slate-900/60 z-[100] flex items-center justify-center backdrop-blur-md p-4" 
             x-show="showGeneratorModal" 
             style="display: none;"
             x-transition.opacity>
            
            <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-sm overflow-hidden">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="font-black text-slate-800 text-base uppercase tracking-tighter">Generación Automática</h3>
                    <button @click="showGeneratorModal = false" class="text-slate-400 hover:text-rose-500"><i class="fa-solid fa-xmark text-xl"></i></button>
                </div>
                
                <div class="p-6">
                    <div class="bg-indigo-50 p-4 rounded-xl border border-indigo-100 mb-5">
                        <p class="text-xs text-indigo-800 font-medium leading-relaxed text-center">
                            Se creará la estructura base para <br>
                            <span class="font-black text-lg block my-1">
                                <span x-text="whConfig.rows"></span> Pasillos × <span x-text="whConfig.cols"></span> Racks
                            </span>
                            con <span x-text="whConfig.levels"></span> niveles de altura.
                        </p>
                    </div>

                    <form @submit.prevent="generateStructure" class="space-y-4">
                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Bines por Nivel (Default)</label>
                            <input type="number" x-model="genForm.bins" min="1" max="10" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:border-indigo-500 outline-none text-center">
                        </div>

                        <div>
                            <label class="block text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1.5">Tipo de Bin (Default)</label>
                            <select x-model="genForm.type" required class="w-full px-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:border-indigo-500 outline-none">
                                <option value="">Seleccione...</option>
                                @foreach($binTypes as $type)
                                    <option value="{{ $type->id }}">{{ $type->name }} ({{ $type->width }}x{{ $type->height }})</option>
                                @endforeach
                            </select>
                        </div>
                        
                        <p class="text-[9px] text-slate-400 mt-2 text-center leading-tight">
                            Esta configuración se aplicará a <strong>TODOS</strong> los niveles inicialmente. <br>
                            Luego podrás personalizar rack por rack en el mapa.
                        </p>

                        <button type="submit" :disabled="submitting" class="w-full bg-indigo-600 text-white py-3 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-indigo-500/40 disabled:opacity-70 flex justify-center items-center gap-2">
                            <i x-show="submitting" class="fa-solid fa-circle-notch fa-spin"></i>
                            <span x-text="submitting ? 'Generando...' : 'Crear Estructura'"></span>
                        </button>
                    </form>
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
            {{-- FIX: Check for empty collection to avoid $__empty_0 error --}}
            @if($branches->isEmpty())
                <div class="col-span-full py-12 text-center bg-white rounded-3xl border-2 border-dashed border-slate-200 flex flex-col items-center">
                    <i class="fa-solid fa-city text-3xl text-slate-200 mb-3"></i>
                    <p class="text-slate-400 text-xs font-bold uppercase tracking-widest">Sin Infraestructura</p>
                    <button onclick="openBranchModal()" class="mt-4 bg-blue-600 text-white px-6 py-2 rounded-xl text-[10px] font-black uppercase">Crear Primera Sede</button>
                </div>
            @else
                @foreach($branches as $branch)
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 hover:shadow-xl transition-all group relative flex flex-col border-t-4 border-t-blue-500">
                        
                        <div class="absolute top-4 right-4 flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                            {{-- FIX: Use single quotes for @json --}}
                            <button onclick='editBranch(@json($branch))' class="text-slate-400 hover:text-amber-500 p-1.5 rounded-lg bg-white shadow-sm border" title="Editar"><i class="fa-solid fa-pen text-[10px]"></i></button>
                            <form action="{{ url('admin/branches/' . $branch->id) }}" method="POST" onsubmit="return confirm('¿Borrar sucursal?');">
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
                                        <div class="flex gap-1">
                                            <button onclick='editWarehouse(@json($wh))' class="text-slate-300 hover:text-blue-500 p-1"><i class="fa-solid fa-gear text-[9px]"></i></button>
                                            <form action="{{ url('admin/warehouses/' . $wh->id) }}" method="POST" onsubmit="return confirm('¿Eliminar bodega?');">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="text-slate-300 hover:text-rose-500 p-1"><i class="fa-solid fa-xmark text-[9px]"></i></button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-2">
                            <button onclick="openWarehouseModal('{{ $branch->id }}')" class="py-2 bg-white border border-slate-200 text-slate-700 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-slate-50 transition-all">
                                + Bodega
                            </button>
                            <a href="{{ request()->fullUrlWithQuery(['view' => 'map', 'warehouse_id' => $branch->warehouses->first()->id ?? null]) }}" class="py-2 bg-slate-800 text-white rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-black transition-all text-center">
                                Mapa
                            </a>
                        </div>
                    </div>
                @endforeach
            @endif
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
{{-- ALPINE.JS & AXIOS --}}
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('warehouseMap', () => ({
            loading: false,
            submitting: false,
            // Uso seguro de variable PHP con fallback a string vacío
            selectedWarehouseId: '{{ optional($selectedWarehouse)->id ?? "" }}',
            mapData: null,
            showGeneratorModal: false,
            whConfig: { rows: 0, cols: 0, levels: 0 },
            genForm: { bins: 3, type: '' },
            editingRack: { active: false, aisle: '', side: '', rack: '', levelConfigs: [] },

            init() {
                if(this.selectedWarehouseId) {
                    this.loadMapData();
                    // Esperar a que el DOM renderice el select para leer data attributes
                    this.$nextTick(() => {
                        this.updateWhConfigFromSelect();
                    });
                }
            },

            updateWhConfigFromSelect() {
                const select = document.querySelector('select[x-model="selectedWarehouseId"]');
                if(!select) return;
                
                const option = select.options[select.selectedIndex];
                if(option && option.value) {
                    this.whConfig = {
                        rows: parseInt(option.dataset.rows) || 0,
                        cols: parseInt(option.dataset.cols) || 0,
                        levels: parseInt(option.dataset.levels) || 0
                    };
                }
            },

            async loadMapData() {
                if (!this.selectedWarehouseId) {
                    this.mapData = null;
                    return;
                }
                this.updateWhConfigFromSelect();
                this.loading = true;
                try {
                    // Ruta correcta definida en web.php: admin.warehouses.layout_data
                    const response = await axios.get(`/admin/warehouses/${this.selectedWarehouseId}/layout-data`);
                    if (response.data.success) {
                        this.mapData = response.data.structure;
                    }
                } catch (error) {
                    console.error("Error cargando mapa:", error);
                    alert("Error al cargar la estructura de la bodega.");
                } finally {
                    this.loading = false;
                }
            },

            getSortedRacks(racksObj) {
                if (!racksObj) return [];
                return Object.entries(racksObj)
                    .sort((a, b) => parseInt(a[0], 10) - parseInt(b[0], 10))
                    .map(([key, value]) => ({ ...value, key: key }));
            },

            openGeneratorModal() {
                // Seleccionar primer tipo de bin por defecto si no hay uno seleccionado
                if(!this.genForm.type) {
                    const firstOption = document.querySelector('select[x-model="genForm.type"] option:nth-child(2)');
                    if(firstOption) this.genForm.type = firstOption.value;
                }
                this.genForm.bins = 3;
                this.showGeneratorModal = true;
            },

            async generateStructure() {
                if(!confirm('ATENCIÓN: Esto generará o sobrescribirá la estructura base de la bodega. ¿Continuar?')) return;

                this.submitting = true;
                
                const defaultLevelConfigs = [];
                // Usamos whConfig.levels para generar todos los niveles
                const maxLevels = this.whConfig.levels > 0 ? this.whConfig.levels : 1;

                for(let i=1; i <= maxLevels; i++) {
                    defaultLevelConfigs.push({
                        level: i,
                        bins_count: this.genForm.bins,
                        bin_type_id: this.genForm.type
                    });
                }

                try {
                    const response = await axios.post('/admin/warehouses/generate-layout', {
                        warehouse_id: this.selectedWarehouseId,
                        level_configs: defaultLevelConfigs
                    });

                    if (response.data.success) {
                        // Usar una notificación más sutil si es posible, sino alert
                        alert(response.data.message);
                        this.showGeneratorModal = false;
                        this.loadMapData();
                    }
                } catch (error) {
                    alert('Error: ' + (error.response?.data?.message || error.message));
                } finally {
                    this.submitting = false;
                }
            },

            async openRackEditor(aisle, side, rack) {
                this.editingRack = { active: true, aisle, side, rack, levelConfigs: [] };
                
                try {
                    const res = await axios.get('/admin/warehouses/rack-details', { 
                        params: { warehouse_id: this.selectedWarehouseId, aisle, side, rack_col: rack } 
                    });
                    
                    if(res.data.status === 'success' && res.data.levels.length > 0) {
                        this.editingRack.levelConfigs = res.data.levels;
                    } else {
                        // Fallback: crear configuración vacía basada en niveles de la bodega
                        const defaultType = this.genForm.type || document.querySelector('select[x-model="genForm.type"] option:nth-child(2)')?.value;
                        const maxLevels = this.whConfig.levels > 0 ? this.whConfig.levels : 1;
                        
                        for(let i=1; i <= maxLevels; i++) {
                            this.editingRack.levelConfigs.push({ 
                                level: i, 
                                bins_count: 1, 
                                bin_type_id: defaultType 
                            });
                        }
                    }
                } catch(e) { 
                    console.error(e);
                    alert("Error al cargar detalles del rack.");
                }
            },

            async saveSingleRack() {
                this.submitting = true;
                try {
                    const response = await axios.post('/admin/warehouses/save-rack', {
                        warehouse_id: this.selectedWarehouseId,
                        aisle: this.editingRack.aisle,
                        side: this.editingRack.side,
                        rack_code: this.editingRack.rack,
                        level_configs: this.editingRack.levelConfigs
                    });

                    if (response.data.success) {
                        // alert('Rack actualizado correctamente.'); // Opcional: quitar para agilizar
                        this.editingRack.active = false;
                        this.loadMapData(); 
                    }
                } catch (error) { 
                    alert('Error al guardar: ' + (error.response?.data?.message || error.message)); 
                } finally { 
                    this.submitting = false; 
                }
            }
        }));
    });

    // ==========================================
    // FUNCIONES GLOBALES (Modales Vanilla JS)
    // ==========================================

    function closeModal(id) { 
        document.getElementById(id).classList.add('hidden'); 
    }

    async function loadStates(countryId, selectedStateName = null) { 
        const stateSelect = document.getElementById('branchState');
        if (!countryId) {
            stateSelect.innerHTML = '<option value="">Seleccione País</option>';
            return;
        }
        
        stateSelect.innerHTML = '<option>Cargando...</option>';
        
        try {
            // --- CORRECCIÓN CLAVE AQUÍ ---
            // Usamos la ruta de Admin, no la de Portal Cliente
            const res = await fetch(`/admin/utils/get-states/${countryId}`);
            
            if (!res.ok) throw new Error('Error en red');
            
            const data = await res.json();
            stateSelect.innerHTML = '<option value="">Seleccionar...</option>';
            
            if (data.length === 0) {
                stateSelect.innerHTML = '<option value="">Sin estados registrados</option>';
                // Opcional: Permitir escribir manual si no hay estados
            }

            data.forEach(s => {
                const opt = document.createElement('option');
                opt.value = s.name;
                opt.textContent = s.name;
                // Marcar seleccionado si estamos editando
                if (selectedStateName && s.name === selectedStateName) {
                    opt.selected = true;
                }
                stateSelect.appendChild(opt);
            });
        } catch(e) { 
            console.error(e);
            stateSelect.innerHTML = '<option value="">Error al cargar</option>'; 
        }
    }

    function openBranchModal() { 
        document.getElementById('branchForm').reset();
        document.getElementById('branchMethod').value = 'POST';
        document.getElementById('branchForm').action = "{{ route('admin.branches.store') }}";
        document.getElementById('branchModalTitle').innerText = 'Nueva Sucursal';
        
        // Resetear select de estados
        document.getElementById('branchState').innerHTML = '<option value="">Seleccione País primero</option>';
        
        document.getElementById('branchModal').classList.remove('hidden'); 
    }

    function editBranch(b) { 
        document.getElementById('branchName').value = b.name;
        document.getElementById('branchCode').value = b.code;
        document.getElementById('branchCity').value = b.city;
        // Manejo seguro de nulos
        document.getElementById('branchAddress').value = b.address || ''; 
        document.getElementById('branchZip').value = b.zip || '';
        
        // Seleccionar país
        const countrySelect = document.getElementById('branchCountry');
        countrySelect.value = b.country;
        
        // Disparar carga de estados
        const countryOption = Array.from(countrySelect.options).find(o => o.value === b.country);
        if (countryOption) {
            // Pasamos el 2do argumento para que pre-seleccione el estado
            loadStates(countryOption.dataset.id, b.state);
        }

        document.getElementById('branchMethod').value = 'PUT';
        document.getElementById('branchForm').action = `/admin/branches/${b.id}`;
        document.getElementById('branchModalTitle').innerText = 'Editar Sucursal';
        document.getElementById('branchModal').classList.remove('hidden'); 
    }

    function openWarehouseModal(bId) {
        document.getElementById('whForm').reset();
        document.getElementById('modal_branch_id').value = bId;
        document.getElementById('whMethod').value = 'POST';
        document.getElementById('whForm').action = "{{ route('admin.warehouses.store') }}";
        document.getElementById('whModalTitle').innerText = 'Nueva Bodega';
        document.getElementById('warehouseModal').classList.remove('hidden');
    }

    function editWarehouse(w) {
        document.getElementById('whName').value = w.name;
        document.getElementById('whCode').value = w.code;
        document.getElementById('whRows').value = w.rows;
        document.getElementById('whCols').value = w.cols;
        document.getElementById('whLevels').value = w.levels;
        document.getElementById('whMethod').value = 'PUT';
        document.getElementById('whForm').action = `/admin/warehouses/${w.id}`;
        document.getElementById('whModalTitle').innerText = 'Editar Bodega';
        document.getElementById('warehouseModal').classList.remove('hidden');
    }
</script>
@endsection