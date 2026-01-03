@extends('layouts.admin')

@section('title', 'Cobertura Geográfica')
@section('header_title', 'Inteligencia de Despacho')

@section('content')
<div class="space-y-6">
    <!-- Header Compacto -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm flex flex-col md:flex-row justify-between items-center gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight leading-none">Cobertura de Sucursales</h2>
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Asignación automática de pedidos por zona</p>
        </div>
        <div class="flex items-center gap-2 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-xl border border-blue-100 text-[10px] font-black uppercase tracking-widest">
            <i class="fa-solid fa-circle-info"></i> El sistema asignará el almacén más cercano al cliente
        </div>
    </div>

    <!-- Grid de Sucursales Compacto -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        @foreach($branches as $branch)
            <div class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm hover:shadow-lg transition-all flex flex-col h-full border-t-4 border-t-slate-800">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-9 h-9 bg-slate-900 text-white rounded-xl flex items-center justify-center text-base shrink-0 shadow-sm">
                        <i class="fa-solid fa-truck-fast"></i>
                    </div>
                    <div class="min-w-0">
                        <h3 class="font-black text-xs text-slate-800 leading-none truncate">{{ $branch->name }}</h3>
                        <span class="text-[8px] font-black text-blue-500 uppercase tracking-tighter mt-1 block">{{ $branch->code }}</span>
                    </div>
                </div>

                <!-- Resumen de Cobertura Actual -->
                <div class="flex-1 space-y-3">
                    <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1">
                            <i class="fa-solid fa-globe text-[7px]"></i> Países Atendidos
                        </p>
                        <div class="flex flex-wrap gap-1">
                            @forelse($branch->covered_countries ?? [] as $c)
                                <span class="px-1.5 py-0.5 bg-blue-600 text-white text-[8px] font-black rounded uppercase shadow-sm">{{ $c }}</span>
                            @empty
                                <span class="text-[9px] text-slate-400 italic">Sin cobertura configurada</span>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-slate-50 p-2.5 rounded-xl border border-slate-100">
                        <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1.5 flex items-center gap-1">
                            <i class="fa-solid fa-map text-[7px]"></i> Estados/Provincias
                        </p>
                        <div class="max-h-20 overflow-y-auto custom-scrollbar flex flex-wrap gap-1">
                            @forelse($branch->covered_states ?? [] as $s)
                                <span class="px-1.5 py-0.5 bg-white border border-slate-200 text-slate-600 text-[8px] font-bold rounded shadow-sm">{{ $s }}</span>
                            @empty
                                <span class="text-[9px] text-slate-400 italic font-medium">Global (Todo el país)</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-50 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="text-[9px] font-bold text-slate-400 uppercase">Export:</span>
                        <i class="fa-solid {{ $branch->can_export ? 'fa-circle-check text-emerald-500' : 'fa-circle-xmark text-slate-200' }} text-xs"></i>
                    </div>
                    <button onclick='openCoverageModal(@json($branch))' class="px-3 py-1.5 bg-slate-800 text-white rounded-lg text-[9px] font-black uppercase tracking-widest hover:bg-black transition-all active:scale-95 shadow-sm">
                        Configurar
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</div>

