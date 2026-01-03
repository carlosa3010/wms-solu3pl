<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Pedido {{ $order->order_number }} - Solu3PL</title>
    <style>
        @page { margin: 1cm; }
        body { font-family: 'Helvetica', sans-serif; font-size: 10pt; color: #334155; }
        .header { border-bottom: 2px solid #2563eb; padding-bottom: 15px; margin-bottom: 20px; }
        .company-info { float: left; width: 50%; }
        .order-info { float: right; width: 40%; text-align: right; }
        .company-name { font-size: 22pt; font-weight: bold; color: #2563eb; margin: 0; }
        .clear { clear: both; }
        .section-title { background: #f1f5f9; padding: 5px 10px; font-weight: bold; text-transform: uppercase; font-size: 9pt; margin-top: 20px; border-radius: 4px; }
        .grid { width: 100%; margin-top: 10px; }
        .grid td { vertical-align: top; width: 50%; padding-bottom: 10px; }
        .label { font-weight: bold; color: #64748b; font-size: 8pt; display: block; text-transform: uppercase; }
        table.items { width: 100%; border-collapse: collapse; margin-top: 20px; }
        table.items th { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 10px; text-align: left; font-size: 9pt; }
        table.items td { padding: 10px; border-bottom: 1px solid #f1f5f9; }
        .sku { font-family: monospace; color: #64748b; font-size: 9pt; }
        .status-badge { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 8pt; font-weight: bold; background: #e2e8f0; }
        .footer { position: fixed; bottom: 0; width: 100%; font-size: 8pt; color: #94a3b8; text-align: center; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>

    <div class="header">
        <div class="company-info">
            <h1 class="company-name">SOLU3PL</h1>
            <p style="margin: 5px 0;">Operaciones Logísticas Avanzadas</p>
        </div>
        <div class="order-info">
            <p style="margin: 0; font-size: 14pt; font-weight: bold;">ORDEN DE SALIDA</p>
            <p style="margin: 2px 0; color: #2563eb; font-weight: bold;">#{{ $order->order_number }}</p>
            <p style="margin: 0; font-size: 9pt;">Fecha: {{ $order->created_at->format('d/m/Y H:i') }}</p>
        </div>
        <div class="clear"></div>
    </div>

    <div class="section-title">Información de las Partes</div>
    <table class="grid">
        <tr>
            <td>
                <span class="label">Remitente (Cliente)</span>
                <strong>{{ $order->client->name }}</strong><br>
                {{ $order->client->email }}
            </td>
            <td>
                <span class="label">Destinatario / Entrega</span>
                <strong>{{ $order->customer_name }}</strong><br>
                {{ $order->customer_address }}<br>
                {{ $order->customer_city }}, {{ $order->customer_state }} {{ $order->customer_zip }}<br>
                {{ $order->customer_country }}<br>
                Tel: {{ $order->customer_phone }}
            </td>
        </tr>
    </table>

    <div class="section-title">Logística</div>
    <table class="grid">
        <tr>
            <td>
                <span class="label">Método de Envío</span>
                {{ strtoupper($order->shipping_method ?? 'ESTÁNDAR') }}
            </td>
            <td>
                <span class="label">Referencia Externa</span>
                {{ $order->reference_number ?? 'SIN REFERENCIA' }}
            </td>
        </tr>
    </table>

    <table class="items">
        <thead>
            <tr>
                <th style="width: 15%;">SKU</th>
                <th style="width: 65%;">Descripción del Producto</th>
                <th style="width: 20%; text-align: center;">Cantidad</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td class="sku">{{ $item->product->sku }}</td>
                <td>{{ $item->product->name }}</td>
                <td style="text-align: center;"><strong>{{ number_format($item->quantity) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2" style="text-align: right; padding: 15px;"><strong>TOTAL UNIDADES</strong></td>
                <td style="text-align: center; border-bottom: 2px solid #2563eb; padding: 15px;">
                    <strong>{{ number_format($order->items->sum('quantity')) }}</strong>
                </td>
            </tr>
        </tfoot>
    </table>

    @if($order->notes)
    <div style="margin-top: 30px; padding: 15px; background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px;">
        <span class="label" style="color: #92400e;">Notas de Despacho:</span>
        <p style="margin: 5px 0; font-size: 9pt; color: #92400e;">{{ $order->notes }}</p>
    </div>
    @endif

    <div class="footer">
        Este documento es un comprobante de orden de salida generado por Solu3PL WMS.<br>
        &copy; {{ date('Y') }} Solu3PL - Sistema de Gestión de Almacenes.
    </div>

</body>
</html>