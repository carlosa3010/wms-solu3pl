<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; color: #333; font-size: 12px; margin: 0; padding: 40px; }
        .header { border-bottom: 2px solid #2563eb; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #2563eb; }
        .doc-type { text-align: right; text-transform: uppercase; font-weight: bold; color: #64748b; }
        .info-grid { width: 100%; margin-bottom: 30px; }
        .info-grid td { vertical-align: top; width: 50%; }
        .label { font-size: 10px; color: #94a3b8; text-transform: uppercase; font-weight: bold; }
        .value { font-size: 13px; font-weight: bold; margin-top: 2px; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.items th { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 10px; text-align: left; font-size: 10px; color: #64748b; }
        table.items td { padding: 12px 10px; border-bottom: 1px solid #f1f5f9; }
        .total-box { margin-top: 30px; text-align: right; }
        .total-amount { font-size: 24px; font-weight: black; color: #1e293b; }
        .footer { position: fixed; bottom: 40px; left: 40px; right: 40px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; pt: 10px; }
        .draft-watermark { position: absolute; top: 40%; left: 15%; transform: rotate(-45deg); font-size: 80px; color: rgba(239, 68, 68, 0.1); font-weight: bold; z-index: -1; }
    </style>
</head>
<body>

    @if($is_draft)
        <div class="draft-watermark">PRE-FACTURA</div>
    @endif

    <div class="header">
        <table style="width: 100%">
            <tr>
                <td class="logo">SOLU3PL WMS</td>
                <td class="doc-type">
                    {{ $title }}<br>
                    <span style="color: #1e293b; font-size: 14px;">#{{ $invoice->invoice_number ?? 'BORRADOR-' . date('Ymd') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-grid">
        <tr>
            <td>
                <div class="label">Emitido para:</div>
                <div class="value">{{ $client->company_name }}</div>
                <div class="value" style="font-weight: normal; font-size: 11px;">
                    ID Fiscal: {{ $client->tax_id }}<br>
                    {{ $client->address }}
                </div>
            </td>
            <td style="text-align: right;">
                <div class="label">Fecha de Emisión:</div>
                <div class="value">{{ $date }}</div>
                @if(isset($invoice))
                    <div class="label" style="margin-top: 10px;">Periodo:</div>
                    <div class="value">{{ $invoice->period_start->format('d/m/Y') }} - {{ $invoice->period_end->format('d/m/Y') }}</div>
                @endif
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th>DESCRIPCIÓN DEL SERVICIO</th>
                <th>FECHA</th>
                <th style="text-align: right;">MONTO</th>
            </tr>
        </thead>
        <tbody>
            @if(isset($items))
                @foreach($items as $item)
                    <tr>
                        <td>
                            <div style="font-weight: bold;">{{ ucfirst($item->type) }}</div>
                            <div style="font-size: 10px; color: #64748b;">{{ $item->description }}</div>
                        </td>
                        <td style="color: #64748b;">{{ $item->charge_date->format('d/m/Y') }}</td>
                        <td style="text-align: right; font-weight: bold;">${{ number_format($item->amount, 2) }}</td>
                    </tr>
                @endforeach
            @else
                {{-- Caso para facturas emitidas sin cargos detallados en esta vista --}}
                <tr>
                    <td>Consumo de Servicios Logísticos Mensuales</td>
                    <td>{{ $date }}</td>
                    <td style="text-align: right; font-weight: bold;">${{ number_format($invoice->total_amount, 2) }}</td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="total-box">
        <div class="label">Total a Pagar</div>
        <div class="total-amount">${{ number_format($total ?? $invoice->total_amount, 2) }}</div>
    </div>

    <div class="footer">
        Este documento es un reporte informativo generado por el sistema Solu3PL WMS.<br>
        Lara, Venezuela - Soporte: {{ config('mail.from.address') }}
    </div>

</body>
</html>