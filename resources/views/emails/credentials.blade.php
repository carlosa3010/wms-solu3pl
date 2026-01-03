@php
    // Recuperamos la configuración dinámica (Logo, Color, Nombre)
    $primaryColor = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('primary_color', '#2563eb') : '#2563eb';
    $siteLogo = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('site_logo') : null;
    $companyName = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('company_name', 'Solu3PL') : 'Solu3PL';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; background-color: #f3f4f6; padding: 20px; color: #334155; }
        .card { background: white; max-width: 600px; margin: 0 auto; padding: 30px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .header { text-align: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 20px; margin-bottom: 20px; }
        .logo { font-size: 24px; font-weight: bold; color: #0f172a; }
        .highlight { color: {{ $primaryColor }}; font-weight: bold; }
        .credentials-box { background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .label { font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: bold; display: block; margin-bottom: 5px; }
        .value { font-size: 16px; font-family: monospace; color: #0f172a; display: block; margin-bottom: 15px; }
        /* Botón dinámico */
        .btn { display: block; width: 100%; text-align: center; background-color: {{ $primaryColor }}; color: white; padding: 12px; border-radius: 8px; text-decoration: none; font-weight: bold; margin-top: 20px; }
        .footer { text-align: center; font-size: 12px; color: #94a3b8; margin-top: 30px; }
        .logo-img { max-height: 50px; width: auto; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <!-- Lógica para mostrar Logo o Texto -->
            @if($siteLogo)
                <img src="{{ asset($siteLogo) }}" alt="{{ $companyName }}" class="logo-img">
            @else
                <div class="logo">{{ $companyName }} <span style="color:{{ $primaryColor }}">WMS</span></div>
            @endif
        </div>
        
        <p>Hola <strong>{{ $user->name }}</strong>,</p>
        
        @if($isClient)
            <p>Bienvenido al <strong>Portal de Clientes</strong>. Se ha generado una cuenta para que pueda gestionar su inventario y pedidos.</p>
        @else
            <p>Se ha creado una cuenta administrativa para usted en el sistema WMS.</p>
        @endif

        <div class="credentials-box">
            <span class="label">Usuario / Email</span>
            <span class="value">{{ $user->email }}</span>
            
            <span class="label">Contraseña Temporal</span>
            <span class="value" style="font-size: 18px; letter-spacing: 1px;">{{ $password }}</span>
        </div>

        <a href="{{ route('login') }}" class="btn">Ingresar al Sistema</a>
        
        <p style="font-size: 13px; text-align: center; margin-top: 20px; color: #ef4444;">
            ⚠️ Por seguridad, le recomendamos cambiar esta contraseña inmediatamente después de iniciar sesión.
        </p>

        <div class="footer">
            &copy; {{ date('Y') }} {{ $companyName }}. Todos los derechos reservados.
        </div>
    </div>
</body>
</html>