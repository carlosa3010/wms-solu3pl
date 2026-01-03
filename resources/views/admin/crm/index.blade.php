@extends('layouts.admin')

@section('content')
<div class="p-8 space-y-8 bg-slate-50/50 min-h-screen font-sans text-slate-900">
    <!-- Encabezado de Gestión Comercial -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="space-y-1">
            <nav class="flex text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                <span>Comercial</span>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-blue-600 font-bold uppercase tracking-widest italic">Embudo de Ventas</span>
            </nav>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">CRM & Leads</h2>
            <p class="text-slate-500 font-medium text-sm">Monitoreo de prospectos y conversión de oportunidades de negocio.</p>
        </div>
        <button onclick="toggleModal('modalNuevoLead')" class="flex items-center justify-center space-x-2 bg-slate-900 text-white px-8 py-4 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-slate-200 hover:shadow-blue-200 group">
            <i data-lucide="plus-circle" class="w-5 h-5 transition-transform group-hover:scale-110"></i>
            <span>Capturar Lead</span>
        </button>
    </div>

    <!-- Sistema de Notificaciones de Éxito / Conversión -->
    @if(session('success'))
    <div class="bg-white border-l-4 border-emerald-500 p-6 rounded-2xl shadow-xl shadow-emerald-100/50 flex items-start space-x-5 animate-in fade-in slide-in-from-top-4 duration-500">
        <div class="bg-emerald-100 text-emerald-600 p-3 rounded-xl">
            <i data-lucide="check-circle" class="w-7 h-7"></i>
        </div>
        <div class="flex-1">
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1 text-emerald-800">Operación Exitosa</p>
            <p class="text-sm text-slate-800 font-bold leading-relaxed">{{ session('success') }}</p>
        </div>
        <button onclick="this.parentElement.remove()" class="text-slate-300 hover:text-slate-500 transition-colors p-2">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>
    @endif

    <!-- Tabla Operativa de Leads -->
    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900 border-b border-slate-800">
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Empresa / Origen</th>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Contacto & Seguimiento</th>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Estado Comercial</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Acciones de Ventas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($leads as $lead)
                    <tr class="hover:bg-blue-50/30 transition-all duration-200 group">
                        <!-- Entidad y Origen -->
                        <td class="px-8 py-6">
                            <div class="space-y-1.5">
                                <p class="text-sm font-black text-slate-900 group-hover:text-blue-600 transition-colors uppercase tracking-tight">{{ $lead->company_name }}</p>
                                <div class="flex items-center space-x-2">
                                    <span class="text-[9px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded tracking-widest uppercase italic">
                                        <i data-lucide="info" class="w-2.5 h-2.5 inline mr-1 -mt-0.5 text-blue-500"></i>
                                        {{ $lead->source ?? 'Origen no especificado' }}
                                    </span>
                                </div>
                            </div>
                        </td>

                        <!-- Contacto y Notas de Seguimiento -->
                        <td class="px-6 py-6 border-l border-slate-50">
                            <div class="flex flex-col space-y-2">
                                <div class="flex flex-col">
                                    <span class="text-xs font-black text-slate-800 uppercase tracking-tight">{{ $lead->contact_name }}</span>
                                    <span class="text-[11px] text-slate-400 font-bold flex items-center">
                                        <i data-lucide="mail" class="w-3 h-3 mr-1.5 opacity-50"></i>
                                        {{ $lead->email }}
                                    </span>
                                </div>
                                @if($lead->notes)
                                <div class="bg-amber-50/70 border border-amber-100 p-3 rounded-xl max-w-xs relative overflow-hidden group/note">
                                    <i data-lucide="message-square" class="absolute -right-1 -bottom-1 w-8 h-8 text-amber-200/50 -rotate-12 transition-transform group-hover/note:scale-110"></i>
                                    <p class="text-[10px] text-amber-800 font-bold italic leading-relaxed relative z-10">
                                        "{{ Str::limit($lead->notes, 100) }}"
                                    </p>
                                </div>
                                @endif
                            </div>
                        </td>

                        <!-- Estado del Embudo -->
                        <td class="px-6 py-6 text-center border-l border-slate-50">
                            <div class="flex justify-center">
                                @php
                                    $statusStyles = [
                                        'new' => 'bg-blue-50 text-blue-600 border-blue-100',
                                        'contacted' => 'bg-amber-50 text-amber-600 border-amber-100',
                                        'converted' => 'bg-emerald-50 text-emerald-600 border-emerald-100',
                                        'rejected' => 'bg-rose-50 text-rose-500 border-rose-100'
                                    ];
                                    $currentStyle = $statusStyles[$lead->status] ?? 'bg-slate-100 text-slate-500 border-slate-200';
                                @endphp
                                <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest border shadow-sm {{ $currentStyle }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current mr-2 {{ $lead->status == 'new' ? 'animate-pulse' : '' }}"></span>
                                    {{ $lead->status == 'new' ? 'Nuevo Prospecto' : ($lead->status == 'contacted' ? 'Contactado' : $lead->status) }}
                                </span>
                            </div>
                        </td>

                        <!-- Acciones -->
                        <td class="px-8 py-6 text-right border-l border-slate-50">
                            <div class="flex items-center justify-end space-x-2">
                                <!-- Botón de Conversión Crítica -->
                                <form action="{{ route('admin.crm.convert', $lead->id) }}" method="POST">
                                    @csrf
                                    <button type="submit" 
                                            onclick="return confirm('¿Desea formalizar este lead? Se creará una ficha de cliente y sus credenciales de acceso.')"
                                            class="flex items-center space-x-2 px-5 py-2.5 bg-slate-900 text-white rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-blue-600 transition-all shadow-lg shadow-slate-100 group/btn">
                                        <i data-lucide="user-check" class="w-4 h-4 transition-transform group-hover/btn:scale-110"></i>
                                        <span>Convertir a Cliente</span>
                                    </button>
                                </form>

                                <!-- Opciones Adicionales -->
                                <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                    <form action="{{ route('admin.crm.destroy', $lead->id) }}" method="POST">
                                        @csrf @method('DELETE')
                                        <button type="submit" onclick="return confirm('¿Eliminar prospecto del embudo?')" 
                                                class="p-2.5 bg-white text-slate-300 border border-slate-100 hover:text-rose-600 hover:border-rose-100 rounded-xl transition-all shadow-sm">
                                            <i data-lucide="trash-2" class="w-4 h-4 text-rose-500"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="px-8 py-32 text-center">
                            <div class="flex flex-col items-center justify-center space-y-4 max-w-sm mx-auto">
                                <div class="bg-slate-50 p-8 rounded-full border border-slate-100 shadow-inner">
                                    <i data-lucide="user-plus-2" class="w-16 h-16 text-slate-200"></i>
                                </div>
                                <div class="space-y-1">
                                    <p class="font-black uppercase tracking-[0.2em] text-xs text-slate-400">Embudo Vacío</p>
                                    <p class="text-slate-400 text-[11px] font-medium leading-relaxed italic">Inicie la captación de prospectos para ver la actividad comercial aquí.</p>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Trello-Style Responsivo -->
<div id="modalNuevoLead" class="fixed inset-0 z-[60] hidden bg-slate-900/60 backdrop-blur-sm transition-all overflow-y-auto">
    <div class="flex items-start md:items-center justify-center min-h-screen p-4">
        <div class="bg-slate-100 w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden animate-in zoom-in duration-200 border border-slate-200">
            <!-- Cabecera de Tarjeta -->
            <div class="px-6 py-5 bg-white border-b border-slate-200 flex items-start justify-between">
                <div class="flex items-start space-x-4">
                    <div class="p-2.5 bg-blue-50 text-blue-600 rounded-xl">
                        <i data-lucide="layout" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-slate-900 font-black uppercase tracking-widest text-sm italic">Nueva Ficha de Prospecto</h3>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">Destino: Entrada de Oportunidades</p>
                    </div>
                </div>
                <button onclick="toggleModal('modalNuevoLead')" class="text-slate-400 hover:text-rose-500 transition-colors p-2 hover:bg-rose-50 rounded-full">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
            
            <form action="{{ route('admin.crm.store') }}" method="POST" class="p-6 md:p-8 space-y-8">
                @csrf
                
                <!-- Sección: Información de Identidad -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-3 text-slate-600 border-b border-slate-200 pb-2">
                        <i data-lucide="building" class="w-4 h-4"></i>
                        <h4 class="text-[10px] font-black uppercase tracking-[0.15em]">Datos de la Entidad</h4>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Empresa <span class="text-rose-500">*</span></label>
                            <input type="text" name="company_name" required 
                                   class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all uppercase placeholder:text-slate-300" 
                                   placeholder="Nombre Comercial">
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Representante <span class="text-rose-500">*</span></label>
                            <input type="text" name="contact_name" required 
                                   class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all uppercase placeholder:text-slate-300" 
                                   placeholder="Persona de Contacto">
                        </div>
                    </div>
                </div>

                <!-- Sección: Contacto Directo -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-3 text-slate-600 border-b border-slate-200 pb-2">
                        <i data-lucide="phone-call" class="w-4 h-4"></i>
                        <h4 class="text-[10px] font-black uppercase tracking-[0.15em]">Canales de Enlace</h4>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Correo Electrónico <span class="text-rose-500">*</span></label>
                            <div class="relative">
                                <input type="email" name="email" required 
                                       class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all placeholder:text-slate-300" 
                                       placeholder="ejemplo@empresa.com">
                                <i data-lucide="mail" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Teléfono</label>
                            <div class="relative">
                                <input type="text" name="phone" 
                                       class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all placeholder:text-slate-300" 
                                       placeholder="+00 000 000 000">
                                <i data-lucide="phone" class="absolute left-3.5 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección: Clasificación Operativa -->
                <div class="space-y-4">
                    <div class="flex items-center space-x-3 text-slate-600 border-b border-slate-200 pb-2">
                        <i data-lucide="tag" class="w-4 h-4"></i>
                        <h4 class="text-[10px] font-black uppercase tracking-[0.15em]">Atributos del Lead</h4>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1">Fuente de Origen</label>
                            <div class="relative group">
                                <select name="source" class="w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-sm font-bold text-slate-700 shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none appearance-none cursor-pointer">
                                    <option value="Directo / Llamada">Directo / Llamada</option>
                                    <option value="Página Web">Inbound (Web)</option>
                                    <option value="Referido">Referencia Comercial</option>
                                    <option value="Redes Sociales">Campaña Social Media</option>
                                    <option value="Email Marketing">Campaña Emailing</option>
                                </select>
                                <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none group-hover:text-blue-500 transition-colors"></i>
                            </div>
                        </div>
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-black text-slate-400 uppercase tracking-widest ml-1 text-center block">Prioridad Sugerida</label>
                            <div class="flex items-center justify-center space-x-2 h-[46px]">
                                <span class="flex-1 py-2 text-center bg-blue-50 text-blue-600 rounded-lg text-[9px] font-black uppercase border border-blue-100 cursor-help shadow-sm">Media</span>
                                <span class="flex-1 py-2 text-center bg-slate-50 text-slate-400 rounded-lg text-[9px] font-black uppercase border border-slate-200 cursor-help opacity-50">Baja</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sección: Notas de Seguimiento -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between text-slate-600">
                        <div class="flex items-center space-x-3">
                            <i data-lucide="file-text" class="w-4 h-4"></i>
                            <h4 class="text-[10px] font-black uppercase tracking-[0.15em]">Notas de Negociación</h4>
                        </div>
                    </div>
                    <textarea name="notes" rows="4" 
                              class="w-full px-5 py-4 bg-white border border-slate-200 rounded-2xl text-sm font-medium text-slate-600 shadow-sm focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 outline-none transition-all placeholder:text-slate-300 resize-none" 
                              placeholder="Describe las necesidades de almacenamiento, volúmenes de carga o acuerdos específicos discutidos..."></textarea>
                </div>

                <!-- Footer de Acción Trello-esque -->
                <div class="pt-6 border-t border-slate-200 flex flex-col-reverse md:flex-row items-center justify-end gap-4">
                    <button type="button" onclick="toggleModal('modalNuevoLead')" 
                            class="w-full md:w-auto px-6 py-3 text-xs font-black text-slate-400 uppercase tracking-widest hover:text-slate-800 transition-colors">
                        Descartar
                    </button>
                    <button type="submit" 
                            class="w-full md:w-auto bg-emerald-600 text-white px-10 py-4 rounded-xl font-black text-xs uppercase tracking-widest shadow-xl shadow-emerald-100 hover:bg-emerald-700 hover:shadow-emerald-200 transition-all flex items-center justify-center space-x-3 group">
                        <span>Guardar Tarjeta de Lead</span>
                        <i data-lucide="save" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function toggleModal(id) {
        const modal = document.getElementById(id);
        if (modal.classList.contains('hidden')) {
            modal.classList.remove('hidden');
            document.body.classList.add('overflow-hidden');
        } else {
            modal.classList.add('hidden');
            document.body.classList.remove('overflow-hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endsection