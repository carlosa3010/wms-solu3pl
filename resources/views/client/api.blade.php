@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-black text-slate-800">API para Desarrolladores</h2>
            <p class="text-sm text-slate-500">Integra tus sistemas ERP/Ecommerce directamente con nuestro WMS.</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="px-3 py-1 bg-emerald-100 text-emerald-700 rounded-full text-xs font-bold uppercase tracking-wider flex items-center">
                <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2 animate-pulse"></span>
                API v1.0 Online
            </span>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Columna Izquierda: Gestión de Tokens -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Tarjeta de Token Actual -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-bold text-slate-800 mb-4 flex items-center">
                    <i data-lucide="key" class="w-5 h-5 mr-2 text-blue-600"></i>
                    Credenciales de Acceso
                </h3>
                
                <div class="bg-slate-900 rounded-xl p-4 mb-6 relative group">
                    <label class="text-[10px] text-slate-400 font-bold uppercase tracking-widest block mb-1">Tu API Key (Producción)</label>
                    <div class="flex items-center justify-between">
                        <code class="text-emerald-400 font-mono text-sm truncate pr-4">sk_live_51Mz...9sTx (Oculto)</code>
                        <button class="text-slate-400 hover:text-white transition-colors" title="Copiar">
                            <i data-lucide="copy" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-6">
                    <div class="flex items-start">
                        <i data-lucide="info" class="w-5 h-5 text-blue-600 mt-0.5 mr-3 flex-shrink-0"></i>
                        <p class="text-sm text-blue-800">
                            <strong>Nota de Seguridad:</strong> Este token otorga acceso completo a tu inventario y pedidos. No lo compartas en repositorios públicos.
                        </p>
                    </div>
                </div>

                <div class="flex gap-3">
                    <button class="px-4 py-2 bg-white border border-slate-300 text-slate-700 font-bold rounded-xl text-sm hover:bg-slate-50 transition-all">
                        Regenerar Key
                    </button>
                    <button class="px-4 py-2 bg-blue-600 text-white font-bold rounded-xl text-sm hover:bg-blue-700 transition-all shadow-lg shadow-blue-100">
                        Crear Token Sandbox
                    </button>
                </div>
            </div>

            <!-- Endpoints Comunes -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700 text-sm">Endpoints Más Utilizados</h3>
                    <a href="#" class="text-blue-600 text-xs font-bold hover:underline">Ver referencia completa →</a>
                </div>
                <div class="divide-y divide-slate-100">
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors cursor-pointer group">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-1 bg-green-100 text-green-700 rounded text-[10px] font-black w-14 text-center">GET</span>
                            <span class="font-mono text-xs text-slate-600">/api/v1/inventory</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 group-hover:text-blue-500"></i>
                    </div>
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors cursor-pointer group">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-[10px] font-black w-14 text-center">POST</span>
                            <span class="font-mono text-xs text-slate-600">/api/v1/orders/create</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 group-hover:text-blue-500"></i>
                    </div>
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors cursor-pointer group">
                        <div class="flex items-center gap-3">
                            <span class="px-2 py-1 bg-purple-100 text-purple-700 rounded text-[10px] font-black w-14 text-center">WEBHOOK</span>
                            <span class="font-mono text-xs text-slate-600">order.status_changed</span>
                        </div>
                        <i data-lucide="chevron-right" class="w-4 h-4 text-slate-300 group-hover:text-blue-500"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Derecha: Documentación -->
        <div class="space-y-6">
            <div class="bg-gradient-to-br from-slate-800 to-slate-900 text-white rounded-2xl p-6 shadow-xl relative overflow-hidden">
                <div class="relative z-10">
                    <div class="w-12 h-12 bg-white/10 rounded-xl flex items-center justify-center mb-4">
                        <i data-lucide="book-open" class="w-6 h-6 text-blue-400"></i>
                    </div>
                    <h3 class="font-bold text-lg mb-2">Documentación Oficial</h3>
                    <p class="text-slate-400 text-sm mb-6 leading-relaxed">
                        Accede a ejemplos de código en PHP, Python y Node.js, esquemas de respuesta y entorno de pruebas Swagger.
                    </p>
                    <a href="#" class="block w-full py-3 bg-blue-600 hover:bg-blue-500 text-white text-center rounded-xl font-bold text-sm transition-all">
                        Ir a la Documentación
                    </a>
                </div>
                <!-- Decoración -->
                <div class="absolute -top-10 -right-10 w-32 h-32 bg-blue-500/20 rounded-full blur-2xl"></div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h4 class="font-bold text-slate-800 text-sm mb-4">Librerías SDK</h4>
                <div class="space-y-3">
                    <a href="#" class="flex items-center p-3 border border-slate-100 rounded-xl hover:bg-slate-50 transition-colors group">
                        <i class="fa-brands fa-php text-slate-400 text-xl w-8 text-center group-hover:text-indigo-600 transition-colors"></i>
                        <span class="text-sm font-medium text-slate-600 ml-2">PHP Client</span>
                    </a>
                    <a href="#" class="flex items-center p-3 border border-slate-100 rounded-xl hover:bg-slate-50 transition-colors group">
                        <i class="fa-brands fa-js text-slate-400 text-xl w-8 text-center group-hover:text-yellow-500 transition-colors"></i>
                        <span class="text-sm font-medium text-slate-600 ml-2">Node.js Client</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection