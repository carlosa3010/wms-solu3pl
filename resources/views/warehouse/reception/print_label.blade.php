<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Label {{ $product->sku }}</title>
    <style>
        /* CONFIGURACIÓN CRÍTICA PARA IMPRESORA TÉRMICA */
        @page {
            size: 4in 2in; /* Tamaño estándar de etiqueta (Ajustar si es 100mm x 50mm) */
            margin: 0; /* Elimina encabezados y pies de página del navegador */
        }
        
        body {
            margin: 0;
            padding: 2mm; /* Pequeño margen de seguridad */
            width: 4in;
            height: 2in;
            box-sizing: border-box;
            font-family: 'Arial', sans-serif;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            overflow: hidden; /* Evita que salga una segunda hoja en blanco */
            background-color: white;
        }

        .sku {
            font-size: 14pt;
            font-weight: 900;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Contenedor del código de barras para forzar ajuste */
        .barcode-container {
            width: 95%;
            margin: 2px 0;
        }
        
        #barcode {
            width: 100%;
            height: auto;
            max-height: 50px; /* Altura máxima para no comerse el texto */
        }

        .name {
            font-size: 9pt;
            line-height: 1.1;
            max-height: 2.2em; /* Máximo 2 líneas de texto */
            overflow: hidden;
            margin-top: 4px;
            font-weight: 600;
        }

        .meta {
            font-size: 7pt;
            color: #000;
            margin-top: 4px;
            border-top: 1px solid #000;
            padding-top: 2px;
            width: 90%;
        }

        /* OCULTAR ELEMENTOS AL IMPRIMIR */
        @media print {
            .no-print { display: none !important; }
            body { -webkit-print-color-adjust: exact; }
        }

        /* Estilos del botón para la pantalla */
        .btn-print {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #2563eb;
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 50px;
            font-size: 16px;
            font-weight: bold;
            box-shadow: 0 4px 6px rgba(0,0,0,0.2);
            cursor: pointer;
            z-index: 100;
        }
        .btn-print:hover { background: #1d4ed8; }
    </style>
</head>
<body>

    <div class="sku">{{ $product->sku }}</div>
    
    <div class="barcode-container">
        <svg id="barcode"></svg>
    </div>
    
    <div class="name">{{ Str::limit($product->name, 45) }}</div>

    <div class="meta">
        RECIBIDO: {{ date('d/m/Y H:i') }} | WMS
    </div>

    <button onclick="window.print()" class="btn-print no-print">
        🖨️ IMPRIMIR
    </button>

    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            JsBarcode("#barcode", "{{ $product->sku }}", {
                format: "CODE128",
                width: 2,       // Grosor de las barras
                height: 50,     // Altura de las barras
                displayValue: false, // No repetir el texto abajo del código (ya lo pusimos arriba más grande)
                margin: 0
            });

            // Opcional: Imprimir automáticamente al cargar
            // setTimeout(() => window.print(), 500); 
        });
    </script>
</body>
</html>