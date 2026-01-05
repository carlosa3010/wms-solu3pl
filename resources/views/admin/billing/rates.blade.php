@extends('layouts.admin')

@section('title', 'Tarifas y Acuerdos')
@section('header_title', 'Configuración de Servicios')

@section('content')

<div class="flex flex-col gap-6" x-data="{ showPlanModal: false, showAgreementModal: false }">

    {{-- Sección de Planes --}}
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="font-black text-slate-800 text-sm uppercase tracking-wide">Planes de Servicio</h3>
                <p class="text-xs text-slate-400">Plantillas de precios para asignar a clientes</p>
            </div>
            <button @click="showPlanModal = true" class="bg-indigo-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-indigo-700 transition">
                <i class="fa-solid fa-plus mr-1"></i> Crear Plan
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] text-slate-400 uppercase border-b border-slate-100 bg-slate-50">
                        <th class="p-3 font-black rounded-tl-lg">Nombre Plan</th>
                        <th class="p-3 font-black">Picking (Base)</th>
                        <th class="p-3 font-black">Item Adic.</th>
                        <th class="p-3 font-black">Recepción (Caja)</th>
                        <th class="p-3 font-black">Almacenamiento</th>
                        <th class="p-3 font-black rounded-tr-lg text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="text-xs">
                    @foreach($plans as $plan)
                        <tr class="border-b border-slate-50 hover:bg-slate-50">
                            <td class="p-3 font-bold text-slate-700">{{ $plan->name }}</td>
                            <td class="p-3">${{ $plan->picking_cost_per_order }}</td>
                            <td class="p-3">${{ $plan->additional_item_cost }}</td>
                            <td class="p-3">${{ $plan->reception_cost_per_box }}</td>
                            <td class="p-3">
                                @if($plan->storage_billing_type == 'm3')
                                    <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded text-[10px] font-bold">Por m³ (${{ $plan->m3_price_monthly }}/mes)</span>
                                @else
                                    <span class="bg-purple-100 text-purple-700 px-2 py-0.5 rounded text-[10px] font-bold">Por Bines (Diario)</span>
                                @endif
                            </td>
                            <td class="p-3 text-center">
                                <button class="text-slate-400 hover:text-rose-500 transition"><i class="fa-solid fa-trash"></i></button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Sección de Acuerdos Vigentes --}}
    <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h3 class="font-black text-slate-800 text-sm uppercase tracking-wide">Acuerdos Comerciales</h3>
                <p class="text-xs text-slate-400">Vinculación Cliente - Plan</p>
            </div>
            <button @click="showAgreementModal = true" class="bg-emerald-600 text-white px-4 py-2 rounded-lg text-xs font-bold hover:bg-emerald-700 transition">
                <i class="fa-solid fa-handshake mr-1"></i> Nuevo Acuerdo
            </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($agreements as $agreement)
                <div class="border border-slate-200 rounded-xl p-4 hover:shadow-md transition bg-slate-50/50">
                    <div class="flex justify-between items-start mb-2">
                        <h4 class="font-black text-sm text-slate-800">{{ $agreement->client->company_name }}</h4>
                        <span class="text-[10px] bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full font-bold">Activo</span>
                    </div>
                    <p class="text-xs text-slate-500 mb-1"><i class="fa-solid fa-file-contract mr-1"></i> Plan: {{ $agreement->servicePlan->name }}</p>
                    <div class="flex gap-2 mt-2 text-[10px]">
                        @if($agreement->has_premium_packing)
                            <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded font-bold">Empaque Premium</span>
                        @endif
                        @if($agreement->agreed_m3_volume > 0)
                            <span class="bg-blue-100 text-blue-700 px-2 py-0.5 rounded font-bold">{{ $agreement->agreed_m3_volume }} m³</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Modal Crear Plan --}}
    <div x-show="showPlanModal" style="display: none;" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm" x-transition.opacity>
        <div class="bg-white rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-hidden flex flex-col shadow-2xl" @click.away="showPlanModal = false">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-black text-slate-800">Nuevo Plan de Servicios</h3>
                <button @click="showPlanModal = false" class="text-slate-400 hover:text-rose-500"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.billing.rates.store') }}" method="POST" class="p-6 overflow-y-auto flex-1 custom-scrollbar">
                @csrf
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="col-span-2">
                        <label class="block text-xs font-bold text-slate-500 mb-1">Nombre del Plan</label>
                        <input type="text" name="name" required class="w-full border border-slate-200 rounded-lg p-2 text-sm focus:ring-2 focus:ring-indigo-500 outline-none" placeholder="Ej: E-commerce Standard">
                    </div>
                    
                    {{-- Costos Operativos --}}
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Recepción (por caja)</label>
                        <div class="relative"><span class="absolute left-3 top-2 text-slate-400">$</span><input type="number" step="0.01" name="reception_cost_per_box" class="w-full border border-slate-200 rounded-lg p-2 pl-6 text-sm" value="0"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Picking (Pedido Base)</label>
                        <div class="relative"><span class="absolute left-3 top-2 text-slate-400">$</span><input type="number" step="0.01" name="picking_cost_per_order" class="w-full border border-slate-200 rounded-lg p-2 pl-6 text-sm" value="0"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Item Adicional</label>
                        <div class="relative"><span class="absolute left-3 top-2 text-slate-400">$</span><input type="number" step="0.01" name="additional_item_cost" class="w-full border border-slate-200 rounded-lg p-2 pl-6 text-sm" value="0"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Devolución (RMA)</label>
                        <div class="relative"><span class="absolute left-3 top-2 text-slate-400">$</span><input type="number" step="0.01" name="return_cost" class="w-full border border-slate-200 rounded-lg p-2 pl-6 text-sm" value="0"></div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Empaque Premium (Opcional)</label>
                        <div class="relative"><span class="absolute left-3 top-2 text-slate-400">$</span><input type="number" step="0.01" name="premium_packing_cost" class="w-full border border-slate-200 rounded-lg p-2 pl-6 text-sm" value="0"></div>
                    </div>
                </div>

                <div class="border-t border-slate-100 pt-4" x-data="{ billingType: 'bins' }">
                    <h4 class="font-black text-xs text-slate-700 mb-3 uppercase">Modelo de Almacenamiento</h4>
                    <div class="flex gap-4 mb-4">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="storage_billing_type" value="bins" x-model="billingType" class="text-indigo-600">
                            <span class="text-sm font-medium">Por Bines (Diario)</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="storage_billing_type" value="m3" x-model="billingType" class="text-indigo-600">
                            <span class="text-sm font-medium">Por Volúmen (m³/Mes)</span>
                        </label>
                    </div>

                    {{-- Configuración M3 --}}
                    <div x-show="billingType === 'm3'" class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                        <label class="block text-xs font-bold text-blue-700 mb-1">Precio por m³ Mensual</label>
                        <div class="relative"><span class="absolute left-3 top-2 text-blue-400">$</span><input type="number" step="0.01" name="m3_price_monthly" class="w-full border border-blue-200 rounded-lg p-2 pl-6 text-sm" placeholder="0.00"></div>
                    </div>

                    {{-- Configuración Bines --}}
                    <div x-show="billingType === 'bins'" class="space-y-2 bg-slate-50 p-3 rounded-lg border border-slate-100">
                        <p class="text-[10px] font-bold text-slate-400 uppercase mb-2">Precios Diarios por Tipo de Bin</p>
                        @foreach($binTypes as $type)
                            <div class="flex justify-between items-center">
                                <span class="text-xs text-slate-600">{{ $type->name }}</span>
                                <div class="relative w-24">
                                    <span class="absolute left-2 top-1.5 text-slate-400 text-xs">$</span>
                                    <input type="number" step="0.01" name="bin_prices[{{ $type->id }}]" class="w-full border border-slate-200 rounded p-1 pl-4 text-xs" placeholder="0.00">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold shadow-lg hover:bg-indigo-700 transition">Guardar Plan</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Modal Asignar Acuerdo --}}
    <div x-show="showAgreementModal" style="display: none;" class="fixed inset-0 bg-slate-900/60 z-50 flex items-center justify-center p-4 backdrop-blur-sm" x-transition.opacity>
        <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl" @click.away="showAgreementModal = false">
            <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-black text-slate-800">Asignar Acuerdo</h3>
                <button @click="showAgreementModal = false" class="text-slate-400 hover:text-rose-500"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.billing.assign_agreement') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Cliente</label>
                    <select name="client_id" required class="w-full border border-slate-200 rounded-lg p-2 text-sm bg-white">
                        <option value="">Seleccionar...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Plan de Servicio</label>
                    <select name="service_plan_id" required class="w-full border border-slate-200 rounded-lg p-2 text-sm bg-white">
                        <option value="">Seleccionar...</option>
                        @foreach($plans as $plan)
                            <option value="{{ $plan->id }}">{{ $plan->name }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="p-3 bg-slate-50 rounded-lg border border-slate-100 space-y-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Volumen m³ Contratado (Si aplica)</label>
                        <input type="number" step="0.01" name="agreed_m3_volume" class="w-full border border-slate-200 rounded p-2 text-sm" value="0">
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="has_premium_packing" class="rounded text-indigo-600 focus:ring-indigo-500">
                        <span class="text-sm text-slate-700">Contrata Empaque Premium</span>
                    </label>
                </div>

                <button type="submit" class="w-full bg-emerald-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-emerald-700 transition">Confirmar Acuerdo</button>
            </form>
        </div>
    </div>

</div>
@endsection