<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WMS Client Portal</title>
    
    <!-- Favicon Dinámico -->
    @php
        $setting = \App\Models\Setting::first(); 
        $favicon = ($setting && $setting->favicon) ? asset('storage/' . $setting->favicon) : asset('favicon.ico');
    @endphp
    <link rel="icon" href="{{ $favicon }}" type="image/x-icon">

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        /* Suavizar la transición del menú móvil */
        #mobile-menu {
            transition: all 0.3s ease-in-out;
        }
    </style>
</head>
<body class="bg-slate-50">

    <header class="bg-white border-b border-slate-200 sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                
                <!-- Lado Izquierdo: Logo y Desktop Nav -->
                <div class="flex items-center gap-8">
                    <div class="flex items-center gap-2">
                        @if($setting && $setting->logo)
                            <img src="{{ asset('storage/' . $setting->logo) }}" alt="Logo" class="h-8 w-auto">
                        @else
                            <div class="text-blue-600 font-black text-2xl tracking-tighter italic">SOLU3PL</div>
                        @endif
                    </div>
                    
                    <!-- Menú Desktop -->
                    <nav class="hidden lg:flex items-center gap-1">
                        @php
                            $links = [
                                ['route' => 'client.portal', 'label' => 'Dashboard', 'active' => request()->routeIs('client.portal')],
                                ['route' => 'client.catalog', 'label' => 'Catálogo', 'active' => request()->routeIs('client.catalog')],
                                ['route' => 'client.stock', 'label' => 'Inventario', 'active' => request()->routeIs('client.stock')],
                                ['route' => 'client.asn.index', 'label' => 'ASN', 'active' => request()->routeIs('client.asn.*')],
                                ['route' => 'client.orders.index', 'label' => 'Pedidos', 'active' => request()->routeIs('client.orders.*')],
                                ['route' => 'client.rma', 'label' => 'RMA', 'active' => request()->routeIs('client.rma')],
                                ['route' => 'client.billing.index', 'label' => 'Facturación', 'active' => request()->routeIs('client.billing.*')],
                                ['route' => 'client.api', 'label' => 'API', 'active' => request()->routeIs('client.api')],
                                ['route' => 'client.support', 'label' => 'Soporte', 'active' => request()->routeIs('client.support')],
                            ];
                        @endphp

                        @foreach($links as $link)
                            <a href="{{ route($link['route']) }}" 
                               class="px-3 py-2 rounded-lg text-sm font-bold {{ $link['active'] ? 'text-blue-600 bg-blue-50' : 'text-slate-500 hover:bg-slate-50' }}">
                                {{ $link['label'] }}
                            </a>
                        @endforeach
                    </nav>
                </div>

                <!-- Lado Derecho: Perfil y Logout -->
                <div class="flex items-center gap-2 sm:gap-4">
                    <div class="hidden sm:block text-right border-r border-slate-100 pr-4">
                        <p class="text-xs font-black text-slate-800">{{ auth()->user()->name }}</p>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tighter">Cuenta Cliente</p>
                    </div>

                    <div class="flex items-center gap-1">
                        <form action="{{ route('logout') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="p-2 text-slate-400 hover:text-rose-600 transition-colors" title="Cerrar Sesión">
                                <i data-lucide="log-out" class="w-5 h-5"></i>
                            </button>
                        </form>

                        <!-- Botón Menú Móvil (Solo visible en pantallas pequeñas) -->
                        <button id="mobile-menu-button" class="lg:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                            <i data-lucide="menu" id="menu-icon" class="w-6 h-6"></i>
                            <i data-lucide="x" id="close-icon" class="w-6 h-6 hidden"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menú Móvil Desplegable -->
        <div id="mobile-menu" class="hidden lg:hidden bg-white border-t border-slate-100 shadow-xl overflow-hidden">
            <div class="px-4 pt-2 pb-6 space-y-1">
                <div class="sm:hidden pb-3 mb-3 border-b border-slate-50">
                    <p class="text-xs font-black text-slate-800">{{ auth()->user()->name }}</p>
                    <p class="text-[10px] text-slate-400 font-bold uppercase">Cuenta Cliente</p>
                </div>
                
                @foreach($links as $link)
                    <a href="{{ route($link['route']) }}" 
                       class="block px-4 py-3 rounded-xl text-base font-bold {{ $link['active'] ? 'text-blue-600 bg-blue-50' : 'text-slate-600 hover:bg-slate-50' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6 md:py-8">
        @yield('content')
    </main>

    <script>
        // Inicializar Iconos
        lucide.createIcons();

        // Lógica del Menú Móvil
        const mobileMenuButton = document.getElementById('mobile-menu-button');
        const mobileMenu = document.getElementById('mobile-menu');
        const menuIcon = document.getElementById('menu-icon');
        const closeIcon = document.getElementById('close-icon');

        mobileMenuButton.addEventListener('click', () => {
            const isHidden = mobileMenu.classList.contains('hidden');
            
            if (isHidden) {
                mobileMenu.classList.remove('hidden');
                menuIcon.classList.add('hidden');
                closeIcon.classList.remove('hidden');
            } else {
                mobileMenu.classList.add('hidden');
                menuIcon.classList.remove('hidden');
                closeIcon.classList.add('hidden');
            }
        });

        // Cerrar menú si se redimensiona a desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 1024) { // 1024px es el breakpoint 'lg' de Tailwind
                mobileMenu.classList.add('hidden');
                menuIcon.classList.remove('hidden');
                closeIcon.classList.add('hidden');
            }
        });
    </script>
</body>
</html>