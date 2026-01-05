@extends('layouts.admin')

@section('title', 'Tarifas y Planes')
@section('header_title', 'Configuración de Tarifas')

@section('content')
<div class="flex justify-between items-center mb-6">
    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Planes de Servicio</h3>
    <button onclick="resetPlanModal(); openModal('modalPlan')" class="bg-indigo-600 text-white px-4 py-2 rounded-xl font-bold text-sm hover:bg-indigo-700 transition shadow-lg shadow-indigo-100">
        <i class="fa-solid fa-plus mr-2"></i> Nuevo Plan
    </button>
</div>

{{-- Cuadrícula de Planes --}}
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
    @forelse($plans as $plan)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden flex flex-col hover:border-indigo-300 transition group">
            <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-start">
                <div>
                    <h4 class="font-black text-slate-800 uppercase text-sm">{{ $plan->name }}</h4>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Esquema: {{ strtoupper($plan->storage_billing_type) }}</p>
                </div>
                <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition">
                    {{-- Botón Editar Plan --}}
                    <button onclick='editPlan(@json($plan), @json($plan->binPrices))' class="text-indigo-400 hover:text-indigo-600">
                        <i class="fa-solid fa-pen-to-square text-xs"></i>
                    </button>
                    {{-- Botón Eliminar Plan --}}
                    <form action="{{ route('admin.billing.rates.destroy', $plan->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este plan definitivamente?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-slate-300 hover:text-red-500">
                            <i class="fa-solid fa-trash-can text-xs"></i>
                        </button>
                    </form>
                </div>
            </div>
            <div class="p-5 flex-1 space-y-2 text-xs text-slate-600">
                <div class="flex justify-between"><span>Recepción</span> <span class="font-bold text-slate-800">${{ number_format($plan->reception_cost_per_box, 2) }}</span></div>
                <div class="flex justify-between"><span>Picking</span> <span class="font-bold text-slate-800">${{ number_format($plan->picking_cost_per_order, 2) }}</span></div>
                <div class="flex justify-between"><span>Item Adicional</span> <span class="font-bold text-slate-800">${{ number_format($plan->additional_item_cost, 2) }}</span></div>
                
                @if($plan->storage_billing_type === 'm3')
                    <div class="pt-3 mt-3 border-t border-slate-100 flex justify-between text-indigo-600 font-bold">
                        <span>m³ Mensual</span> <span>${{ number_format($plan->m3_price_monthly, 2) }}</span>
                    </div>
                @else
                    <div class="pt-3 mt-3 border-t border-slate-100">
                        <p class="font-black text-[9px] uppercase text-slate-400 mb-2">Precios Bins (Día):</p>
                        <div class="grid grid-cols-1 gap-1">
                            @foreach($plan->binPrices as $bp)
                                <div class="flex justify-between text-[10px] bg-slate-50 p-2 rounded-lg">
                                    <span class="text-slate-500">{{ $bp->binType->name ?? 'N/A' }}</span>
                                    <span class="font-bold text-slate-800">${{ number_format($bp->price_per_day, 3) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @empty
        <div class="col-span-3 py-12 text-center bg-white rounded-3xl border-2 border-dashed border-slate-200 text-slate-400 italic text-sm">No hay planes definidos.</div>
    @endforelse
</div>

<div class="flex justify-between items-center mb-6">
    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">Acuerdos Comerciales</h3>
    <button onclick="resetAgreementModal(); openModal('modalAgreement')" class="bg-emerald-600 text-white px-4 py-2 rounded-xl font-bold text-sm hover:bg-emerald-700 transition shadow-lg shadow-emerald-100">
        <i class="fa-solid fa-file-contract mr-2"></i> Asignar Plan
    </button>
</div>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-10">
    @forelse($agreements as $agreement)
        <div class="bg-white p-4 rounded-2xl shadow-sm border border-slate-200 flex items-center justify-between group hover:border-emerald-300 transition">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center text-slate-400 group-hover:bg-emerald-50 transition">
                    <i class="fa-solid fa-user-tie text-xs"></i>
                </div>
                <div>
                    <h4 class="font-black text-sm text-slate-800 truncate max-w-[120px]">{{ $agreement->client->company_name ?? 'Sin Empresa' }}</h4>
                    <p class="text-[10px] font-bold text-indigo-600 uppercase">{{ $agreement->servicePlan->name ?? 'N/A' }}</p>
                    @if($agreement->agreed_m3_volume !== null)
                        <p class="text-[9px] font-black text-emerald-600 uppercase tracking-tighter">{{ $agreement->agreed_m3_volume }} m³</p>
                    @endif
                </div>
            </div>
            
            {{-- ACCIONES DE ACUERDO --}}
            <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition">
                <button onclick="editAgreement({{ $agreement->client_id }}, {{ $agreement->service_plan_id }}, {{ $agreement->agreed_m3_volume ?? 0 }}, {{ $agreement->has_premium_packing ? 'true' : 'false' }})" class="text-indigo-400 hover:text-indigo-600">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <form action="{{ route('admin.billing.agreement.destroy', $agreement->id) }}" method="POST" onsubmit="return confirm('¿Revocar este acuerdo comercial?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-slate-300 hover:text-red-500">
                        <i class="fa-solid fa-circle-xmark"></i>
                    </button>
                </form>
            </div>
        </div>
    @empty
        <p class="col-span-4 text-center text-slate-400 text-xs py-10 italic bg-white rounded-2xl border border-slate-100">No hay clientes con acuerdos vigentes.</p>
    @endforelse
</div>

{{-- MODAL NUEVO/EDITAR PLAN --}}
<div id="modalPlan" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-300">
        <form action="{{ route('admin.billing.rates.store') }}" method="POST" id="formPlan">
            @csrf
            <input type="hidden" name="plan_id" id="plan_id">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-black text-slate-800 uppercase tracking-tight" id="planModalTitle">Configurar Plan</h3>
                <button type="button" onclick="closeModal('modalPlan')" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6 max-h-[70vh] overflow-y-auto">
                <div class="md:col-span-2">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1">Nombre del Plan</label>
                    <input type="text" name="name" id="plan_name" required placeholder="Ej: Tarifa Estándar 2024" class="w-full bg-slate-50 border-none rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 p-3">
                </div>
                <div class="space-y-4">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-1 italic text-indigo-500 font-bold">Costos de Operación</label>
                    <input type="number" step="0.01" name="reception_cost_per_box" id="p_reception" required placeholder="Recepción por caja" class="w-full bg-slate-50 border-none rounded-xl text-sm p-3">
                    <input type="number" step="0.01" name="picking_cost_per_order" id="p_picking" required placeholder="Picking por orden" class="w-full bg-slate-50 border-none rounded-xl text-sm p-3">
                    <input type="number" step="0.01" name="additional_item_cost" id="p_additional" required placeholder="Item adicional" class="w-full bg-slate-50 border-none rounded-xl text-sm p-3">
                    <input type="number" step="0.01" name="premium_packing_cost" id="p_premium" required placeholder="Packing Premium" class="w-full bg-slate-50 border-none rounded-xl text-sm p-3">
                    <input type="number" step="0.01" name="return_cost" id="p_return" required placeholder="Costo por devolución" class="w-full bg-slate-50 border-none rounded-xl text-sm p-3">
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 italic text-amber-500 font-bold">Modelo de Almacenaje</label>
                    <select name="storage_billing_type" id="p_storage_type" onchange="toggleStorageFields(this.value)" class="w-full bg-slate-50 border-none rounded-xl text-sm p-3 mb-4 text-black">
                        <option value="bins">Por Bins / Ubicaciones (Día)</option>
                        <option value="m3">Por Metro Cúbico (Mes)</option>
                    </select>

                    <div id="m3_fields" class="hidden animate-in fade-in duration-300">
                        <label class="block text-[9px] font-bold text-slate-400 uppercase mb-1">Precio x m³ Mensual</label>
                        <input type="number" step="0.01" name="m3_price_monthly" id="p_m3_price" class="w-full bg-slate-50 border-none rounded-xl text-sm p-3 border border-indigo-100">
                    </div>

                    <div id="bin_fields" class="animate-in fade-in duration-300">
                        <p class="text-[9px] font-black text-slate-400 uppercase mb-2">Precios Diarios por Bin:</p>
                        <div class="space-y-2">
                            @foreach($binTypes as $bt)
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] text-slate-500 w-1/2 truncate">{{ $bt->name }}</span>
                                    <input type="number" step="0.001" name="bin_prices[{{ $bt->id }}]" id="bin_price_{{ $bt->id }}" placeholder="0.000" class="w-1/2 bg-slate-50 border-none rounded-lg text-[10px] p-2">
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="p-6 bg-slate-50 flex gap-4">
                <button type="button" onclick="closeModal('modalPlan')" class="flex-1 py-3 text-sm font-bold text-slate-500 hover:bg-slate-200 rounded-xl transition">Descartar</button>
                <button type="submit" class="flex-1 py-3 text-sm font-bold bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 shadow-lg shadow-indigo-200 transition">Guardar Plan</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL ASIGNAR ACUERDO --}}
<div id="modalAgreement" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-300">
        <form action="{{ route('admin.billing.assign_agreement') }}" method="POST" id="formAgreement">
            @csrf
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-black text-slate-800 uppercase tracking-tight text-sm">Vincular Cliente a Plan</h3>
                <button type="button" onclick="closeModal('modalAgreement')" class="text-slate-400 hover:text-slate-600 transition"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="p-8 space-y-6">
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Cliente</label>
                    <select name="client_id" id="agreement_client_id" required class="w-full bg-slate-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 p-3">
                        <option value="">Seleccione un cliente...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2">Seleccionar Tarifa</label>
                    <select name="service_plan_id" id="agreement_service_plan_id" required onchange="handleAgreementPlanChange(this)" class="w-full bg-slate-100 border-none rounded-xl text-sm focus:ring-2 focus:ring-indigo-500 p-3">
                        <option value="">Seleccione un plan...</option>
                        @foreach($plans as $p)
                            <option value="{{ $p->id }}" data-type="{{ $p->storage_billing_type }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Campo dinámico para m3 contratados --}}
                <div id="agreement_m3_volume_container" class="hidden animate-in fade-in duration-300">
                    <label class="block text-[10px] font-black text-slate-400 uppercase mb-2 italic text-emerald-600">Volumen m³ Contratado</label>
                    <input type="number" step="0.01" name="agreed_m3_volume" id="agreement_m3_volume" placeholder="Ej: 5.50" class="w-full bg-slate-100 border-none rounded-xl text-sm p-3 border border-indigo-100">
                    <p class="text-[9px] text-slate-400 mt-1 italic">Indique el volumen base negociado con el cliente.</p>
                </div>

                <div class="flex items-center gap-3 bg-indigo-50 p-4 rounded-2xl border border-indigo-100">
                    <input type="checkbox" name="has_premium_packing" id="agreement_has_premium_packing" class="rounded border-indigo-300 text-indigo-600 focus:ring-indigo-500">
                    <label for="agreement_has_premium_packing" class="text-xs font-bold text-indigo-700 cursor-pointer italic">Activar Packing Premium por defecto</label>
                </div>
            </div>
            <div class="p-6 bg-slate-50 flex gap-4">
                <button type="button" onclick="closeModal('modalAgreement')" class="flex-1 py-3 text-sm font-bold text-slate-500 hover:bg-slate-200 rounded-xl transition">Cancelar</button>
                <button type="submit" class="flex-1 py-3 text-sm font-bold bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 shadow-lg shadow-emerald-100 transition">Activar Acuerdo</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModal(id) { document.getElementById(id).classList.remove('hidden'); }
    function closeModal(id) { document.getElementById(id).classList.add('hidden'); }

    // --- LÓGICA DE ACUERDOS ---
    function resetAgreementModal() {
        document.getElementById('agreement_client_id').value = '';
        document.getElementById('agreement_service_plan_id').value = '';
        document.getElementById('agreement_m3_volume').value = '';
        document.getElementById('agreement_has_premium_packing').checked = false;
        document.getElementById('agreement_m3_volume_container').classList.add('hidden');
    }

    function editAgreement(clientId, planId, m3, hasPremium) {
        document.getElementById('agreement_client_id').value = clientId;
        document.getElementById('agreement_service_plan_id').value = planId;
        document.getElementById('agreement_m3_volume').value = m3;
        document.getElementById('agreement_has_premium_packing').checked = hasPremium;
        
        handleAgreementPlanChange(document.getElementById('agreement_service_plan_id'));
        openModal('modalAgreement');
    }

    function handleAgreementPlanChange(select) {
        const selectedOption = select.options[select.selectedIndex];
        if(!selectedOption || selectedOption.value === "") return;
        
        const type = selectedOption.getAttribute('data-type');
        const container = document.getElementById('agreement_m3_volume_container');
        
        if(type === 'm3') {
            container.classList.remove('hidden');
        } else {
            container.classList.add('hidden');
            document.getElementById('agreement_m3_volume').value = '';
        }
    }

    // --- LÓGICA DE PLANES ---
    function resetPlanModal() {
        document.getElementById('plan_id').value = '';
        document.getElementById('planModalTitle').innerText = 'Configurar Nuevo Plan';
        document.getElementById('plan_name').value = '';
        document.getElementById('p_reception').value = '';
        document.getElementById('p_picking').value = '';
        document.getElementById('p_additional').value = '';
        document.getElementById('p_premium').value = '';
        document.getElementById('p_return').value = '';
        document.getElementById('p_m3_price').value = '';
        document.getElementById('p_storage_type').value = 'bins';
        toggleStorageFields('bins');
    }

    function editPlan(plan, binPrices) {
        document.getElementById('plan_id').value = plan.id;
        document.getElementById('planModalTitle').innerText = 'Editar Plan: ' + plan.name;
        document.getElementById('plan_name').value = plan.name;
        document.getElementById('p_reception').value = plan.reception_cost_per_box;
        document.getElementById('p_picking').value = plan.picking_cost_per_order;
        document.getElementById('p_additional').value = plan.additional_item_cost;
        document.getElementById('p_premium').value = plan.premium_packing_cost;
        document.getElementById('p_return').value = plan.return_cost;
        document.getElementById('p_m3_price').value = plan.m3_price_monthly;
        document.getElementById('p_storage_type').value = plan.storage_billing_type;

        // Limpiar campos de bines
        document.querySelectorAll('[id^="bin_price_"]').forEach(el => el.value = '');
        
        // Cargar precios de bines
        if(binPrices) {
            binPrices.forEach(bp => {
                const el = document.getElementById('bin_price_' + bp.bin_type_id);
                if(el) el.value = bp.price_per_day;
            });
        }

        toggleStorageFields(plan.storage_billing_type);
        openModal('modalPlan');
    }

    function toggleStorageFields(type) {
        const binF = document.getElementById('bin_fields');
        const m3F = document.getElementById('m3_fields');
        if(type === 'm3') {
            binF.classList.add('hidden');
            m3F.classList.remove('hidden');
        } else {
            binF.classList.remove('hidden');
            m3F.classList.add('hidden');
        }
    }
</script>
@endsection