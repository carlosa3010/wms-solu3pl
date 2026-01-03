@extends('layouts.client')

@section('content')
<div class="space-y-6">
    <!-- Encabezado de Bienvenida -->
    <div class="flex justify-between items-end">
        <div>
            <h2 class="text-2xl font-black text-slate-800">Panel de Control</h2>
            <p class="text-slate-500 text-sm">Resumen operativo y financiero de tu cuenta.</p>
        </div>
        <div class="text-right">
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Ciclo Actual</span>
            <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-xs font-bold">Enero 2026</span>
        </div>
    </div>

    <!-- Indicadores Principales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm border-l-4 border-l-blue-600">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">Corte de Cuenta</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">${{ number_format($corteCuenta ?? 0, 2) }}</p>
                </div>
                <div class="bg-blue-50 p-2 rounded-lg text-blue-600">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                </div>
            </div>
            <p class="text-[10px] text-emerald-600 font-bold mt-2 flex items-center">
                <i data-lucide="trending-up" class="w-3 h-3 mr-1"></i> Actualizado hoy
            </p>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">Tus SKUs</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ $skusCount ?? 0 }}</p>
                </div>
                <div class="bg-purple-50 p-2 rounded-lg text-purple-600">
                    <i data-lucide="package" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">RMAs Pendientes</p>
                    <p class="text-2xl font-black text-slate-900 mt-1 text-rose-600">{{ $pendingRmas ?? 0 }}</p>
                </div>
                <div class="bg-rose-50 p-2 rounded-lg text-rose-600">
                    <i data-lucide="rotate-ccw" class="w-5 h-5"></i>
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-xs font-bold text-slate-500 uppercase">Envíos Activos</p>
                    <p class="text-2xl font-black text-slate-900 mt-1">{{ $activeAsns ?? 0 }}</p>
                </div>
                <div class="bg-emerald-50 p-2 rounded-lg text-emerald-600">
                    <i data-lucide="truck" class="w-5 h-5"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones y Facturación -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
            <h3 class="font-bold text-slate-800 mb-6 flex items-center">
                <i data-lucide="zap" class="mr-2 text-amber-500 w-5 h-5"></i> 
                Acciones Rápidas
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <a href="{{ route('client.asn.index') }}" class="flex flex-col items-center p-6 border border-slate-100 rounded-2xl hover:bg-blue-50 hover:border-blue-200 transition-all group">
                    <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3 group-hover:bg-white">
                        <i data-lucide="plus-square" class="text-slate-400 group-hover:text-blue-600"></i>
                    </div>
                    <span class="text-sm font-bold text-slate-600">Crear ASN</span>
                </a>
                
                <a href="{{ route('client.orders.create') }}" class="flex flex-col items-center p-6 border border-slate-100 rounded-2xl hover:bg-emerald-50 hover:border-emerald-200 transition-all group">
                    <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3 group-hover:bg-white">
                        <i data-lucide="shopping-cart" class="text-slate-400 group-hover:text-emerald-600"></i>
                    </div>
                    <span class="text-sm font-bold text-slate-600">Nuevo Pedido</span>
                </a>

                <a href="{{ route('client.catalog') }}" class="flex flex-col items-center p-6 border border-slate-100 rounded-2xl hover:bg-purple-50 hover:border-purple-200 transition-all group">
                    <div class="w-12 h-12 bg-slate-50 rounded-full flex items-center justify-center mb-3 group-hover:bg-white">
                        <i data-lucide="package-plus" class="text-slate-400 group-hover:text-purple-600"></i>
                    </div>
                    <span class="text-sm font-bold text-slate-600">Gestionar SKU</span>
                </a>
            </div>
        </div>

        <!-- Módulo de Descarga de Prefactura -->
        <div class="bg-slate-900 text-white rounded-3xl p-8 shadow-2xl relative overflow-hidden">
            <div class="relative z-10">
                <h3 class="font-bold text-xl mb-2">Estado de Cuenta</h3>
                <p class="text-slate-400 text-sm mb-8">Descarga tu pre-factura del ciclo en curso para revisión.</p>
                
                <div class="p-4 bg-white/5 rounded-2xl border border-white/10 mb-8">
                    <div class="flex justify-between items-center mb-2">
                        <span class="text-xs text-slate-400">Total acumulado</span>
                        <span class="text-lg font-black text-blue-400">${{ number_format($corteCuenta ?? 0, 2) }}</span>
                    </div>
                    <div class="w-full bg-white/10 h-1.5 rounded-full">
                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: 65%"></div>
                    </div>
                </div>

                <a href="{{ route('client.billing.download') }}" class="w-full py-4 bg-blue-600 hover:bg-blue-700 rounded-2xl font-black flex items-center justify-center space-x-2 transition-all shadow-lg shadow-blue-900/40">
                    <i data-lucide="download-cloud"></i>
                    <span>Descargar Prefactura</span>
                </a>
            </div>
            <!-- Decoración -->
            <div class="absolute -right-10 -bottom-10 w-40 h-40 bg-blue-500/10 rounded-full blur-3xl"></div>
        </div>
    </div>
</div>
@endsection