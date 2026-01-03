@extends('layouts.admin')

@section('title', 'Panel de Control')
@section('header_title', 'Resumen Ejecutivo')

@section('content')
    <!-- Bienvenida y Contexto -->
    <div class="mb-8">
        <h2 class="text-xl md:text-2xl font-bold text-slate-800">Hola, {{ Auth::user()->name }}</h2>
        <p class="text-sm text-slate-500">Aquí tienes el estado actual de tu operación logística a tiempo real.</p>
    </div>

    <!-- Rejilla de Indicadores (KPIs Comerciales) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8">
        <!-- Ingresos -->
        <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-green-500 hover:shadow-md transition group">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Ingresos Proyectados</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1">$ {{ number_format($revenue ?? 0, 2) }}</h3>
                </div>
                <div class="p-2 bg-green-50 rounded text-green-600 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-dollar-sign"></i>
                </div>
            </div>
        </div>
        
        <!-- Pedidos Totales -->
        <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-custom-primary hover:shadow-md transition group">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pedidos Mes</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ $totalOrders ?? 0 }}</h3>
                </div>
                <div class="p-2 bg-blue-50 rounded text-custom-primary group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-cart-shopping"></i>
                </div>
            </div>
        </div>

        <!-- SKUs en Catálogo -->
        <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-purple-500 hover:shadow-md transition group">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Productos (SKUs)</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ $totalProducts ?? 0 }}</h3>
                </div>
                <div class="p-2 bg-purple-50 rounded text-purple-600 group-hover:scale-110 transition-transform">
                    <i class="fa-solid fa-tags"></i>
                </div>
            </div>
        </div>

        <!-- Pendientes de Despacho -->
        <div class="bg-white p-6 rounded-xl shadow-sm border-l-4 border-red-500 hover:shadow-md transition group">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Pendientes</p>
                    <h3 class="text-2xl font-bold text-slate-800 mt-1">{{ $pendingOrders ?? 0 }}</h3>
                </div>
                <div class="p-2 bg-red-50 rounded text-red-600 group-hover:scale-110 transition-transform animate-pulse">
                    <i class="fa-solid fa-clock"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- NUEVA SECCIÓN: KPIs DE ALMACENAMIENTO -->
    <div class="mb-8">
        <h3 class="font-bold text-slate-700 text-lg mb-4 flex items-center gap-2">
            <i class="fa-solid fa-cubes-stacked text-custom-primary"></i> Capacidad de Almacenamiento
        </h3>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:gap-6">
            
            <!-- Capacidad Total -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Total Bines (Instalados)</p>
                    <h4 class="text-3xl font-bold text-slate-700 mt-1">{{ number_format($totalBins ?? 0) }}</h4>
                </div>
                <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400">
                    <i class="fa-solid fa-warehouse text-xl"></i>
                </div>
            </div>

            <!-- Disponible -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Espacios Disponibles</p>
                    <h4 class="text-3xl font-bold text-emerald-500 mt-1">{{ number_format($availableBins ?? 0) }}</h4>
                </div>
                <div class="w-12 h-12 rounded-full bg-emerald-50 flex items-center justify-center text-emerald-500">
                    <i class="fa-solid fa-check-circle text-xl"></i>
                </div>
            </div>

            <!-- Ocupación -->
            <div class="bg-white p-5 rounded-xl shadow-sm border border-slate-200 relative overflow-hidden group">
                <div class="relative z-10 flex justify-between items-center">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Ocupación Actual</p>
                        <div class="flex items-baseline gap-2 mt-1">
                            <h4 class="text-3xl font-bold text-blue-600">{{ number_format($occupiedBins ?? 0) }}</h4>
                            <span class="text-xs font-bold text-slate-400 bg-slate-100 px-2 py-1 rounded-full">{{ $occupancyRate ?? 0 }}%</span>
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-full bg-blue-50 flex items-center justify-center text-blue-500">
                        <i class="fa-solid fa-box-open text-xl"></i>
                    </div>
                </div>
                <!-- Barra de progreso sutil al fondo -->
                <div class="absolute bottom-0 left-0 h-1 bg-blue-100 w-full">
                    <div class="h-full bg-blue-500 transition-all duration-1000" style="width: {{ $occupancyRate ?? 0 }}%"></div>
                </div>
            </div>

        </div>
    </div>

    <!-- Sección de Accesos Directos y Gráficas Rápidas -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Acciones Rápidas -->
        <div class="lg:col-span-1 bg-white rounded-xl shadow-sm p-6 border border-slate-200">
            <h3 class="font-bold text-slate-700 mb-6 flex items-center gap-2">
                <i class="fa-solid fa-bolt text-yellow-500"></i> Acciones Directas
            </h3>
            <div class="grid grid-cols-1 gap-3">
                <a href="{{ route('admin.clients.create') }}" class="flex items-center gap-4 p-3 bg-slate-50 rounded-lg border border-slate-200 hover:border-custom-primary hover:bg-blue-50 transition group">
                    <div class="w-10 h-10 bg-white rounded flex items-center justify-center text-slate-400 group-hover:text-custom-primary shadow-sm">
                        <i class="fa-solid fa-user-plus"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-700">Nuevo Cliente</p>
                        <p class="text-[10px] text-slate-400">Registrar socio comercial</p>
                    </div>
                </a>

                <a href="{{ route('admin.products.create') }}" class="flex items-center gap-4 p-3 bg-slate-50 rounded-lg border border-slate-200 hover:border-custom-primary hover:bg-blue-50 transition group">
                    <div class="w-10 h-10 bg-white rounded flex items-center justify-center text-slate-400 group-hover:text-custom-primary shadow-sm">
                        <i class="fa-solid fa-barcode"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-700">Registrar SKU</p>
                        <p class="text-[10px] text-slate-400">Crear producto en catálogo</p>
                    </div>
                </a>

                <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-4 p-3 bg-slate-50 rounded-lg border border-slate-200 hover:border-custom-primary hover:bg-blue-50 transition group">
                    <div class="w-10 h-10 bg-white rounded flex items-center justify-center text-slate-400 group-hover:text-custom-primary shadow-sm">
                        <i class="fa-solid fa-palette"></i>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-700">Ajustes de Marca</p>
                        <p class="text-[10px] text-slate-400">Cambiar colores y logos</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Placeholder para Gráfica de Actividad -->
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm p-6 border border-slate-200 flex flex-col items-center justify-center min-h-[300px] text-center">
            <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center text-slate-300 mb-4">
                <i class="fa-solid fa-chart-line text-3xl"></i>
            </div>
            <h4 class="font-bold text-slate-700">Flujo de Pedidos</h4>
            <p class="text-xs text-slate-400 max-w-xs">Gráfica de rendimiento operativo (Siguiente módulo en desarrollo).</p>
        </div>
    </div>
@endsection

@section('scripts')
<script>
    // Scripts específicos para el Dashboard si fueran necesarios
    console.log('Dashboard cargado con KPIs de Almacén');
</script>
@endsection