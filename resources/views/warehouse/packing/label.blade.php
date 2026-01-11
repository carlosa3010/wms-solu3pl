<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiqueta #{{ $order->order_number }}</title>
    <style>
        @media print {
            @page { size: 10cm 15cm; margin: 0; } /* Ajuste para impresora térmica 4x6 aprox */
            body { margin: 0; padding: 10px; }
            .no-print { display: none; }
        }
        body {
            font-family: sans-serif;
            font-size: 12px;
            color: #000;
            background: #fff;
        }
        .container {
            border: 2px solid #000;
            padding: 10px;
            max-width: 380px; /* Ancho seguro */
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .header h1 { margin: 0; font-size: 18px; font-weight: 900; }
        .header p { margin: 2px 0 0; font-size: 10px; text-transform: uppercase; }
        
        .row { display: flex; margin-bottom: 8px; }
        .label { font-weight: bold; width: 80px; flex-shrink: 0; font-size: 10px; text-transform: uppercase; color: #444; }
        .value { font-weight: bold; font-size: 14px; flex-grow: 1; }
        .address { font-size: 12px; line-height: 1.3; }
        
        .section {
            border-top: 1px dashed #000;
            padding-top: 8px;
            margin-top: 8px;
        }
        
        .barcode-box {
            text-align: center;
            margin-top: 15px;
            margin-bottom: 10px;
        }
        .barcode {
            height: 50px;
            background: #000; /* Simulación */
            display: inline-block;
        }
        
        .footer {
            text-align: center;
            font-size: 9px;
            margin-top: 10px;
            border-top: 2px solid #000;
            padding-top: 5px;
        }

        .actions {
            position: fixed;
            top: 20px;
            right: 20px;
            background: #fff;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .btn {
            background: #2563eb;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            display: block;
            margin-bottom: 5px;
            text-align: center;
        }
        .btn-back { background: #64748b; }
    </style>
</head>
<body>

    <div class="actions no-print">
        <a href="javascript:window.print()" class="btn">IMPRIMIR</a>
        <a href="{{ route('warehouse.packing.index') }}" class="btn btn-back">VOLVER</a>
    </div>

    <div class="container">
        <div class="header">
            <p>REMITENTE:</p>
            <h1>{{ strtoupper($order->client->company_name) }}</h1>
            <p>SOLU3PL FULFILLMENT CENTER</p>
        </div>

        <div class="section" style="border: none; padding-top: 0;">
            <p class="label" style="width: 100%; margin-bottom: 2px;">DESTINATARIO (SHIP TO):</p>
            <div class="value" style="font-size: 18px; margin-bottom: 5px;">{{ strtoupper($order->customer_name) }}</div>
            
            <div class="row">
                <span class="value address">
                    {{ strtoupper($order->shipping_address) }}<br>
                    {{ strtoupper($order->city) }}, {{ strtoupper($order->state) }}<br>
                    {{ strtoupper($order->country) }} - CP: {{ $order->customer_zip }}
                </span>
            </div>

            <div class="row">
                <span class="label">TLF:</span>
                <span class="value">{{ $order->customer_phone }}</span>
            </div>
        </div>

        <div class="section">
            <div class="row">
                <span class="label">PEDIDO #:</span>
                <span class="value" style="font-size: 16px;">{{ $order->order_number }}</span>
            </div>
            <div class="row">
                <span class="label">REF. EXT:</span>
                <span class="value">{{ $order->external_ref ?? 'N/A' }}</span>
            </div>
            <div class="row">
                <span class="label">MÉTODO:</span>
                <span class="value">{{ strtoupper($order->shipping_method ?? 'STANDARD') }}</span>
            </div>
        </div>

        <div class="barcode-box">
            <img src="https://bwipjs-api.metafloor.com/?bcid=code128&text={{ $order->order_number }}&scale=2&height=10&includetext" alt="Barcode">
        </div>

        <div class="footer">
            <p>DESPACHADO POR SOLU3PL WMS</p>
            <p>{{ now()->format('d/m/Y H:i') }} | Bulto 1 de 1</p>
        </div>
    </div>

    <script>
        // Auto-imprimir al cargar
        window.onload = function() {
            // window.print(); 
        }
    </script>
</body>
</html>