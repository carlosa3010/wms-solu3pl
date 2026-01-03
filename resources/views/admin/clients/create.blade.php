@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <!-- Encabezado -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Nuevo Cliente</h1>
            <p class="text-sm text-slate-500 mt-1">Registra una nueva empresa o socio comercial en el sistema</p>
        </div>
        <a href="{{ route('admin.clients.index') }}" class="text-sm font-medium text-slate-500 hover:text-blue-600 flex items-center transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4 mr-1"></i>
            Volver al listado
        </a>
    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <form action="{{ route('admin.clients.store') }}" method="POST" class="p-6 space-y-8">
            @csrf
            
            <!-- Valor por defecto para facturación (oculto) -->
            <input type="hidden" name="billing_type" value="transactional">

            <!-- Sección: Información de la Empresa -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                    <div class="p-1.5 bg-blue-50 rounded-lg text-blue-600">
                        <i data-lucide="building-2" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Datos Corporativos</h3>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Razón Social <span class="text-rose-500">*</span></label>
                        <input type="text" name="company_name" value="{{ old('company_name') }}" required
                               class="block w-full rounded-lg bg-slate-50 border-slate-300 text-slate-800 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 transition-all">
                    </div>
                    
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Identificación Fiscal (Tax ID)</label>
                        <input type="text" name="tax_id" value="{{ old('tax_id') }}"
                               class="block w-full rounded-lg bg-slate-50 border-slate-300 text-slate-800 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 transition-all">
                    </div>

                    <div class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Dirección Fiscal</label>
                        <textarea name="address" rows="2" class="block w-full rounded-lg bg-slate-50 border-slate-300 text-slate-800 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 transition-all">{{ old('address') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Sección: Contacto Principal -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                    <div class="p-1.5 bg-emerald-50 rounded-lg text-emerald-600">
                        <i data-lucide="user-square" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Contacto Principal</h3>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Nombre Completo <span class="text-rose-500">*</span></label>
                        <input type="text" name="contact_name" value="{{ old('contact_name') }}" required
                               class="block w-full rounded-lg bg-slate-50 border-slate-300 text-slate-800 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Correo Electrónico <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                               class="block w-full rounded-lg bg-slate-50 border-slate-300 text-slate-800 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 transition-all">
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-700 uppercase tracking-wide">Teléfono</label>
                        <input type="text" name="phone" value="{{ old('phone') }}"
                               class="block w-full rounded-lg bg-slate-50 border-slate-300 text-slate-800 shadow-sm focus:bg-white focus:border-blue-500 focus:ring-blue-500 sm:text-sm py-2.5 px-3 transition-all">
                    </div>
                </div>
            </div>

            <!-- Sección: Estado -->
            <div class="space-y-4">
                <div class="flex items-center gap-2 border-b border-slate-100 pb-2">
                    <div class="p-1.5 bg-amber-50 rounded-lg text-amber-600">
                        <i data-lucide="toggle-left" class="w-5 h-5"></i>
                    </div>
                    <h3 class="font-bold text-slate-800 text-sm uppercase tracking-wide">Estado de la Cuenta</h3>
                </div>

                <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-bold text-slate-700">Acceso al Sistema</p>
                            <p class="text-xs text-slate-500 mt-0.5">Determina si el cliente puede operar y acceder al portal.</p>
                        </div>
                        <div class="flex items-center space-x-4">
                            <label class="inline-flex items-center cursor-pointer p-2 rounded-lg hover:bg-white hover:shadow-sm transition-all">
                                <input type="radio" name="is_active" value="1" checked class="text-blue-600 focus:ring-blue-500 h-4 w-4">
                                <span class="ml-2 text-sm font-medium text-slate-700">Activo</span>
                            </label>
                            <label class="inline-flex items-center cursor-pointer p-2 rounded-lg hover:bg-white hover:shadow-sm transition-all">
                                <input type="radio" name="is_active" value="0" class="text-blue-600 focus:ring-blue-500 h-4 w-4">
                                <span class="ml-2 text-sm font-medium text-slate-700">Inactivo</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="pt-6 border-t border-slate-100 flex items-center justify-end gap-3">
                <a href="{{ route('admin.clients.index') }}" class="px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 rounded-lg transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="inline-flex items-center justify-center px-6 py-2 bg-slate-900 text-white text-sm font-medium rounded-lg hover:bg-blue-600 transition-all shadow-md hover:shadow-lg focus:ring-2 focus:ring-offset-2 focus:ring-slate-900">
                    <i data-lucide="save" class="w-4 h-4 mr-2"></i>
                    Registrar Cliente
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if(typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endsection