@php
    // Recuperamos configuración dinámica para personalizar el login
    $primaryColor = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('primary_color', '#2563eb') : '#2563eb';
    $siteLogo = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('site_logo') : null;
    $companyName = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('company_name', 'Solu3PL') : 'Solu3PL';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso {{ $companyName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        :root {
            --primary-color: {{ $primaryColor }};
        }
        .text-custom-primary { color: var(--primary-color) !important; }
        .bg-custom-primary { background-color: var(--primary-color) !important; }
        .border-custom-primary { border-color: var(--primary-color) !important; }
        .ring-custom-primary { --tw-ring-color: var(--primary-color) !important; }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            filter: brightness(110%);
        }
    </style>
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center font-sans p-4">

    <div class="bg-white rounded-2xl shadow-2xl flex overflow-hidden max-w-4xl w-full border border-slate-800">
        
        <!-- Lado Izquierdo (Visual) -->
        <div class="hidden md:block w-1/2 bg-slate-800 relative">
            <div class="absolute inset-0 bg-slate-900/60 z-10"></div>
            <!-- Imagen de fondo genérica de almacén/logística -->
            <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" 
                 class="absolute inset-0 w-full h-full object-cover mix-blend-overlay opacity-40">
            
            <div class="relative z-20 h-full flex flex-col justify-between p-12 text-white">
                <div>
                    <!-- Logo / Marca -->
                    <div class="flex items-center gap-3 mb-6">
                        @if($siteLogo)
                            <img src="{{ asset($siteLogo) }}" alt="{{ $companyName }}" class="h-10 w-auto bg-white/10 p-1 rounded backdrop-blur-sm">
                        @else
                            <i class="fa-solid fa-cube text-4xl text-custom-primary"></i>
                        @endif
                        <h2 class="text-3xl font-bold tracking-wider">{{ $companyName }}</h2>
                    </div>
                    
                    <p class="mt-4 text-slate-300 text-lg font-light leading-relaxed">
                        Sistema Integral de Logística y Fulfillment. Gestiona tu inventario, pedidos y envíos en un solo lugar.
                    </p>
                </div>
                <div class="text-xs text-slate-500 border-t border-slate-700 pt-4">
                    &copy; {{ date('Y') }} {{ $companyName }} WMS v3.0
                </div>
            </div>
        </div>

        <!-- Lado Derecho (Formulario) -->
        <div class="w-full md:w-1/2 p-8 md:p-12 flex flex-col justify-center bg-white">
            <div class="mb-8">
                <h3 class="text-2xl font-bold text-slate-800 mb-1">Iniciar Sesión</h3>
                <p class="text-slate-500 text-sm">Bienvenido de nuevo, ingresa tus credenciales.</p>
            </div>

            @if ($errors->any())
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 text-sm rounded-r-lg shadow-sm">
                    <p class="font-bold mb-1"><i class="fa-solid fa-circle-exclamation mr-1"></i> Error de Acceso</p>
                    <p>{{ $errors->first() }}</p>
                </div>
            @endif
            
            @if (session('status'))
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 text-sm rounded-r-lg shadow-sm">
                    <p class="font-bold mb-1"><i class="fa-solid fa-check-circle mr-1"></i> Éxito</p>
                    <p>{{ session('status') }}</p>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-5">
                @csrf
                
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Correo Electrónico</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3 top-3.5 text-slate-400 text-sm"></i>
                        <input type="email" name="email" required autofocus value="{{ old('email') }}"
                               class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl focus:ring-1 focus:ring-custom-primary focus:border-custom-primary outline-none transition text-sm font-medium bg-slate-50 focus:bg-white"
                               placeholder="Correo Electronico">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-1">
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Contraseña</label>
                        <!-- Enlace a recuperación de contraseña -->
                        <a href="{{ route('password.request') }}" class="text-xs font-bold text-custom-primary hover:underline" tabindex="-1">
                            ¿Olvidaste tu contraseña?
                        </a>
                    </div>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3 top-3.5 text-slate-400 text-sm"></i>
                        <input type="password" name="password" required 
                               class="w-full pl-10 pr-4 py-3 border border-slate-200 rounded-xl focus:ring-1 focus:ring-custom-primary focus:border-custom-primary outline-none transition text-sm font-medium bg-slate-50 focus:bg-white"
                               placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center pt-2">
                    <input type="checkbox" name="remember" id="remember" class="w-4 h-4 text-custom-primary border-gray-300 rounded focus:ring-custom-primary cursor-pointer">
                    <label for="remember" class="ml-2 block text-sm text-slate-600 cursor-pointer select-none">
                        Recordar mi sesión
                    </label>
                </div>

                <button type="submit" class="w-full btn-primary font-bold py-3.5 rounded-xl transition shadow-lg shadow-black/5 active:scale-95 text-sm uppercase tracking-wide flex justify-center items-center gap-2">
                    <span>Acceder al Sistema</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

</body>
</html>