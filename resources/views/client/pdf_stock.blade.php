<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario - Solu3PL</title>
    <style>
        @page {
            margin: 1cm;
        }
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10pt;
            color: #334155;
            line-height: 1.4;
        }
        .header {
            border-bottom: 2px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .company-name {
            font-size: 24pt;
            font-weight: bold;
            color: #2563eb;
            margin: 0;
            text-transform: uppercase;
        }
        .report-title {
            font-size: 14pt;
            color: #64748b;
            margin: 5px 0 0 0;
        }
        .info-grid {
            width: 100%;
            margin-bottom: 20px;
        }
        .info-label {
            font-weight: bold;
            color: #475569;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8pt;
            text-align: left;
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: top;
        }
        .sku-text {
            font-family: 'Courier', monospace;
            color: #64748b;
            font-size: 9pt;
        }
        .qty-text {
            font-weight: bold;
            text-align: center;
        }
        .status-badge {
            font-size: 8pt;
            font-weight: bold;
            padding: 2px 6px;
            border-radius: 10px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            font-size: 8pt;
            color: #94a3b8;
            text-align: center;
            padding: 10px 0;
            border-top: 1px solid #e2e8f0;
        }
        .summary-box {
            margin-top: 20px;
            padding: 10px;
            background-color: #f8fafc;
            border-radius: 8px;
            text-align: right;
        }
    </style>
</head>
<body>

    <div class="header">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="border: none; padding: 0;">
                    <h1 class="company-name">Solu3PL</h1>
                    <p class="report-title">Resumen de Inventario Actual</p>
                </td>
                <td style="border: none; padding: 0; text-align: right;">
                    <p style="margin: 0;"><strong>Fecha:</strong> {{ now()->format('d/m/Y H:i') }}</p>
                    <p style="margin: 0;"><strong>Cliente:</strong> {{ auth()->user()->name }}</p>
                </td>
            </tr>
        </table>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 35%;">Producto / SKU</th>
                <th style="width: 15%;">Ubicación</th>
                <th style="width: 30%;">Bodega / Sucursal</th>
                <th style="width: 10%; text-align: center;">Cant.</th>
                <th style="width: 10%; text-align: center;">Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stocks as $stock)
            <tr>
                <td>
                    <strong>{{ $stock->product->name ?? 'N/A' }}</strong><br>
                    <span class="sku-text">{{ $stock->product->sku ?? 'S/S' }}</span>
                </td>
                <td>
                    <span class="sku-text">{{ $stock->location?->code ?? 'N/A' }}</span>
                </td>
                <td>
                    {{ $stock->location?->warehouse?->name ?? 'N/A' }}<br>
                    <small style="color: #94a3b8;">{{ $stock->location?->warehouse?->branch?->name ?? '' }}</small>
                </td>
                <td class="qty-text">
                    {{ number_format($stock->quantity) }}
                </td>
                <td style="text-align: center;">
                    @if($stock->quantity > ($stock->product->min_stock_level ?? 0))
                        <span style="color: #10b981;">OK</span>
                    @else
                        <span style="color: #f59e0b;">BAJO</span>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-box">
        <strong>Total SKUs en reporte:</strong> {{ $stocks->unique('product_id')->count() }} | 
        <strong>Total Unidades:</strong> {{ number_format($stocks->sum('quantity')) }}
    </div>

    <div class="footer">
        Este documento es un reporte generado automáticamente por el portal Solu3PL WMS.
        &copy; {{ date('Y') }} Solu3PL - Todos los derechos reservados.
    </div>

</body>
</html>