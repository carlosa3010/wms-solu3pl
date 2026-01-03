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
        body { font-family: sans-serif; padding: 20px; background-color: #fff1f2; }
        .alert-card { background: white; max-width: 500px; margin: 0 auto; padding: 25px; border-radius: 10px; border-left: 6px solid #e11d48; box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
        .title { color: #be123c; font-size: 18px; font-weight: bold; margin-top: 0; display: flex; align-items: center; gap: 10px; }
        .product-info { margin: 20px 0; border-top: 1px solid #fecdd3; border-bottom: 1px solid #fecdd3; padding: 15px 0; }
        .stat { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .label { color: #881337; font-size: 13px; }
        .val { font-weight: bold; color: #0f172a; }
        /* Botón dinámico */
        .btn { display: inline-block; background: {{ $primaryColor }}; color: white; text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 13px; margin-top: 10px; }
        .logo-container { text-align: center; margin-bottom: 20px; }
        .logo-img { max-height: 40px; width: auto; opacity: 0.8; }
        .company-text { font-size: 14px; font-weight: bold; color: #64748b; }
    </style>
</head>
<body>
    <div class="logo-container">
        @if($siteLogo)
            <img src="{{ asset($siteLogo) }}" alt="{{ $companyName }}" class="logo-img">
        @else
            <div class="company-text">{{ $companyName }}</div>
        @endif
    </div>

    <div class="alert-card">
        <h2 class="title">⚠️ Alerta de Inventario</h2>
        <p style="color: #4b5563; font-size: 14px;">El siguiente producto ha alcanzado su nivel mínimo de reorden:</p>
        
        <div class="product-info">
            <h3 style="margin: 0 0 10px 0; color: #0f172a;">{{ $product->name }}</h3>
            <div class="stat">
                <span class="label">SKU:</span>
                <span class="val">{{ $product->sku }}</span>
            </div>
            <div class="stat">
                <span class="label">Stock Actual:</span>
                <span class="val" style="color: #e11d48;">{{ $currentStock }} unid.</span>
            </div>
            <div class="stat">
                <span class="label">Mínimo Configurado:</span>
                <span class="val">{{ $product->min_stock }} unid.</span>
            </div>
        </div>

        <p style="font-size: 12px; color: #64748b;">Se recomienda generar una orden de entrada o reabastecimiento pronto.</p>
        
        <center>
            <a href="{{ route('login') }}" class="btn">Ver en WMS</a>
        </center>
    </div>
</body>
</html>