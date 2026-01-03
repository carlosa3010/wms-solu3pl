@extends('layouts.admin')

@section('title', 'Configuración de Sistema')
@section('header_title', 'Configuración Global')

@section('content')
    @php
        // Extraer logos específicos para previsualización local en el formulario
        $siteLogo = \App\Models\Setting::get('site_logo');
        $reportLogo = \App\Models\Setting::get('report_logo');
        $siteFavicon = \App\Models\Setting::get('site_favicon');
    @endphp

    <!-- Mensajes de Éxito -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm animate-fade-in flex items-center gap-3">
            <i class="fa-solid fa-check-circle text-custom-primary"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data" id="settingsForm">
        @csrf
        <!-- Inputs ocultos para marcar eliminación de archivos cargados -->
        <input type="hidden" name="clear_site_logo" id="clear_site_logo" value="0">
        <input type="hidden" name="clear_report_logo" id="clear_report_logo" value="0">
        <input type="hidden" name="clear_site_favicon" id="clear_site_favicon" value="0">

        <div class="flex flex-col lg:flex-row gap-8">
            
            <!-- Pestañas Laterales de Navegación -->
            <div class="w-full lg:w-1/4 space-y-2">
                <button type="button" onclick="showTab('branding')" id="btn-branding" class="tab-btn active w-full text-left px-4 py-3 rounded-lg font-bold transition flex items-center gap-3">
                    <i class="fa-solid fa-palette"></i> Identidad Visual
                </button>
                <button type="button" onclick="showTab('company')" id="btn-company" class="tab-btn w-full text-left px-4 py-3 rounded-lg font-bold transition flex items-center gap-3 text-slate-500 hover:bg-white hover:text-custom-primary">
                    <i class="fa-solid fa-building"></i> Datos de Empresa
                </button>
                <button type="button" onclick="showTab('mail')" id="btn-mail" class="tab-btn w-full text-left px-4 py-3 rounded-lg font-bold transition flex items-center gap-3 text-slate-500 hover:bg-white hover:text-custom-primary">
                    <i class="fa-solid fa-envelope-open-text"></i> Servidor SMTP
                </button>
                <button type="button" onclick="showTab('system')" id="btn-system" class="tab-btn w-full text-left px-4 py-3 rounded-lg font-bold transition flex items-center gap-3 text-slate-500 hover:bg-white hover:text-custom-primary">
                    <i class="fa-solid fa-code"></i> API & Dominio
                </button>

                <div class="pt-6">
                    <!-- Botón de Guardado Centralizado -->
                    <button type="submit" class="w-full bg-custom-primary text-white py-3 rounded-xl font-bold shadow-lg shadow-black/10 hover-bg-primary transition flex items-center justify-center gap-2 uppercase tracking-tighter text-sm">
                        <i class="fa-solid fa-save"></i> Guardar Cambios
                    </button>
                </div>
            </div>

            <!-- Contenedor de Contenido de Pestañas -->
            <div class="w-full lg:w-3/4">
                
                <!-- SECCIÓN: BRANDING -->
                <div id="tab-branding" class="tab-content space-y-6">
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
                        <h3 class="text-lg font-bold text-slate-800 mb-6 border-b pb-2 flex items-center justify-between">
                            <span>Colores e Imagen Corporativa</span>
                            <span class="text-[10px] text-slate-400 font-normal uppercase tracking-widest italic">Personalización</span>
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-8">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Color Primario (UI Elements)</label>
                                <div class="flex gap-2">
                                    <input type="color" name="primary_color" value="{{ $settings['primary_color'] ?? '#1d4ed8' }}" class="h-10 w-20 rounded border cursor-pointer shadow-sm">
                                    <input type="text" value="{{ $settings['primary_color'] ?? '#1d4ed8' }}" class="flex-1 px-4 py-2 border rounded-lg bg-slate-50 font-mono text-xs" readonly>
                                </div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase tracking-wider mb-2">Fondo del Menú Lateral</label>
                                <div class="flex gap-2">
                                    <input type="color" name="sidebar_color" value="{{ $settings['sidebar_color'] ?? '#0f172a' }}" class="h-10 w-20 rounded border cursor-pointer shadow-sm">
                                    <input type="text" value="{{ $settings['sidebar_color'] ?? '#0f172a' }}" class="flex-1 px-4 py-2 border rounded-lg bg-slate-50 font-mono text-xs" readonly>
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <h4 class="text-xs font-bold text-slate-700 mb-6 uppercase tracking-tight">Gestión de Logotipos y Iconos</h4>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Logo Sidebar -->
                                <div class="space-y-3">
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase">Logo Principal (PNG)</label>
                                    <div class="relative bg-slate-50 border border-dashed border-slate-300 rounded-xl p-4 h-32 flex flex-col items-center justify-center overflow-hidden">
                                        @if($siteLogo)
                                            <img src="{{ $siteLogo }}" class="max-h-20 w-auto object-contain mb-2">
                                            <button type="button" onclick="confirmClear('site_logo')" class="absolute top-1 right-1 bg-red-100 text-red-600 p-1.5 rounded-full hover:bg-red-200 transition">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                            </button>
                                        @else
                                            <i class="fa-solid fa-image text-slate-300 text-3xl"></i>
                                        @endif
                                    </div>
                                    <input type="file" name="site_logo" class="w-full text-[10px] text-slate-500 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-custom-primary file:text-white">
                                </div>

                                <!-- Logo PDF -->
                                <div class="space-y-3">
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase">Logo para Reportes</label>
                                    <div class="relative bg-slate-50 border border-dashed border-slate-300 rounded-xl p-4 h-32 flex flex-col items-center justify-center overflow-hidden">
                                        @if($reportLogo)
                                            <img src="{{ $reportLogo }}" class="max-h-20 w-auto object-contain mb-2">
                                            <button type="button" onclick="confirmClear('report_logo')" class="absolute top-1 right-1 bg-red-100 text-red-600 p-1.5 rounded-full hover:bg-red-200 transition">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                            </button>
                                        @else
                                            <i class="fa-solid fa-file-pdf text-slate-300 text-3xl"></i>
                                        @endif
                                    </div>
                                    <input type="file" name="report_logo" class="w-full text-[10px] text-slate-500 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-custom-primary file:text-white">
                                </div>

                                <!-- Favicon -->
                                <div class="space-y-3">
                                    <label class="block text-[9px] font-bold text-slate-500 uppercase">Favicon (.ico)</label>
                                    <div class="relative bg-slate-50 border border-dashed border-slate-300 rounded-xl p-4 h-32 flex flex-col items-center justify-center overflow-hidden">
                                        @if($siteFavicon)
                                            <img src="{{ $siteFavicon }}" class="w-8 h-8 mb-2 rounded border shadow-sm">
                                            <button type="button" onclick="confirmClear('site_favicon')" class="absolute top-1 right-1 bg-red-100 text-red-600 p-1.5 rounded-full hover:bg-red-200 transition">
                                                <i class="fa-solid fa-trash-can text-[10px]"></i>
                                            </button>
                                        @else
                                            <i class="fa-solid fa-earth-americas text-slate-300 text-3xl"></i>
                                        @endif
                                    </div>
                                    <input type="file" name="site_favicon" class="w-full text-[10px] text-slate-500 file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-custom-primary file:text-white">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN: DATOS DE EMPRESA -->
                <div id="tab-company" class="tab-content hidden space-y-6">
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
                        <h3 class="text-lg font-bold text-slate-800 mb-6 border-b pb-2">Información de la Organización</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Nombre / Razón Social</label>
                                <input type="text" name="company_name" value="{{ $settings['company_name'] ?? '' }}" class="w-full px-4 py-2 border rounded-lg focus:ring-2 ring-custom-primary outline-none" placeholder="Solu3PL Logística C.A.">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Identificación Fiscal (RIF)</label>
                                <input type="text" name="company_tax_id" value="{{ $settings['company_tax_id'] ?? '' }}" class="w-full px-4 py-2 border rounded-lg font-mono" placeholder="J-12345678-0">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Teléfono de Oficina</label>
                                <input type="text" name="company_phone" value="{{ $settings['company_phone'] ?? '' }}" class="w-full px-4 py-2 border rounded-lg" placeholder="+58 212 000 0000">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Dirección Fiscal</label>
                                <textarea name="company_address" rows="3" class="w-full px-4 py-2 border rounded-lg focus:ring-2 ring-custom-primary outline-none">{{ $settings['company_address'] ?? '' }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN: CORREO (SMTP) -->
                <div id="tab-mail" class="tab-content hidden space-y-6">
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
                        <h3 class="text-lg font-bold text-slate-800 mb-6 border-b pb-2">Configuración de Envío de Email</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Servidor SMTP</label>
                                <input type="text" name="mail_host" value="{{ $settings['mail_host'] ?? '' }}" class="w-full px-4 py-2 border rounded-lg font-mono text-xs" placeholder="smtp.mailtrap.io">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Puerto</label>
                                <input type="text" name="mail_port" value="{{ $settings['mail_port'] ?? '587' }}" class="w-full px-4 py-2 border rounded-lg font-mono text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Usuario SMTP</label>
                                <input type="text" name="mail_username" value="{{ $settings['mail_username'] ?? '' }}" class="w-full px-4 py-2 border rounded-lg font-mono text-xs">
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Password SMTP</label>
                                <input type="password" name="mail_password" value="{{ $settings['mail_password'] ?? '' }}" class="w-full px-4 py-2 border rounded-lg font-mono text-xs">
                            </div>
                            <div class="col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Email Remitente (Default)</label>
                                <input type="email" name="mail_from_address" value="{{ $settings['mail_from_address'] ?? '' }}" class="w-full px-4 py-2 border rounded-lg text-xs" placeholder="no-reply@solu3pl.com">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECCIÓN: SISTEMA / AVANZADO -->
                <div id="tab-system" class="tab-content hidden space-y-6">
                    <div class="bg-white p-8 rounded-xl shadow-sm border border-slate-200">
                        <h3 class="text-lg font-bold text-slate-800 mb-6 border-b pb-2 flex items-center gap-2">
                            <span>Parámetros de Entorno</span>
                            <span class="text-[9px] bg-red-100 text-red-600 px-2 py-0.5 rounded font-bold uppercase tracking-widest">Crítico</span>
                        </h3>
                        <div class="space-y-6">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-2">Dominio Base de la Aplicación</label>
                                <input type="url" name="app_domain" value="{{ $settings['app_domain'] ?? 'https://wms.solu3pl.com' }}" class="w-full px-4 py-2 border rounded-lg font-mono text-custom-primary font-bold">
                                <p class="text-[10px] text-slate-400 mt-2">Este dominio se utiliza para generar códigos QR y enlaces de seguimiento.</p>
                            </div>
                            <div class="p-4 bg-amber-50 border border-amber-200 rounded-lg flex gap-3">
                                <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-1"></i>
                                <p class="text-xs text-amber-800 leading-relaxed font-medium">
                                    Tenga cuidado al modificar estos valores. Configuraciones erróneas pueden causar que las imágenes no carguen o que los correos electrónicos sean rechazados por los servidores de destino.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </form>
@endsection

@section('styles')
<style>
    /* Estilos para el estado activo de las pestañas */
    .tab-btn.active {
        background-color: white !important;
        color: var(--primary-color) !important;
        border-left: 4px solid var(--primary-color) !important;
        box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1);
    }
</style>
@endsection

@section('scripts')
<script>
    /**
     * Gestión de pestañas laterales
     */
    function showTab(tabId) {
        // Ocultar todos los contenidos
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        // Mostrar el seleccionado
        document.getElementById('tab-' + tabId).classList.remove('hidden');

        // Resetear botones
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
            btn.classList.add('text-slate-500');
        });

        // Activar el actual
        const activeBtn = document.getElementById('btn-' + tabId);
        activeBtn.classList.add('active');
        activeBtn.classList.remove('text-slate-500');
    }

    /**
     * Marcar archivo para eliminación en el servidor
     */
    function confirmClear(key) {
        if(confirm('¿Desea eliminar este archivo gráfico? Se restaurará el valor por defecto tras guardar.')) {
            document.getElementById('clear_' + key).value = "1";
            // Efecto visual de borrado
            const btn = event.currentTarget;
            const parent = btn.parentElement;
            parent.style.opacity = "0.3";
            parent.classList.add('grayscale');
            btn.remove();
        }
    }
</script>
@endsection