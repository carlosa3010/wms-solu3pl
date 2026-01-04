@extends('layouts.admin')

@section('content')
<div class="space-y-8">
    <div class="flex justify-between items-center">
        <h2 class="text-2xl font-black text-slate-800">Tarifas de Servicios</h2>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Formulario Crear Perfil -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="text-sm font-black uppercase text-slate-400 mb-6 tracking-widest">Nuevo Perfil Tarifario</h3>
            <form action="{{ route('admin.billing.rates.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Nombre del Perfil</label>
                    <input type="text" name="name" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm" placeholder="Ej: Tarifas 2024 Gold">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Recepción ($)</label>
                        <input type="number" step="0.01" name="reception_fee" required class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Picking ($)</label>
                        <input type="number" step="0.01" name="picking_fee" required class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Almacenaje (m³/mes) ($)</label>
                    <input type="number" step="0.01" name="storage_m3_monthly" required class="w-full px-4 py-2 border border-slate-200 rounded-xl text-sm">
                </div>
                
                <div class="bg-blue-50 p-4 rounded-xl space-y-4">
                    <p class="text-[10px] font-black text-blue-600 uppercase">Servicios Extra</p>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Empaque Premium ($)</label>
                        <input type="number" step="0.01" name="premium_packing_fee" value="0.00" class="w-full px-4 py-2 border border-blue-200 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1">Manejo de RMA ($)</label>
                        <input type="number" step="0.01" name="rma_handling_fee" value="0.00" class="w-full px-4 py-2 border border-blue-200 rounded-xl text-sm">
                    </div>
                </div>

                <button type="submit" class="w-full bg-slate-800 text-white py-3 rounded-xl font-bold hover:bg-black transition-all">
                    Guardar Perfil
                </button>
            </form>
        </div>

        <!-- Formulario Asignar Perfil a Cliente -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200">
            <h3 class="text-sm font-black uppercase text-slate-400 mb-6 tracking-widest">Asignar Tarifas a Cliente</h3>
            <form action="{{ route('admin.billing.assign_agreement') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Seleccionar Cliente</label>
                    <select name="client_id" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                        <option value="">Elegir cliente...</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1">Perfil Tarifario</label>
                    <select name="profile_id" required class="w-full px-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-sm">
                        <option value="">Elegir perfil...</option>
                        @foreach($profiles as $profile)
                            <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="w-full bg-blue-600 text-white py-3 rounded-xl font-bold hover:bg-blue-700 shadow-lg shadow-blue-200 transition-all">
                    Establecer Acuerdo
                </button>
            </form>
        </div>

        <!-- Listado de Acuerdos Activos -->
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-200 lg:col-span-3">
             <h3 class="text-sm font-black uppercase text-slate-400 mb-4 tracking-widest">Acuerdos Tarifarios Vigentes</h3>
             <table class="w-full text-left">
                 <thead>
                     <tr class="text-[10px] text-slate-400 uppercase">
                         <th class="py-2">Cliente</th>
                         <th class="py-2">Perfil Asignado</th>
                         <th class="py-2">Estatus</th>
                         <th class="py-2">Desde</th>
                     </tr>
                 </thead>
                 <tbody class="divide-y divide-slate-100">
                     @foreach($agreements as $agreement)
                     <tr class="text-sm">
                         <td class="py-3 font-bold text-slate-700">{{ $agreement->client->company_name }}</td>
                         <td class="py-3 text-blue-600 font-medium">{{ $agreement->billingProfile->name }}</td>
                         <td class="py-3">
                             <span class="px-2 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-full uppercase">Activo</span>
                         </td>
                         <td class="py-3 text-slate-500 text-xs">{{ $agreement->starts_at }}</td>
                     </tr>
                     @endforeach
                 </tbody>
             </table>
        </div>
    </div>
</div>
@endsection