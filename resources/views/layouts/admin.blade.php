@php
    // Recuperar configuración de marca (Branding) desde el modelo Setting
    $primaryColor = \App\Models\Setting::get('primary_color', '#2563eb'); 
    $sidebarColor = \App\Models\Setting::get('sidebar_color', '#0f172a'); 
    $siteLogo = \App\Models\Setting::get('site_logo');
    $siteFavicon = \App\Models\Setting::get('site_favicon');

    // LÓGICA DE NOTIFICACIONES (Alertas de Operación)
    // Se alimenta directamente de los modelos para mostrar conteos reales
    $pendingAsnsCount = \App\Models\ASN::where('status', 'pending')->count();
    $pendingOrdersCount = \App\Models\Order::where('status', 'pending')->count();
    $totalNotifications = $pendingAsnsCount + $pendingOrdersCount;
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Panel') - Solu3PL WMS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    @if($siteFavicon)
        <link rel="icon" type="image/x-icon" href="{{ $siteFavicon }}">
    @endif

    <!-- Recursos Externos -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        :root {
            --primary-color: {{ $primaryColor }};
            --sidebar-color: {{ $sidebarColor }};
        }
        /* Clases dinámicas basadas en la configuración de marca */
        .bg-custom-primary { background-color: var(--primary-color) !important; }
        .text-custom-primary { color: var(--primary-color) !important; }
        .border-custom-primary { border-color: var(--primary-color) !important; }
        .ring-custom-primary { --tw-ring-color: var(--primary-color) !important; }
        .bg-custom-sidebar { background-color: var(--sidebar-color) !important; }
        
        /* Scrollbar estilizado */
        .custom-scrollbar::-webkit-scrollbar { width: 5px; height: 5px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: rgba(0,0,0,0.05); }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 10px; }
        
        /* Animaciones suaves */
        .animate-fade-in { animation: fadeIn 0.3s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
        
        #sidebar.mobile-active { transform: translateX(0); }
    </style>
    @yield('styles')
</head>
<body class="bg-slate-100 font-sans text-slate-600 h-screen overflow-hidden flex">

    <!-- Overlay para dispositivos móviles -->
    <div id="mobile-overlay" class="fixed inset-0 bg-black/50 z-40 hidden transition-opacity" onclick="toggleSidebar()"></div>

    <!-- BARRA LATERAL (SIDEBAR) -->
    <aside id="sidebar" class="bg-custom-sidebar text-white w-64 flex-shrink-0 flex flex-col h-full fixed lg:relative z-50 transition-transform duration-300 -translate-x-full lg:translate-x-0 shadow-2xl">
        
        <!-- Logo de la Empresa -->
        <div class="h-16 flex items-center px-6 border-b border-white/10 bg-black/20 shrink-0">
            @if($siteLogo)
                <img src="{{ $siteLogo }}" alt="Logo" class="h-8 w-auto object-contain">
            @else
                <div class="flex items-center gap-2 font-bold text-xl tracking-tight">
                    <i class="fa-solid fa-cube text-blue-400"></i>
                    <span>SOLU<span class="text-blue-400">3PL</span></span>
                </div>
            @endif
        </div>

        <!-- Menú Principal -->
        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1 custom-scrollbar">
            
            <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 mt-2">Operativo</p>
            
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition-all {{ request()->routeIs('admin.dashboard') ? 'bg-custom-primary text-white shadow-lg' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                <span class="text-sm font-bold">Dashboard</span>
            </a>

            <!-- MÓDULO COMERCIAL -->
            <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 mt-6">Comercial</p>
            <a href="{{ route('admin.clients.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.clients.*') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-briefcase w-5 text-center"></i>
                <span class="text-sm font-medium">Clientes</span>
            </a>
            <a href="{{ route('admin.crm.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.crm.*') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-user-group w-5 text-center"></i>
                <span class="text-sm font-medium">CRM / Leads</span>
            </a>

            <!-- MÓDULO INVENTARIO -->
            <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 mt-6">Inventario</p>
            <a href="{{ route('admin.products.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.products.*') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-barcode w-5 text-center"></i>
                <span class="text-sm font-medium">Catálogo Maestro</span>
            </a>
            <a href="{{ route('admin.categories.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.categories.*') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-tags w-5 text-center"></i>
                <span class="text-sm font-medium">Categorías</span>
            </a>
            <a href="{{ route('admin.inventory.stock') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.inventory.stock') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-boxes-stacked w-5 text-center"></i>
                <span class="text-sm font-medium">Stock Actual</span>
            </a>
            <a href="{{ route('admin.inventory.movements') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.inventory.movements') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-clock-rotate-left w-5 text-center"></i>
                <span class="text-sm font-medium">Kardex (Movimientos)</span>
            </a>
            <a href="{{ route('admin.inventory.adjustments') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.inventory.adjustments') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-scale-balanced w-5 text-center"></i>
                <span class="text-sm font-medium">Ajustes Manuales</span>
            </a>

            <!-- MÓDULO FLUJO OPERATIVO -->
            <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 mt-6">Operaciones</p>
            <a href="{{ route('admin.receptions.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.receptions.*') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-truck-ramp-box w-5 text-center"></i>
                <span class="text-sm font-medium">Entradas (ASN)</span>
            </a>
            <a href="{{ route('admin.orders.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.orders.*') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-cart-shopping w-5 text-center"></i>
                <span class="text-sm font-medium">Salidas (Pedidos)</span>
            </a>
            <a href="{{ route('admin.shipping.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.shipping.*') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-truck-fast w-5 text-center"></i>
                <span class="text-sm font-medium">Despachos</span>
            </a>
            <a href="{{ route('admin.transfers.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.transfers.*') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-arrow-right-arrow-left w-5 text-center"></i>
                <span class="text-sm font-medium">Traslados Internos</span>
            </a>
            <!-- NUEVO MÓDULO: RMA / DEVOLUCIONES -->
            <a href="{{ route('admin.rma.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.rma.*') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-rotate-left w-5 text-center"></i>
                <span class="text-sm font-medium">Devoluciones (RMA)</span>
            </a>

            <!-- MÓDULO FINANZAS -->
            <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 mt-6">Finanzas</p>
            <a href="{{ route('admin.billing.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.billing.index') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-file-invoice-dollar w-5 text-center"></i>
                <span class="text-sm font-medium">Facturación</span>
            </a>
            <a href="{{ route('admin.billing.rates') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.billing.rates') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-hand-holding-dollar w-5 text-center"></i>
                <span class="text-sm font-medium">Tarifas de Servicios</span>
            </a>

            <!-- MÓDULO INFRAESTRUCTURA -->
            <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 mt-6">Infraestructura</p>
            <a href="{{ route('admin.inventory.map') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.inventory.map') && !request()->has('view') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-warehouse w-5 text-center"></i>
                <span class="text-sm font-medium">Sedes & Bodegas</span>
            </a>
            <a href="{{ route('admin.inventory.map', ['view' => 'map']) }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.inventory.map') && request('view') == 'map' ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-map-location-dot w-5 text-center"></i>
                <span class="text-sm font-medium">Mapa de Planta</span>
            </a>
            <a href="{{ route('admin.inventory.coverage') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.inventory.coverage') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-satellite-dish w-5 text-center"></i>
                <span class="text-sm font-medium">Cobertura Regional</span>
            </a>

            <!-- MÓDULO CONFIGURACIÓN -->
            <p class="px-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2 mt-6">Configuración</p>
            <a href="{{ route('admin.bintypes.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.bintypes.*') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-cubes w-5 text-center"></i>
                <span class="text-sm font-medium">Tipos de Contenedores</span>
            </a>
            <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.settings.*') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-gears w-5 text-center"></i>
                <span class="text-sm font-medium">Marca & Sistema</span>
            </a>
            <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg transition-all {{ request()->routeIs('admin.users.*') ? 'bg-custom-primary text-white' : 'text-slate-300 hover:bg-white/10 hover:text-white' }}">
                <i class="fa-solid fa-users-gear w-5 text-center"></i>
                <span class="text-sm font-medium">Usuarios</span>
            </a>

        </nav>

        <!-- Footer del Sidebar -->
        <div class="p-4 border-t border-white/10 bg-black/20 shrink-0">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center text-xs font-bold text-white uppercase border-2 border-white/20">
                    {{ substr(Auth::user()->name, 0, 1) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-bold text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] text-slate-400 truncate uppercase tracking-tighter">{{ Auth::user()->role }}</p>
                </div>
            </div>
        </div>
    </aside>

    <!-- PANEL DE CONTENIDO PRINCIPAL -->
    <main class="flex-1 flex flex-col min-w-0 bg-slate-100 h-full relative">
        
        <!-- ENCABEZADO SUPERIOR (HEADER) -->
        <header class="h-16 bg-white shadow-sm flex items-center justify-between px-4 lg:px-8 border-b border-slate-200 shrink-0 z-30">
            <div class="flex items-center gap-4">
                <button onclick="toggleSidebar()" class="lg:hidden p-2 text-slate-600 hover:bg-slate-100 rounded-lg transition">
                    <i class="fa-solid fa-bars-staggered text-xl"></i>
                </button>
                <h1 class="text-lg lg:text-xl font-black text-slate-800 tracking-tight">@yield('header_title', 'Dashboard')</h1>
            </div>

            <div class="flex items-center gap-3 lg:gap-5">
                
                <!-- CAMPANA DE NOTIFICACIONES FUNCIONAL -->
                <div class="relative">
                    <button onclick="toggleNotificationDropdown()" id="notificationBtn" class="relative p-2.5 text-slate-400 hover:text-custom-primary transition rounded-xl hover:bg-slate-50 group">
                        <i class="fa-regular fa-bell text-xl group-hover:rotate-12 transition-transform"></i>
                        @if($totalNotifications > 0)
                            <span class="absolute top-2 right-2 w-5 h-5 bg-red-500 text-white text-[10px] font-black rounded-full border-2 border-white flex items-center justify-center animate-bounce">
                                {{ $totalNotifications }}
                            </span>
                        @endif
                    </button>

                    <!-- Dropdown de Notificaciones -->
                    <div id="notificationDropdown" class="hidden absolute right-0 mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-slate-100 py-0 animate-fade-in z-50 overflow-hidden">
                        <div class="p-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                            <h4 class="text-xs font-black text-slate-700 uppercase tracking-widest">Alertas Operativas</h4>
                            <span class="text-[10px] bg-custom-primary text-white px-2 py-0.5 rounded-full font-bold">{{ $totalNotifications }}</span>
                        </div>
                        
                        <div class="max-h-96 overflow-y-auto custom-scrollbar">
                            <!-- Alerta ASNs Pendientes -->
                            @if($pendingAsnsCount > 0)
                                <a href="{{ route('admin.receptions.index', ['status' => 'pending']) }}" class="flex items-start gap-4 p-4 hover:bg-blue-50 transition border-b border-slate-50 group">
                                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shrink-0 group-hover:bg-blue-600 group-hover:text-white transition-colors shadow-sm">
                                        <i class="fa-solid fa-truck-ramp-box"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-700">Entradas por Recibir</p>
                                        <p class="text-xs text-slate-500 leading-snug">Hay <span class="font-black text-blue-600">{{ $pendingAsnsCount }}</span> ASNs en espera en el muelle de descarga.</p>
                                        <p class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-tighter">Prioridad: Normal</p>
                                    </div>
                                </a>
                            @endif

                            <!-- Alerta Pedidos Pendientes -->
                            @if($pendingOrdersCount > 0)
                                <a href="{{ route('admin.orders.index', ['search' => 'pending']) }}" class="flex items-start gap-4 p-4 hover:bg-orange-50 transition border-b border-slate-50 group">
                                    <div class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center shrink-0 group-hover:bg-orange-600 group-hover:text-white transition-colors shadow-sm">
                                        <i class="fa-solid fa-cart-flatbed"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-bold text-slate-700">Nuevos Pedidos de Salida</p>
                                        <p class="text-xs text-slate-500 leading-snug">Se han registrado <span class="font-black text-orange-600">{{ $pendingOrdersCount }}</span> órdenes listas para picking.</p>
                                        <p class="text-[10px] text-slate-400 mt-1 uppercase font-bold tracking-tighter">Prioridad: Alta</p>
                                    </div>
                                </a>
                            @endif

                            @if($totalNotifications == 0)
                                <div class="p-8 text-center">
                                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4 text-slate-300">
                                        <i class="fa-solid fa-bell-slash text-2xl"></i>
                                    </div>
                                    <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Sin alertas pendientes</p>
                                    <p class="text-[10px] text-slate-300 mt-1">Tu operación está al día.</p>
                                </div>
                            @endif
                        </div>

                        <div class="p-3 bg-slate-50 border-t border-slate-100 text-center">
                            <button onclick="toggleNotificationDropdown()" class="text-[10px] font-black text-slate-400 uppercase hover:text-custom-primary transition">Cerrar Panel</button>
                        </div>
                    </div>
                </div>

                <div class="h-8 w-px bg-slate-200 hidden sm:block"></div>

                <!-- Perfil de Usuario -->
                <div class="relative">
                    <button onclick="toggleUserDropdown()" id="userBtn" class="flex items-center gap-3 hover:bg-slate-50 p-1.5 rounded-xl transition border border-transparent hover:border-slate-200 group">
                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 group-hover:text-custom-primary transition-colors">
                            <i class="fa-solid fa-user-circle text-lg"></i>
                        </div>
                        <div class="text-left hidden sm:block leading-none">
                            <p class="text-sm font-bold text-slate-700">Mi Cuenta</p>
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Panel de Control</p>
                        </div>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-300"></i>
                    </button>

                    <!-- Menú Desplegable (Dropdown) -->
                    <div id="userDropdown" class="hidden absolute right-0 mt-3 w-56 bg-white rounded-2xl shadow-2xl border border-slate-100 py-2 animate-fade-in z-50">
                        <div class="px-4 py-3 border-b border-slate-50 mb-1">
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-0.5">Usuario Conectado</p>
                            <p class="text-sm font-bold text-slate-800 truncate">{{ Auth::user()->name }}</p>
                        </div>
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-custom-primary transition font-medium">
                            <i class="fa-regular fa-id-card w-4"></i> Mi Perfil
                        </a>
                        <a href="{{ route('admin.settings.index') }}" class="flex items-center gap-3 px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-custom-primary transition font-medium">
                            <i class="fa-solid fa-sliders w-4"></i> Preferencias
                        </a>
                        <div class="h-px bg-slate-50 my-2"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left flex items-center gap-3 px-4 py-2 text-sm text-red-500 hover:bg-red-50 font-bold transition">
                                <i class="fa-solid fa-power-off w-4"></i> Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- ÁREA DE CONTENIDO DINÁMICO -->
        <div class="flex-1 overflow-auto p-4 md:p-8 lg:p-10 custom-scrollbar relative">
            @yield('content')
        </div>
    </main>
</div>

<!-- Lógica Global de Interfaz -->
<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('mobile-overlay');
        sidebar.classList.toggle('mobile-active');
        sidebar.classList.toggle('-translate-x-full');
        overlay.classList.toggle('hidden');
    }

    function toggleUserDropdown() {
        const dropdown = document.getElementById('userDropdown');
        const notifDropdown = document.getElementById('notificationDropdown');
        dropdown.classList.toggle('hidden');
        if (!notifDropdown.classList.contains('hidden')) notifDropdown.classList.add('hidden');
    }

    function toggleNotificationDropdown() {
        const dropdown = document.getElementById('notificationDropdown');
        const userDropdown = document.getElementById('userDropdown');
        dropdown.classList.toggle('hidden');
        if (!userDropdown.classList.contains('hidden')) userDropdown.classList.add('hidden');
    }

    // Cerrar dropdowns al hacer clic fuera del área
    window.onclick = function(event) {
        if (!event.target.matches('#userBtn') && !event.target.closest('#userBtn')) {
            const dropdown = document.getElementById("userDropdown");
            if (dropdown && !dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
            }
        }
        if (!event.target.matches('#notificationBtn') && !event.target.closest('#notificationBtn')) {
            const dropdown = document.getElementById("notificationDropdown");
            if (dropdown && !dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
            }
        }
    }

    // Inicializar iconos de Lucide (si se usan componentes adicionales)
    lucide.createIcons();
</script>
@yield('scripts')
</body>
</html>