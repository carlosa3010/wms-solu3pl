@extends('layouts.admin')

@section('title', 'Tarifas y Contratos')
@section('header_title', 'Configuración de Cobros')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- LADO IZQUIERDO: CREAR PERFIL -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-6">
            <div class="p-5 bg-slate-50 border-b border-slate-100">
                <h3 class="font-bold text-slate-700 flex items-center gap-2">
                    <i class="fa-solid fa-file-invoice-dollar text-custom-primary"></i> Nuevo Perfil Tarifario
                </h3>
            </div>
            <form action="{{ route('admin.billing.rates.store') }}" method="POST" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nombre del Plan</label>
                    <input type="text" name="name" required placeholder="Ej: Tarifa E-commerce Plus" class="w-full px-3 py-2 border rounded-lg text-sm outline-none focus:ring-2 ring-custom-primary transition">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Moneda</label>
                        <select name="currency" class="w-full px-3 py-2 border rounded-lg text-sm bg-white">
                            <option value="USD">USD ($)</option>
                            <option value="VES">VES (Bs)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Alm. Bin/Día</label>
                        <input type="number" name="storage_fee_per_bin_daily" step="0.01" required placeholder="0.50" class="w-full px-3 py-2 border rounded-lg text-sm">
                    </div>
                </div>

                <hr class="border-slate-100">

                <div class="space-y-3">
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Costos Operativos</p>
                    <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="text-xs font-bold text-slate-600">Base Picking (Orden)</span>
                        <input type="number" name="picking_fee_base" step="0.1" required class="w-20 p-1 text-right border rounded text-sm" value="1.50">
                    </div>
                    <div class="flex justify-between items-center bg-slate-50 p-3 rounded-xl border border-slate-100">
                        <span class="text-xs font-bold text-slate-600">Entrada (Por Unidad)</span>
                        <input type="number" name="inbound_fee_per_unit" step="0.01" required class="w-20 p-1 text-right border rounded text-sm" value="0.10">
                    </div>
                </div>

                <button type="submit" class="w-full bg-custom-primary text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-blue-500/20 hover:brightness-110 transition">
                    Guardar Perfil
                </button>
            </form>
        </div>
    </div>

    <!-- LADO DERECHO: LISTADO Y ASIGNACIÓN -->
    <div class="lg:col-span-2 space-y-6">
        
        <!-- Perfiles Existentes -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100 font-bold text-slate-700 text-xs uppercase tracking-wider">
                Planes Tarifarios Activos
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/50 text-slate-400 font-bold text-[10px] uppercase">
                        <tr>
                            <th class="px-6 py-3">Perfil</th>
                            <th class="px-6 py-3 text-center">Almacenamiento</th>
                            <th class="px-6 py-3 text-center">Operativo</th>
                            <th class="px-6 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($profiles as $profile)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <p class="font-bold text-slate-700">{{ $profile->name }}</p>
                                <span class="text-[10px] bg-blue-100 text-blue-600 px-1.5 py-0.5 rounded font-black">{{ $profile->currency }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-xs font-bold text-slate-600">${{ number_format($profile->storage_fee_per_bin_daily, 2) }} /bin día</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <p class="text-[10px] text-slate-400 font-bold uppercase">Pick: ${{ $profile->picking_fee_base }}</p>
                                <p class="text-[10px] text-slate-400 font-bold uppercase">In: ${{ $profile->inbound_fee_per_unit }}</p>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <button class="text-slate-400 hover:text-custom-primary"><i class="fa-solid fa-pen-to-square"></i></button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Asignación por Cliente -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-100 font-bold text-slate-700 text-xs uppercase tracking-wider">
                Acuerdos por Cliente
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($clients as $client)
                    <div class="p-4 rounded-xl border border-slate-100 bg-slate-50 flex justify-between items-center">
                        <div>
                            <p class="font-bold text-slate-800 text-sm">{{ $client->company_name }}</p>
                            <p class="text-[10px] text-slate-400 uppercase font-black">
                                Plan: <span class="text-custom-primary">{{ $client->billingAgreement->profile->name ?? 'SIN PLAN' }}</span>
                            </p>
                        </div>
                        <button class="bg-white text-slate-600 p-2 rounded-lg border border-slate-200 hover:text-custom-primary transition shadow-sm">
                            <i class="fa-solid fa-link"></i>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection