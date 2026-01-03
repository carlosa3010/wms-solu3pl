@extends('layouts.admin')

@section('content')
<div class="p-8 space-y-8 bg-slate-50/50 min-h-screen font-sans">
    <!-- Encabezado Operativo -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="space-y-1">
            <nav class="flex text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                <span>Administración</span>
                <span class="mx-2 text-slate-300">/</span>
                <a href="{{ route('admin.clients.index') }}" class="hover:text-blue-600 transition-colors">Clientes</a>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-blue-600 italic">Alta de Cuenta</span>
            </nav>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">Registro de Nuevo Cliente</h2>
            <p class="text-slate-500 font-medium text-sm">Apertura de ficha técnica y habilitación de credenciales para el portal.</p>
        </div>
        <a href="{{ route('admin.clients.index') }}" class="flex items-center justify-center space-x-2 bg-white text-slate-600 border border-slate-200 px-6 py-3.5 rounded-2xl font-bold hover:bg-slate-50 transition-all shadow-sm">
            <i data-lucide="arrow-left" class="w-5 h-5"></i>
            <span>Volver al Listado</span>
        </a>
    </div>

    <div class="max-w-4xl mx-auto">
        <!-- Bloque de Errores de Validación -->
        @if ($errors->any())
            <div class="mb-8 bg-rose-50 border-l-4 border-rose-500 p-5 rounded-2xl shadow-sm animate-in fade-in slide-in-from-top-4">
                <div class="flex">
                    <i data-lucide="alert-triangle" class="w-6 h-6 text-rose-500 mr-4"></i>
                    <div>
                        <p class="text-sm font-black text-rose-800 uppercase tracking-widest mb-1">Revisión Requerida</p>
                        <ul class="text-xs text-rose-700 font-bold list-disc list-inside space-y-1">
                            @foreach ($errors->all() as $error) <li>{{ $error }}</li> @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <form action="{{ route('admin.clients.store') }}" method="POST" class="space-y-6">
            @csrf
            
            <!-- Sección 01: Información Corporativa -->
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-slate-900 px-8 py-5 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center">
                        <i data-lucide="building-2" class="w-4 h-4 mr-3 text-blue-500"></i>
                        Información de la Entidad
                    </h3>
                    <span class="text-[9px] font-black bg-blue-500/10 text-blue-400 px-2 py-0.5 rounded tracking-widest uppercase">Paso 01</span>
                </div>
                
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre Comercial / Empresa <span class="text-rose-500">*</span></label>
                        <input type="text" name="company_name" required value="{{ old('company_name') }}"
                               class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 outline-none focus:border-blue-500 transition-all uppercase placeholder:text-slate-300"
                               placeholder="RAZÓN SOCIAL COMPLETA">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Identificación Fiscal (TAX ID) <span class="text-rose-500">*</span></label>
                        <input type="text" name="tax_id" required value="{{ old('tax_id') }}"
                               class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 outline-none focus:border-blue-500 transition-all placeholder:text-slate-300"
                               placeholder="RUC / NIT / RFC">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Teléfono Principal</label>
                        <div class="relative">
                            <input type="text" name="phone" value="{{ old('phone') }}"
                                   class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 outline-none focus:border-blue-500 transition-all placeholder:text-slate-300 font-mono"
                                   placeholder="+00 000 000 000">
                            <i data-lucide="phone" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300"></i>
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Dirección</label>
                        <div class="relative">
                            <input type="text" name="address" value="{{ old('address') }}"
                                   class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 outline-none focus:border-blue-500 transition-all placeholder:text-slate-300"
                                   placeholder="Ciudad, Estado, Dirección">
                            <i data-lucide="map-pin" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sección 02: Seguridad y Contacto -->
            <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
                <div class="bg-slate-900 px-8 py-5 border-b border-slate-800 flex items-center justify-between">
                    <h3 class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] flex items-center">
                        <i data-lucide="shield-check" class="w-4 h-4 mr-3 text-emerald-500"></i>
                        Punto de Contacto & Acceso
                    </h3>
                    <span class="text-[9px] font-black bg-emerald-500/10 text-emerald-400 px-2 py-0.5 rounded tracking-widest uppercase">Paso 02</span>
                </div>
                
                <div class="p-8 grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Representante de Cuenta <span class="text-rose-500">*</span></label>
                        <input type="text" name="contact_name" required value="{{ old('contact_name') }}"
                               class="w-full px-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 outline-none focus:border-blue-500 transition-all uppercase placeholder:text-slate-300"
                               placeholder="NOMBRE DEL RESPONSABLE">
                    </div>

                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Email Corporativo (Login Portal) <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <input type="email" name="email" required value="{{ old('email') }}"
                                   class="w-full pl-12 pr-5 py-4 bg-slate-50 border border-slate-200 rounded-2xl text-sm font-bold text-slate-700 outline-none focus:border-blue-500 transition-all placeholder:text-slate-300 font-mono"
                                   placeholder="usuario@empresa.com">
                            <i data-lucide="mail" class="absolute left-4 top-1/2 -translate-y-1/2 w-5 h-5 text-slate-300"></i>
                        </div>
                    </div>

                    <!-- Cuadro Informativo de Seguridad -->
                    <div class="md:col-span-2 bg-emerald-50 border border-emerald-100 rounded-2xl p-6 flex items-start space-x-4">
                        <div class="bg-emerald-500 text-white p-2 rounded-xl shadow-lg shadow-emerald-200 flex-shrink-0">
                            <i data-lucide="key-round" class="w-6 h-6"></i>
                        </div>
                        <div class="space-y-1">
                            <p class="text-[11px] font-black text-emerald-800 uppercase tracking-widest">Seguridad Automatizada</p>
                            <p class="text-xs text-emerald-700 font-bold leading-relaxed">
                                El sistema generará una clave de acceso <span class="underline">aleatoria</span> y segura tras confirmar el registro. Podrá visualizarla inmediatamente en el listado principal para entregarla al cliente.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones de Acción -->
            <div class="flex items-center justify-end space-x-6 pt-4 pb-12">
                <button type="reset" class="text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">
                    Limpiar Formulario
                </button>
                <button type="submit" class="bg-slate-900 text-white px-16 py-5.5 rounded-[2rem] font-black text-base uppercase tracking-[0.15em] hover:bg-blue-600 transition-all shadow-2xl shadow-slate-300 hover:shadow-blue-200 flex items-center space-x-5 group">
                    <span>Confirmar Alta Operativa</span>
                    <i data-lucide="arrow-right-circle" class="w-7 h-7 transition-transform group-hover:translate-x-2"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endsection