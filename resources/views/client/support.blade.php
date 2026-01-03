@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800">Centro de Ayuda</h2>
            <p class="text-sm text-slate-500">¿Tienes problemas? Abre un ticket o contáctanos directamente.</p>
        </div>
        <button class="flex items-center justify-center space-x-2 bg-blue-600 text-white px-5 py-2.5 rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>Crear Nuevo Ticket</span>
        </button>
    </div>

    <!-- Canales de Contacto -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <i data-lucide="phone" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Línea Urgente</p>
                <p class="text-lg font-black text-slate-800">+52 (55) 1234-5678</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <i data-lucide="message-circle" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">WhatsApp Soporte</p>
                <p class="text-lg font-black text-slate-800">Chat en Vivo</p>
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm flex items-center space-x-4">
            <div class="w-12 h-12 bg-purple-50 text-purple-600 rounded-xl flex items-center justify-center flex-shrink-0">
                <i data-lucide="mail" class="w-6 h-6"></i>
            </div>
            <div>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Email</p>
                <p class="text-lg font-black text-slate-800">soporte@wms.com</p>
            </div>
        </div>
    </div>

    <!-- Área Principal: Tickets y FAQ -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Lista de Tickets -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                <h3 class="font-bold text-slate-700">Mis Tickets Recientes</h3>
                <div class="flex gap-2">
                    <select class="text-xs border border-slate-200 rounded-lg px-2 py-1 bg-white focus:outline-none focus:border-blue-500">
                        <option>Todos</option>
                        <option>Abiertos</option>
                        <option>Cerrados</option>
                    </select>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                <!-- Ticket Item Demo 1 -->
                <div class="p-6 hover:bg-slate-50 transition-colors cursor-pointer group">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-mono text-slate-400">#TK-8832</span>
                            <span class="bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">En Proceso</span>
                        </div>
                        <span class="text-xs text-slate-400">Hace 2 horas</span>
                    </div>
                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-blue-600 transition-colors">Error al sincronizar inventario vía API</h4>
                    <p class="text-xs text-slate-500 mt-1">El endpoint devuelve error 500 cuando intento actualizar SKU...</p>
                </div>

                <!-- Ticket Item Demo 2 -->
                <div class="p-6 hover:bg-slate-50 transition-colors cursor-pointer group">
                    <div class="flex justify-between items-start mb-2">
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-mono text-slate-400">#TK-8810</span>
                            <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase">Resuelto</span>
                        </div>
                        <span class="text-xs text-slate-400">Ayer</span>
                    </div>
                    <h4 class="font-bold text-slate-800 text-sm group-hover:text-blue-600 transition-colors">Solicitud de alta de nueva sucursal</h4>
                    <p class="text-xs text-slate-500 mt-1">Necesitamos activar la bodega Norte para envíos...</p>
                </div>

                <!-- Empty State (comentado para demo) -->
                <!-- 
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="check-circle" class="w-8 h-8 text-slate-300"></i>
                    </div>
                    <p class="text-slate-500 font-medium text-sm">No tienes tickets pendientes.</p>
                </div>
                -->
            </div>
            
            <div class="p-4 bg-slate-50 border-t border-slate-100 text-center">
                <a href="#" class="text-xs font-bold text-blue-600 hover:text-blue-800">Ver historial completo</a>
            </div>
        </div>

        <!-- FAQ Sidebar -->
        <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm h-fit">
            <h3 class="font-bold text-slate-800 mb-4 flex items-center">
                <i data-lucide="help-circle" class="w-5 h-5 mr-2 text-slate-400"></i>
                Preguntas Frecuentes
            </h3>
            
            <div class="space-y-4">
                <details class="group [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex cursor-pointer items-center justify-between gap-1.5 text-slate-900">
                        <h4 class="text-xs font-bold group-hover:text-blue-600 transition-colors">¿Cómo descargo mi factura XML?</h4>
                        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 transition duration-300 group-open:-rotate-180 text-slate-400"></i>
                    </summary>
                    <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                        Ve a la sección de "Facturación" en el menú principal. Allí encontrarás un botón de descarga para PDF y XML.
                    </p>
                </details>

                <div class="h-px bg-slate-100"></div>

                <details class="group [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex cursor-pointer items-center justify-between gap-1.5 text-slate-900">
                        <h4 class="text-xs font-bold group-hover:text-blue-600 transition-colors">¿Tiempos de corte para envíos?</h4>
                        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 transition duration-300 group-open:-rotate-180 text-slate-400"></i>
                    </summary>
                    <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                        Los pedidos recibidos antes de las 13:00 hrs se procesan el mismo día. Sábados hasta las 11:00 hrs.
                    </p>
                </details>

                <div class="h-px bg-slate-100"></div>

                <details class="group [&_summary::-webkit-details-marker]:hidden">
                    <summary class="flex cursor-pointer items-center justify-between gap-1.5 text-slate-900">
                        <h4 class="text-xs font-bold group-hover:text-blue-600 transition-colors">Devoluciones (RMA)</h4>
                        <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 transition duration-300 group-open:-rotate-180 text-slate-400"></i>
                    </summary>
                    <p class="mt-2 text-xs text-slate-500 leading-relaxed">
                        Debes generar una solicitud RMA desde el panel antes de enviar la mercancía de regreso a la bodega.
                    </p>
                </details>
            </div>
        </div>

    </div>
</div>
@endsection