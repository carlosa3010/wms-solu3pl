@extends('layouts.admin')

@section('title', 'Nuevo Cliente')
@section('header_title', 'Registrar Socio Comercial')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-6 border-b border-slate-100 bg-slate-50">
            <h3 class="font-bold text-slate-700 flex items-center gap-2">
                <i class="fa-solid fa-user-plus text-custom-primary"></i> Datos de la Cuenta
            </h3>
        </div>

        <form action="{{ route('admin.clients.store') }}" method="POST" enctype="multipart/form-data" class="p-8">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Razón Social -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Razón Social *</label>
                    <input type="text" name="company_name" required placeholder="Ej: Importadora Logística C.A." 
                           class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none">
                </div>

                <!-- ID Fiscal -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">ID Fiscal / RIF *</label>
                    <input type="text" name="tax_id" required placeholder="J-12345678-9" 
                           class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm font-mono focus:ring-2 ring-custom-primary outline-none">
                </div>

                <!-- Correo -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Email Corporativo *</label>
                    <input type="email" name="email" required placeholder="admin@empresa.com" 
                           class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none">
                </div>

                <!-- Contacto -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Persona de Contacto *</label>
                    <input type="text" name="contact_name" required placeholder="Nombre del gerente" 
                           class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none">
                </div>

                <!-- Teléfono -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Teléfono</label>
                    <input type="text" name="phone" placeholder="+58..." 
                           class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none">
                </div>

                <!-- Logo -->
                <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Logo de la Empresa</label>
                    <input type="file" name="logo" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                </div>
            </div>

            <!-- Dirección Fiscal (NUEVO) -->
            <div class="mb-8">
                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-1">Dirección Fiscal *</label>
                <textarea name="address" rows="3" required placeholder="Ingrese la dirección completa para la emisión de facturas..." 
                          class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none"></textarea>
                <p class="text-[9px] text-slate-400 mt-1 italic">Esta dirección se imprimirá en los documentos de facturación y guías de despacho.</p>
            </div>

            <div class="flex justify-end gap-4 border-t border-slate-100 pt-6">
                <a href="{{ route('admin.clients.index') }}" class="px-6 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-sm hover:bg-slate-50 transition">Cancelar</a>
                <button type="submit" class="bg-custom-primary text-white px-8 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-blue-500/30 hover:brightness-110 transition">
                    Registrar Cliente
                </button>
            </div>
        </form>
    </div>
</div>
@endsection