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
    <title>Nueva Contraseña - {{ $companyName }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: {{ $primaryColor }};
        }
        .bg-custom-primary { background-color: var(--primary-color) !important; }
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
    </style>
</head>
<body class="bg-slate-100 h-screen flex items-center justify-center p-4">
    <div class="bg-white p-8 rounded-2xl shadow-xl w-full max-w-md border border-slate-200">
        
        <div class="text-center mb-8">
            <!-- Logo Dinámico -->
            <div class="mb-6 flex justify-center">
                @if($siteLogo)
                    <img src="{{ asset($siteLogo) }}" alt="{{ $companyName }}" class="h-12 w-auto object-contain">
                @else
                    <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center text-custom-primary text-3xl border border-slate-100 shadow-sm">
                        <i class="fa-solid fa-lock-open"></i>
                    </div>
                @endif
            </div>

            <h1 class="text-2xl font-bold text-slate-800">Restablecer Contraseña</h1>
            <p class="text-slate-500 text-sm mt-1">Crea una nueva contraseña segura para tu cuenta.</p>
        </div>

        @if ($errors->any())
            <div class="bg-red-50 text-red-600 p-3 rounded-lg mb-4 text-sm border border-red-100">
                <ul class="list-disc pl-4">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('password.update') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Correo Electrónico</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="email" name="email" value="{{ $email ?? old('email') }}" required readonly
                        class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-slate-500 cursor-not-allowed font-medium text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Nueva Contraseña</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fa-solid fa-key"></i>
                    </span>
                    <input type="password" name="password" required autofocus placeholder="Mínimo 8 caracteres"
                        class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl focus:border-custom-primary focus:outline-none focus:ring-1 focus:ring-custom-primary transition-all text-sm">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Confirmar Contraseña</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fa-solid fa-check-double"></i>
                    </span>
                    <input type="password" name="password_confirmation" required placeholder="Repite la contraseña"
                        class="w-full pl-10 pr-4 py-3 bg-white border border-slate-200 rounded-xl focus:border-custom-primary focus:outline-none focus:ring-1 focus:ring-custom-primary transition-all text-sm">
                </div>
            </div>

            <button type="submit" class="w-full btn-primary font-bold py-3 rounded-xl transition-all shadow-lg shadow-black/10 active:scale-95 mt-2 flex items-center justify-center gap-2">
                <i class="fa-solid fa-save"></i> Restablecer Contraseña
            </button>
        </form>
    </div>
</body>
</html>