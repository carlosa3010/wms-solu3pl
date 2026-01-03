@extends('layouts.admin')

@section('title', 'Editar Cliente')
@section('header_title', 'Configuración de Socio')

@section('content')
<div class="max-w-4xl mx-auto">
    <!-- Navegación -->
    <div class="mb-6 flex items-center justify-between">
        <a href="{{ route('admin.clients.index') }}" class="flex items-center gap-2 text-slate-500 hover:text-custom-primary transition font-bold text-sm">
            <i class="fa-solid fa-arrow-left"></i> Volver al listado
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
            <h3 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-user-gear text-custom-primary"></i> Modificar Datos de {{ $client->company_name }}
            </h3>
            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-lg text-[10px] font-black uppercase">ID: {{ $client->id }}</span>
        </div>

        <form action="{{ route('admin.clients.update', $client->id) }}" method="POST" enctype="multipart/form-data" class="p-8">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Razón Social -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Razón Social *</label>
                    <input type="text" name="company_name" value="{{ old('company_name', $client->company_name) }}" required 
                           class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none">
                </div>

                <!-- ID Fiscal -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">ID Fiscal / RIF *</label>
                    <input type="text" name="tax_id" value="{{ old('tax_id', $client->tax_id) }}" required 
                           class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 ring-custom-primary outline-none uppercase">
                </div>

                <!-- Correo -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Email Corporativo *</label>
                    <input type="email" name="email" value="{{ old('email', $client->email) }}" required 
                           class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none">
                </div>

                <!-- Contacto -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Persona de Contacto *</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name', $client->contact_name) }}" required 
                           class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none">
                </div>

                <!-- Teléfono -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Teléfono</label>
                    <input type="text" name="phone" value="{{ old('phone', $client->phone) }}" 
                           class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none">
                </div>

                <!-- Logo Actual y Nuevo -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Logo de la Empresa</label>
                    <div class="flex items-center gap-4 mt-1">
                        @if($client->logo_url)
                            <img src="{{ $client->logo_url }}" alt="Logo" class="w-10 h-10 rounded-lg object-cover border border-slate-200">
                        @endif
                        <input type="file" name="logo" class="flex-1 text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    </div>
                </div>
            </div>

            <!-- Dirección Fiscal (Sincronizado con Create) -->
            <div class="mb-8">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dirección Fiscal *</label>
                <textarea name="address" rows="3" required placeholder="Ingrese la dirección completa..." 
                          class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none">{{ old('address', $client->address) }}</textarea>
                <p class="text-[9px] text-slate-400 mt-1 italic">Este campo es requerido para la generación de facturas y guías de despacho.</p>
            </div>

            <div class="flex justify-end gap-4 border-t border-slate-100 pt-6">
                <a href="{{ route('admin.clients.index') }}" class="px-6 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-sm hover:bg-slate-50 transition">Cancelar</a>
                <button type="submit" class="bg-custom-primary text-white px-8 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-500/30 hover:brightness-110 transition flex items-center gap-2">
                    <i class="fa-solid fa-save"></i> Guardar Cambios
                </button>
            </div>
        </form>
    </div>

    <!-- Sección de Peligro / Otros -->
    <div class="mt-12 p-6 bg-red-50 rounded-2xl border border-red-100">
        <h4 class="text-xs font-black text-red-600 uppercase tracking-widest mb-4">Zona de Peligro</h4>
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
            <div>
                <p class="text-sm font-bold text-slate-700">Eliminar Cliente</p>
                <p class="text-xs text-slate-500">Esto ocultará al cliente del sistema pero mantendrá su historial logístico.</p>
            </div>
            <form action="{{ route('admin.clients.destroy', $client->id) }}" method="POST" onsubmit="return confirm('¿Está seguro de eliminar este cliente?');">
                @csrf @method('DELETE')
                <button type="submit" class="bg-white text-red-500 px-4 py-2 rounded-lg border border-red-200 text-xs font-bold hover:bg-red-50 transition">
                    Dar de baja
                </button>
            </form>
        </div>
    </div>
</div>
@endsection