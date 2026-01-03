@php
    $primaryColor = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('primary_color', '#2563eb') : '#2563eb';
    $siteLogo = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('site_logo') : null;
    $companyName = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('company_name', 'Solu3PL') : 'Solu3PL';
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Contraseña - {{ $companyName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: {{ $primaryColor }};
        }
        .text-custom-primary { color: var(--primary-color) !important; }
        .border-custom-primary { border-color: var(--primary-color) !important; }
        .ring-custom-primary { --tw-ring-color: var(--primary-color) !important; }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        .btn-primary:hover {
            filter: brightness(110%);
        }

        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body class="bg-slate-100 h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md animate-fade-in border border-slate-200">
        
        <div class="text-center mb-8">
            <!-- Logo Dinámico -->
            <div class="mb-6 flex justify-center">
                @if($siteLogo)
                    <img src="{{ asset($siteLogo) }}" alt="{{ $companyName }}" class="h-12 w-auto object-contain">
                @else
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-custom-primary text-3xl border border-slate-100 shadow-sm">
                        <i class="fa-solid fa-lock"></i>
                    </div>
                @endif
            </div>

            <h1 class="text-2xl font-bold text-slate-800">Recuperar Acceso</h1>
            <p class="text-slate-500 text-sm mt-2">Ingresa tu correo y te enviaremos un enlace seguro para restablecer tu clave.</p>
        </div>

        @if (session('status'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-3">
                <i class="fa-solid fa-check-circle text-lg"></i>
                <div>
                    <p class="font-bold text-sm">¡Correo Enviado!</p>
                    <p class="text-xs">{{ session('status') }}</p>
                </div>
            </div>
        @endif

        @if ($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <ul class="text-sm list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('password.email') }}" method="POST" class="space-y-5">
            @csrf
            <!-- ELIMINADO EL INPUT TOKEN QUE CAUSABA EL ERROR -->
            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Correo Electrónico</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="email" name="email" value="{{ old('email') }}" required autofocus placeholder="ejemplo@empresa.com"
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl focus:border-custom-primary focus:outline-none focus:ring-1 focus:ring-custom-primary transition-all text-sm font-medium">
                </div>
            </div>

            <button type="submit" class="w-full btn-primary font-bold py-3 rounded-xl transition-all shadow-lg shadow-black/10 active:scale-95 flex items-center justify-center gap-2">
                <i class="fa-solid fa-paper-plane"></i> Enviar Enlace
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100 text-center">
            <a href="{{ route('login') }}" class="text-sm text-slate-500 hover:text-custom-primary font-bold transition flex items-center justify-center gap-2 group">
                <i class="fa-solid fa-arrow-left group-hover:-translate-x-1 transition-transform"></i> Volver al Login
            </a>
        </div>
    </div>
</body>
</html>