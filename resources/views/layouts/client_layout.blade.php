<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMS Client Portal</title>
    
    <!-- Favicon Dinámico -->
    @php
        // Intentamos obtener la configuración. Ajusta App\Models\Setting al nombre real de tu modelo de configuración si es diferente.
        $setting = \App\Models\Setting::first(); 
        $favicon = ($setting && $setting->favicon) ? asset('storage/' . $setting->favicon) : asset('favicon.ico');
    @endphp
    <link rel="icon" href="{{ $favicon }}" type="image/x-icon">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="bg-slate-50">

    <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center gap-8">
                    <div class="flex items-center gap-2">
                        <!-- Logo Dinámico (Opcional, si también quieres que el logo cambie) -->
                        @if($setting && $setting->logo)
                            <img src="{{ asset('storage/' . $setting->logo) }}" alt="Logo" class="h-8 w-auto">
                        @else
                            <div class="text-blue-600 font-black text-2xl tracking-tighter italic">SOLU3PL</div>
                        @endif
                    </div>
                    
                    <!-- Menú Desktop -->
                    <nav class="hidden md:flex items-center gap-1">
                        <a href="{{ route('client.portal') }}" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->routeIs('client.portal') ? 'text-blue-600 bg-blue-50' : 'text-slate-500 hover:bg-slate-50' }}">Dashboard</a>
                        <a href="{{ route('client.catalog') }}" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->routeIs('client.catalog') ? 'text-blue-600 bg-blue-50' : 'text-slate-500 hover:bg-slate-50' }}">Catálogo</a>
                        <a href="{{ route('client.stock') }}" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->routeIs('client.stock') ? 'text-blue-600 bg-blue-50' : 'text-slate-500 hover:bg-slate-50' }}">Inventario</a>
                        <a href="{{ route('client.asn.index') }}" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->routeIs('client.asn.*') ? 'text-blue-600 bg-blue-50' : 'text-slate-500 hover:bg-slate-50' }}">ASN</a>
                        
                        <a href="{{ route('client.orders.index') }}" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->routeIs('client.orders.*') ? 'text-blue-600 bg-blue-50' : 'text-slate-500 hover:bg-slate-50' }}">Pedidos</a>
                        
                        <a href="{{ route('client.rma') }}" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->routeIs('client.rma') ? 'text-blue-600 bg-blue-50' : 'text-slate-500 hover:bg-slate-50' }}">RMA</a>

                        <!-- Nuevo Apartado: Facturación -->
                        <a href="{{ route('client.billing.index') }}" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->routeIs('client.billing.*') ? 'text-blue-600 bg-blue-50' : 'text-slate-500 hover:bg-slate-50' }}">Facturación</a>
                        
                        <!-- Nuevos Apartados -->
                        <a href="{{ route('client.api') }}" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->routeIs('client.api') ? 'text-blue-600 bg-blue-50' : 'text-slate-500 hover:bg-slate-50' }}">API</a>
                        <a href="{{ route('client.support') }}" class="px-3 py-2 rounded-lg text-sm font-bold {{ request()->routeIs('client.support') ? 'text-blue-600 bg-blue-50' : 'text-slate-500 hover:bg-slate-50' }}">Soporte</a>
                    </nav>
                </div>

                <!-- Perfil -->
                <div class="flex items-center gap-4">
                    <div class="hidden sm:block text-right">
                        <p class="text-xs font-black text-slate-800">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">Cuenta Cliente</p>
                    </div>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="p-2 text-slate-400 hover:text-rose-600" title="Cerrar Sesión">
                            <i data-lucide="log-out" class="w-5 h-5"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-8">
        @yield('content')
    </main>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>