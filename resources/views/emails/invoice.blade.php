@php
    // Recuperamos la configuración dinámica (Logo, Color, Nombre)
    $primaryColor = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('primary_color', '#2563eb') : '#2563eb';
    $siteLogo = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('report_logo') ?? \App\Models\Setting::get('site_logo') : null;
    $companyName = class_exists('\App\Models\Setting') ? \App\Models\Setting::get('company_name', 'Solu3PL') : 'Solu3PL';
@endphp
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; background-color: #f8fafc; padding: 20px; }
        .invoice-card { background: white; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .top-bar { background: #0f172a; padding: 20px; text-align: center; color: white; }
        .content { padding: 30px; }
        .amount-box { text-align: center; margin: 25px 0; background: #f1f5f9; padding: 20px; border-radius: 8px; }
        .amount-label { display: block; font-size: 12px; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
        .amount-value { display: block; font-size: 32px; color: #0f172a; font-weight: bold; }
        .details { font-size: 14px; color: #475569; line-height: 1.6; }
        /* Botón dinámico */
        .btn { display: inline-block; background: {{ $primaryColor }}; color: white; padding: 12px 30px; border-radius: 50px; text-decoration: none; font-weight: bold; }
        .logo-img { max-height: 40px; width: auto; background: white; padding: 5px; border-radius: 4px; }
        .company-header { font-size: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="invoice-card">
        <div class="top-bar">
            @if($siteLogo)
                <img src="{{ asset($siteLogo) }}" alt="{{ $companyName }}" class="logo-img">
            @else
                <div class="company-header">{{ $companyName }}</div>
            @endif
            <h2 style="margin-top: 10px; font-size: 18px; font-weight: normal; opacity: 0.9;">Nueva Factura Generada</h2>
        </div>
        <div class="content">
            <p>Estimado Cliente,</p>
            <p>Se ha generado el documento <strong>#{{ $invoice->invoice_number }}</strong> correspondiente a sus servicios logísticos.</p>
            
            <div class="amount-box">
                <span class="amount-label">Total a Pagar</span>
                <span class="amount-value">${{ number_format($invoice->total, 2) }}</span>
            </div>

            <div class="details">
                <p><strong>Fecha de Emisión:</strong> {{ $invoice->issue_date }}</p>
                <p><strong>Vencimiento:</strong> {{ $invoice->due_date }}</p>
            </div>

            <div style="text-align: center; margin-top: 30px;">
                <a href="{{ route('client.billing.index') }}" class="btn">Ver y Descargar Factura</a>
            </div>
            
            <p style="font-size: 12px; color: #94a3b8; text-align: center; margin-top: 30px;">
                Gracias por confiar en {{ $companyName }}.
            </p>
        </div>
    </div>
</body>
</html>