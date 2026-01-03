<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acceso Solu3PL</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
</head>
<body class="bg-slate-900 h-screen flex items-center justify-center font-sans">

    <div class="bg-white rounded-lg shadow-2xl flex overflow-hidden max-w-4xl w-full mx-4">
        
        <!-- Lado Izquierdo (Visual) -->
        <div class="hidden md:block w-1/2 bg-slate-800 relative">
            <div class="absolute inset-0 bg-blue-900/40 z-10"></div>
            <img src="https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?ixlib=rb-1.2.1&auto=format&fit=crop&w=1350&q=80" 
                 class="absolute inset-0 w-full h-full object-cover grayscale opacity-50">
            
            <div class="relative z-20 h-full flex flex-col justify-between p-12 text-white">
                <div>
                    <i class="fa-solid fa-cube text-4xl text-blue-500 mb-4"></i>
                    <h2 class="text-3xl font-bold tracking-wider">SOLU<span class="text-blue-500">3PL</span></h2>
                    <p class="mt-2 text-slate-300">Sistema Integral de Logística y Fulfillment.</p>
                </div>
                <div class="text-xs text-slate-400">
                    &copy; {{ date('Y') }} Solu3PL WMS v3.0
                </div>
            </div>
        </div>

        <!-- Lado Derecho (Formulario) -->
        <div class="w-full md:w-1/2 p-12 flex flex-col justify-center bg-slate-50">
            <h3 class="text-2xl font-bold text-slate-800 mb-2">Iniciar Sesión</h3>
            <p class="text-slate-500 mb-8 text-sm">Ingresa tus credenciales para acceder al panel.</p>

            @if ($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-3 mb-6 text-sm">
                    <strong>¡Error!</strong> {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST" class="space-y-6">
                @csrf
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Correo Electrónico</label>
                    <div class="relative">
                        <i class="fa-solid fa-envelope absolute left-3 top-3 text-slate-400"></i>
                        <input type="email" name="email" required 
                               class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                               placeholder="usuario@solu3pl.com">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Contraseña</label>
                    <div class="relative">
                        <i class="fa-solid fa-lock absolute left-3 top-3 text-slate-400"></i>
                        <input type="password" name="password" required 
                               class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                               placeholder="••••••••">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center text-sm text-slate-600 cursor-pointer">
                        <input type="checkbox" name="remember" class="mr-2 text-blue-600 rounded focus:ring-blue-500">
                        Recordarme
                    </label>
                    <a href="#" class="text-sm text-blue-600 hover:underline">¿Olvidaste tu contraseña?</a>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded transition shadow-lg shadow-blue-500/30">
                    Acceder al Sistema
                </button>
            </form>
        </div>
    </div>

</body>
</html>