<!-- Modal de Configuración Avanzada -->
<div id="coverageModal" class="hidden fixed inset-0 bg-slate-900/60 z-[100] flex items-center justify-center backdrop-blur-md p-4">
    <div class="bg-white rounded-[2.5rem] shadow-2xl w-full max-w-4xl overflow-hidden animate-in fade-in zoom-in duration-200">
        <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-600 text-white rounded-2xl flex items-center justify-center shadow-lg shadow-blue-200">
                    <i class="fa-solid fa-globe"></i>
                </div>
                <div>
                    <h3 id="modalBranchName" class="font-black text-slate-800 text-lg tracking-tighter">Nombre Sucursal</h3>
                    <p class="text-[9px] font-black text-slate-400 uppercase tracking-widest">Zonas de Influencia Geográfica</p>
                </div>
            </div>
            <button onclick="closeModal('coverageModal')" class="text-slate-400 hover:text-rose-500 p-2"><i class="fa-solid fa-xmark text-2xl"></i></button>
        </div>

        <form id="coverageForm" method="POST" class="p-8 space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-12 gap-8">
                <!-- COLUMNA 1: LISTADO DE PAÍSES (3 COLUMNAS) -->
                <div class="md:col-span-4 space-y-4">
                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-blue-500 rounded-full"></div> 1. Elegir Países
                    </h4>
                    <div class="grid grid-cols-1 gap-1 max-h-[400px] overflow-y-auto custom-scrollbar p-2 bg-slate-50 rounded-2xl border border-slate-100">
                        @isset($countries)
                            @foreach($countries as $country)
                                <label class="flex items-center gap-3 p-2.5 bg-white rounded-xl border border-transparent hover:border-blue-200 transition-all cursor-pointer group">
                                    <input type="checkbox" name="covered_countries[]" value="{{ $country->name }}" 
                                           data-id="{{ $country->id }}" data-name="{{ $country->name }}" onchange="filterStatesBySelection()"
                                           class="w-4 h-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500 js-country-checkbox">
                                    <span class="text-xs font-black text-slate-700 group-hover:text-blue-600 transition-colors uppercase tracking-tight">{{ $country->name }}</span>
                                </label>
                            @endforeach
                        @endisset
                    </div>
                </div>

                <!-- COLUMNA 2: BLOQUES DE ESTADOS POR PAÍS (8 COLUMNAS) -->
                <div class="md:col-span-8 space-y-4">
                    <h4 class="text-[10px] font-black text-slate-500 uppercase tracking-[0.2em] flex items-center gap-2">
                        <div class="w-1.5 h-4 bg-emerald-500 rounded-full"></div> 2. Estados Atendidos
                    </h4>
                    
                    <div id="statesWrapper" class="max-h-[400px] overflow-y-auto custom-scrollbar p-2 space-y-4 bg-slate-50 rounded-2xl border border-slate-100 min-h-[100px]">
                        
                        <div id="noCountriesMsg" class="p-12 text-center text-[10px] font-bold text-slate-400 italic leading-relaxed">
                            Selecciona países a la izquierda para configurar sus estados.
                        </div>

                        @isset($states)
                            @php $groupedStates = $states->groupBy('country_id'); @endphp
                            @foreach($groupedStates as $countryId => $countryStates)
                                @php $countryName = $countryStates->first()->country->name; @endphp
                                <div class="hidden js-state-country-block bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm animate-in fade-in slide-in-from-right-2 duration-200" 
                                     data-country-id="{{ $countryId }}">
                                    <div class="px-4 py-2 bg-slate-800 text-white flex justify-between items-center">
                                        <span class="text-[10px] font-black uppercase tracking-widest">{{ $countryName }}</span>
                                        <span class="text-[9px] font-bold opacity-60">{{ $countryStates->count() }} regiones</span>
                                    </div>
                                    <div class="p-4 grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        @foreach($countryStates as $state)
                                            <label class="flex items-center gap-2 p-1.5 hover:bg-slate-50 rounded-lg cursor-pointer transition-colors group">
                                                <input type="checkbox" name="covered_states[]" value="{{ $state->name }}" 
                                                       class="w-3.5 h-3.5 rounded text-emerald-600 focus:ring-emerald-500 js-state-checkbox">
                                                <span class="text-[10px] font-bold text-slate-600 group-hover:text-emerald-700 transition-colors leading-tight">{{ $state->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        @endisset
                    </div>
                </div>
            </div>

            <div class="pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <label class="flex items-center gap-3 cursor-pointer group">
                    <div class="relative inline-flex items-center">
                        <input type="checkbox" name="can_export" id="canExportCheck" class="sr-only peer">
                        <div class="w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500 shadow-inner"></div>
                    </div>
                    <span class="text-[10px] font-black text-slate-600 uppercase tracking-widest group-hover:text-emerald-600 transition-colors">Permitir Exportación Directa</span>
                </label>

                <button type="submit" class="w-full sm:w-auto bg-blue-600 text-white px-12 py-4 rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-xl shadow-blue-500/40 hover:bg-blue-700 transition-all active:scale-95">
                    Guardar Configuración
                </button>
            </div>
        </form>
    </div>
</div>

@endsection

@section('scripts')
<script>
    function closeModal(id) { 
        document.getElementById(id).classList.add('hidden'); 
    }

    /**
     * Abre el modal y carga los datos de la sucursal seleccionada
     */
    function openCoverageModal(branch) {
        if (!branch) return;

        document.getElementById('modalBranchName').innerText = branch.name;
        const form = document.getElementById('coverageForm');
        form.action = `/admin/branches/${branch.id}/coverage`;

        // Resetear todos los checkboxes
        form.querySelectorAll('input[type="checkbox"]').forEach(cb => cb.checked = false);

        // Marcar Países Guardados
        const countries = Array.isArray(branch.covered_countries) ? branch.covered_countries : [];
        countries.forEach(c => {
            const cb = form.querySelector(`.js-country-checkbox[value="${c}"]`);
            if (cb) cb.checked = true;
        });

        // Marcar Estados Guardados
        const states = Array.isArray(branch.covered_states) ? branch.covered_states : [];
        states.forEach(s => {
            const cb = form.querySelector(`.js-state-checkbox[value="${s}"]`);
            if (cb) cb.checked = true;
        });

        document.getElementById('canExportCheck').checked = !!branch.can_export;
        document.getElementById('coverageModal').classList.remove('hidden');
        
        filterStatesBySelection();
    }

    /**
     * Muestra bloques de estados agrupados por país
     */
    function filterStatesBySelection() {
        const selectedCountryIds = Array.from(document.querySelectorAll('.js-country-checkbox:checked'))
                                       .map(cb => cb.dataset.id);
        
        const stateBlocks = document.querySelectorAll('.js-state-country-block');
        const noMsg = document.getElementById('noCountriesMsg');

        if (selectedCountryIds.length > 0) {
            noMsg.classList.add('hidden');
            
            stateBlocks.forEach(block => {
                const countryId = block.dataset.countryId;
                if (selectedCountryIds.includes(countryId)) {
                    block.classList.remove('hidden');
                } else {
                    block.classList.add('hidden');
                    // Limpiar selecciones de estados de bloques ocultos
                    block.querySelectorAll('input:checked').forEach(cb => cb.checked = false);
                }
            });
        } else {
            noMsg.classList.remove('hidden');
            stateBlocks.forEach(block => block.classList.add('hidden'));
        }
    }
</script>
@endsection