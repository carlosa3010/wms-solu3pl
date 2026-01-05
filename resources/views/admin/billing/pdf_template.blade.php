<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Factura de Servicios</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .header { width: 100%; border-bottom: 2px solid #ddd; padding-bottom: 20px; margin-bottom: 30px; }
        .logo { font-size: 24px; font-weight: bold; color: #4f46e5; }
        .client-info { margin-bottom: 30px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th { text-align: left; background-color: #f8fafc; padding: 10px; border-bottom: 1px solid #e2e8f0; font-size: 10px; text-transform: uppercase; color: #64748b; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
        .total-row td { font-weight: bold; font-size: 14px; border-top: 2px solid #333; }
        .badge { padding: 2px 6px; border-radius: 4px; font-size: 9px; font-weight: bold; text-transform: uppercase; }
        .draft-watermark { position: fixed; top: 30%; left: 10%; font-size: 100px; color: rgba(200,200,200,0.2); transform: rotate(-45deg); font-weight: bold; }
    </style>
</head>
<body>
    @if($is_draft ?? false)
        <div class="draft-watermark">PRELIMINAR</div>
    @endif

    <div class="header">
        <table style="width: 100%">
            <tr>
                <td>
                    <div class="logo">SOLU3PL</div>
                    <div>Soluciones Logísticas 3PL</div>
                </td>
                <td style="text-align: right;">
                    <h1 style="margin: 0; color: #1e293b; font-size: 20px;">{{ $title }}</h1>
                    <p>Fecha: {{ $date }}</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="client-info">
        <strong>Cliente:</strong> {{ $client->name }}<br>
        <strong>Email:</strong> {{ $client->email }}<br>
        <strong>ID:</strong> {{ $client->code ?? $client->id }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Concepto</th>
                <th>Cantidad</th>
                <th>Precio Unit.</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
                <tr>
                    <td>{{ $item->activity_date->format('d/m/Y') }}</td>
                    <td>
                        {{ $item->concept }}
                        @if($item->reference_type)
                            <br><span style="font-size: 9px; color: #999;">Ref: {{ strtoupper($item->reference_type) }} #{{ $item->reference_id }}</span>
                        @endif
                    </td>
                    <td>{{ $item->quantity }}</td>
                    <td>${{ number_format($item->unit_price, 2) }}</td>
                    <td style="text-align: right;">${{ number_format($item->total_price, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="4" style="text-align: right;">TOTAL A PAGAR:</td>
                <td style="text-align: right;">${{ number_format($total, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top: 50px; font-size: 10px; color: #666; text-align: center;">
        <p>Gracias por confiar en nuestros servicios logísticos.</p>
    </div>
</body>
</html>