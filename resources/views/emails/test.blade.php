<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        /* Recuperamos el color primario de la configuración o usamos el default */
        @php
            $primaryColor = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('primary_color', '#2563eb') : '#2563eb';
        @endphp

        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f1f5f9; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); overflow: hidden; }
        
        /* Usamos el color dinámico aquí */
        .header { background-color: {{ $primaryColor }}; padding: 30px; text-align: center; }
        
        .header h1 { color: white; margin: 0; font-size: 24px; }
        .icon { font-size: 48px; color: white; margin-bottom: 10px; display: block; }
        .content { padding: 40px; color: #334155; line-height: 1.6; }
        
        /* Un fondo suave derivado del color (simulado con opacidad o gris azulado para neutralidad) */
        .highlight { background-color: #f8fafc; border-left: 4px solid {{ $primaryColor }}; color: #334155; padding: 15px; border-radius: 4px; margin: 20px 0; text-align: center; font-weight: bold; }
        
        .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; }
        
        /* Enlace o texto destacado */
        .accent-text { color: {{ $primaryColor }}; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="icon">🚀</span>
            <h1>¡Conexión Exitosa!</h1>
        </div>
        <div class="content">
            <p>Hola Administrador,</p>
            <p>Si estás leyendo este mensaje, significa que la configuración de tu servidor de correo (SMTP) en <strong>Solu3PL WMS</strong> está funcionando correctamente.</p>
            
            <div class="highlight">
                El sistema ya está listo para enviar notificaciones.
            </div>

            <p>Detalles técnicos:</p>
            <ul>
                <li><strong>Fecha:</strong> {{ now()->format('d/m/Y H:i:s') }}</li>
                <li><strong>Origen:</strong> <span class="accent-text">Panel de Configuración</span></li>
                <li><strong>Configuración:</strong> <span class="accent-text">Base de Datos (Settings)</span></li>
            </ul>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Solu3PL WMS. Mensaje generado automáticamente.
        </div>
    </div>
</body>
</html>