<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas ASN {{ $asn->asn_number }}</title>
    
    <!-- Estilos y Librerías -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        /* Estilos Generales */
        body { 
            background: #e2e8f0; 
            font-family: sans-serif; 
            -webkit-print-color-adjust: exact; 
            print-color-adjust: exact; 
        }

        /* Contenedor de la Etiqueta (Visualización Web) */
        .label-container {
            width: 10cm; /* Ancho estándar aprox 4 pulgadas */
            height: 5cm; /* Alto estándar aprox 2 pulgadas */
            background: white;
            padding: 4px; /* Padding interno seguro */
            margin: 10px auto;
            border: 1px solid #cbd5e1;
            display: flex;
            gap: 4px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            box-sizing: border-box;
        }

        /* Configuración de Impresión (Térmica / Zebra) */
        @media print {
            @page {
                size: 10cm 5cm; /* Define el tamaño de página al tamaño de la etiqueta */
                margin: 0; /* Sin márgenes de página */
            }
            
            body { 
                background: white; 
                margin: 0; 
                padding: 0; 
            }
            
            .no-print { 
                display: none !important; 
            }
            
            .print-area {
                padding: 0;
                margin: 0;
                display: block; 
            }
            
            .label-container {
                width: 100%; /* Ocupa todo el papel */
                height: 100%; /* Ocupa todo el papel */
                border: none; /* Sin borde para impresión */
                margin: 0; /* Sin margen externo */
                box-shadow: none;
                page-break-after: always; /* Cortar después de cada etiqueta */
                break-inside: avoid;
                float: none;
            }
        }
    </style>
</head>
<body>

    <!-- Barra de Herramientas (No sale en impresión) -->
    <div class="no-print fixed top-0 left-0 w-full bg-slate-800 text-white p-4 flex justify-between items-center shadow-lg z-50">
        <div>
            <h1 class="font-bold text-lg flex items-center gap-2">
                <i class="fa-solid fa-tags"></i> Etiquetas Unitarias - ASN {{ $asn->asn_number }}
            </h1>
            <p class="text-xs text-slate-400">
                Total a imprimir: <span class="font-bold text-white text-sm bg-slate-700 px-2 rounded">{{ count($labels) }}</span> etiquetas
            </p>
            <p class="text-[10px] text-yellow-400 mt-1"><i class="fa-solid fa-triangle-exclamation"></i> Configure su impresora a tamaño 100mm x 50mm (4"x2")</p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.close()" class="bg-slate-600 px-4 py-2 rounded text-sm font-bold hover:bg-slate-700 transition">
                Cerrar
            </button>
            <button onclick="window.print()" class="bg-blue-600 px-6 py-2 rounded text-sm font-bold hover:bg-blue-700 flex items-center gap-2 shadow-lg transition">
                <i class="fa-solid fa-print"></i> Imprimir Todo
            </button>
        </div>
    </div>

    <!-- Espaciador para no tapar contenido -->
    <div class="h-24 no-print"></div>

    <!-- Área de Etiquetas -->
    <div class="print-area p-4 flex flex-wrap justify-center gap-4">
        @forelse($labels as $index => $label)
            <div class="label-container rounded-sm"> <!-- rounded-sm mejor para térmica -->
                
                <!-- Banda Lateral de Color (Identificación Visual) -->
                <!-- Reducida un poco para ahorrar espacio en etiqueta pequeña -->
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-blue-600"></div>

                <!-- Sección Izquierda: QR de Destino -->
                <div class="flex flex-col items-center justify-center w-24 border-r border-slate-200 pr-1 ml-2 h-full">
                    <canvas id="qr-{{ $index }}"></canvas>
                    <!-- Texto QR muy pequeño para referencia visual -->
                    <span class="text-[6px] font-mono font-bold mt-0.5 text-center break-all leading-none text-slate-600 w-full overflow-hidden" style="max-height: 2em;">
                        {{ $label['qr_data'] }}
                    </span>
                </div>

                <!-- Sección Derecha: Información -->
                <div class="flex-1 flex flex-col justify-between pl-1.5 py-0.5 h-full">
                    
                    <!-- Header Producto -->
                    <div>
                        <div class="flex justify-between items-start mb-0.5">
                            <div class="flex-1 mr-1">
                                <p class="text-[6px] text-slate-500 font-bold uppercase tracking-wider mb-0">SKU / Item</p>
                                <p class="text-sm font-mono font-black text-slate-900 leading-none truncate">{{ $label['sku'] }}</p>
                            </div>
                            <span class="text-[7px] font-bold bg-slate-100 px-1 py-0.5 rounded text-slate-600 border border-slate-300 whitespace-nowrap">
                                {{ $label['asn_number'] }}
                            </span>
                        </div>
                        
                        <!-- Nombre del producto con truncado inteligente -->
                        <h2 class="text-[10px] font-bold text-slate-800 leading-tight mb-0.5 line-clamp-2 h-[2.2em] overflow-hidden">
                            {{ $label['product_name'] }}
                        </h2>
                    </div>

                    <!-- Footer Ubicación y Conteo -->
                    <div class="border-t-2 border-dashed border-slate-300 pt-1 mt-auto">
                        <div class="flex justify-between items-end">
                            <div>
                                <p class="text-[6px] text-slate-500 font-bold uppercase mb-0">Destino (Bin)</p>
                                <p class="text-sm font-black text-slate-900 leading-none bg-yellow-100 px-1 rounded border border-yellow-200 inline-block">
                                    {{ $label['location_code'] }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-[6px] text-slate-500 font-bold uppercase mb-0">Unidad</p>
                                <div class="flex items-baseline justify-end gap-0.5">
                                    <span class="text-xl font-black text-blue-700 leading-none">{{ explode('/', $label['counter'])[0] }}</span>
                                    <span class="text-[8px] font-bold text-slate-400">/ {{ explode('/', $label['counter'])[1] }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="w-full text-center py-20">
                <i class="fa-solid fa-triangle-exclamation text-4xl text-slate-300 mb-4"></i>
                <p class="text-slate-500 font-bold text-lg">No hay etiquetas para generar.</p>
                <p class="text-sm text-slate-400">Verifique que la ASN tenga productos y cantidades asignadas.</p>
            </div>
        @endforelse
    </div>

    <!-- Script de Generación de QRs -->
    <script>
        window.onload = function() {
            // Datos inyectados desde el controlador
            const labels = @json($labels);

            // Generar un QR por cada etiqueta
            labels.forEach((label, index) => {
                new QRious({
                    element: document.getElementById('qr-' + index),
                    value: label.qr_data, // El código que se escaneará
                    size: 85,             // Tamaño optimizado para 10cm de ancho
                    level: 'M',           // Nivel medio para legibilidad en térmicas
                    background: 'white',
                    foreground: 'black'
                });
            });
        };
    </script>
</body>
</html>