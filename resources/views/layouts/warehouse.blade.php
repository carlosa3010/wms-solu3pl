<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>WMS Operaciones</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <script src="//unpkg.com/alpinejs" defer></script>
    
    <style>
        /* Ocultar scrollbars para que parezca una App Nativa en PDAs */
        body::-webkit-scrollbar { display: none; }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        
        /* Evitar selección de texto en botones para sensación táctil */
        .touch-manipulation { touch-action: manipulation; user-select: none; }
    </style>
    @yield('styles')
</head>
<body class="bg-slate-900 text-white h-screen flex flex-col overflow-hidden">

    <header class="h-16 bg-slate-800 flex items-center justify-between px-4 shadow-lg shrink-0 border-b border-slate-700 z-50">
        <div class="flex items-center gap-4">
            <a href="{{ route('warehouse.dashboard') }}" class="w-10 h-10 flex items-center justify-center rounded-xl bg-slate-700 hover:bg-slate-600 text-slate-300 hover:text-white transition active:scale-95">
                <i class="fa-solid fa-house text-lg"></i>
            </a>
            
            <div class="leading-tight">
                <h1 class="font-bold text-lg tracking-wide text-white">@yield('station_title', 'WMS Terminal')</h1>
                @if(isset($order))
                    <p class="text-[10px] text-blue-400 font-mono">ORD: {{ $order->order_number }}</p>
                @endif
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <div class="text-right hidden sm:block">
                <p class="text-xs text-slate-400 font-bold uppercase truncate max-w-[100px]">{{ Auth::user()->name ?? 'Operador' }}</p>
                <div class="flex items-center justify-end gap-1">
                    <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                    <p class="text-[9px] text-green-400">Online</p>
                </div>
            </div>

            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button class="bg-red-500/10 text-red-500 hover:bg-red-600 hover:text-white w-10 h-10 rounded-xl flex items-center justify-center transition border border-red-500/20 active:scale-95">
                    <i class="fa-solid fa-power-off"></i>
                </button>
            </form>
        </div>
    </header>

    @if(session('success') || session('error') || session('warning'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
             class="absolute top-20 left-4 right-4 z-50 flex flex-col gap-2">
            
            @if(session('success'))
                <div class="bg-green-600 text-white p-4 rounded-xl shadow-2xl flex items-center gap-3 border border-green-400 animate-bounce-in">
                    <i class="fa-solid fa-circle-check text-2xl"></i>
                    <span class="font-bold text-lg">{{ session('success') }}</span>
                    <script>new Audio('https://www.myinstants.com/media/sounds/correct.mp3').play().catch(e=>{});</script>
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-600 text-white p-4 rounded-xl shadow-2xl flex items-center gap-3 border border-red-400 animate-shake">
                    <i class="fa-solid fa-triangle-exclamation text-2xl"></i>
                    <span class="font-bold text-lg">{{ session('error') }}</span>
                    <script>new Audio('https://www.myinstants.com/media/sounds/error.mp3').play().catch(e=>{});</script>
                </div>
            @endif
        </div>
    @endif

    <main class="flex-1 overflow-y-auto p-3 relative bg-slate-900 custom-scrollbar">
        @yield('content')
    </main>

    @yield('footer_actions')

    <script>
        // Auto-focus en inputs de scanner si existen
        document.addEventListener('DOMContentLoaded', function() {
            const scanInput = document.getElementById('scanInput');
            if(scanInput) {
                scanInput.focus();
                // Mantener foco aunque se haga clic fuera (útil para PDAs)
                document.addEventListener('click', () => scanInput.focus());
            }
        });
    </script>
    @yield('scripts')
</body>
</html>