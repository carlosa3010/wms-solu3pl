
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas Bodega {{ $warehouse->code }}</title>
    
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        body { background: #e2e8f0; font-family: sans-serif; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        
        /* Estilos Base de Etiqueta */
        .label-card {
            background: white;
            border: 1px solid #cbd5e1;
            margin: 10px;
            padding: 10px;
            display: inline-flex;
            page-break-inside: avoid;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }

        /* TIPOS DE ETIQUETAS */
        
        /* 1. Bodega / Pasillo (Grande - Media Carta aprox o 4x6") */
        .type-WAREHOUSE, .type-AISLE {
            width: 15cm;
            height: 10cm;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            border: 2px solid #000;
        }

        /* 2. Rack (Mediana) */
        .type-RACK {
            width: 10cm;
            height: 5cm; /* 4x2" */
        }

        /* 3. Bin (Pequeña estándar) */
        .type-BIN {
            width: 10cm;
            height: 3.5cm; /* Un poco más angosta para estantería */
        }

        @media print {
            body { background: white; margin: 0; }
            .no-print { display: none !important; }
            .label-card {
                margin: 0;
                box-shadow: none;
                border: 1px dashed #ddd; /* Guía corte */
                float: left;
                page-break-after: always;
            }
            /* Forzar salto de página entre tipos grandes */
            .type-WAREHOUSE, .type-AISLE { page-break-after: always; }
        }
    </style>
</head>
<body>

    <!-- Barra Herramientas -->
    <div class="no-print fixed top-0 left-0 w-full bg-slate-900 text-white p-4 flex justify-between items-center shadow-lg z-50">
        <div>
            <h1 class="font-bold text-lg"><i class="fa-solid fa-print mr-2"></i> Rotulación: {{ $warehouse->name }}</h1>
            <p class="text-xs text-slate-400">Total: <span class="text-white font-bold">{{ count($labels) }}</span> etiquetas generadas</p>
        </div>
        <div class="flex gap-3">
            <button onclick="window.close()" class="bg-slate-700 px-4 py-2 rounded text-sm font-bold hover:bg-slate-600">Cerrar</button>
            <button onclick="window.print()" class="bg-blue-600 px-6 py-2 rounded text-sm font-bold hover:bg-blue-500 flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Imprimir Todo
            </button>
        </div>
    </div>
    <div class="h-20 no-print"></div>

    <!-- Contenedor Etiquetas -->
    <div class="p-8 flex flex-wrap gap-4 justify-center">
        @foreach($labels as $index => $label)
            
            <div class="label-card type-{{ $label['type'] }}">
                
                {{-- DISEÑO PARA PASILLOS Y BODEGAS (Grande) --}}
                @if(in_array($label['type'], ['WAREHOUSE', 'AISLE']))
                    <div class="absolute top-0 left-0 w-full h-4 bg-black"></div>
                    <h1 class="text-4xl font-black uppercase mb-2">{{ $label['title'] }}</h1>
                    <p class="text-xl text-slate-600 mb-6">{{ $label['subtitle'] }}</p>
                    <canvas id="qr-{{ $index }}" class="border-4 border-white shadow-sm"></canvas>
                    <p class="text-lg font-mono font-bold mt-2">{{ $label['code'] }}</p>
                
                {{-- DISEÑO PARA RACKS Y BINES (Horizontal) --}}
                @else
                    <div class="absolute left-0 top-0 bottom-0 w-2 {{ $label['type'] == 'RACK' ? 'bg-blue-600' : 'bg-green-500' }}"></div>
                    
                    <!-- QR Izquierda -->
                    <div class="w-32 border-r border-slate-200 pr-2 mr-2 flex flex-col items-center justify-center">
                        <canvas id="qr-{{ $index }}"></canvas>
                    </div>

                    <!-- Texto Derecha -->
                    <div class="flex-1 flex flex-col justify-center">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">{{ $label['type'] }}</p>
                        <h2 class="{{ $label['type'] == 'RACK' ? 'text-2xl' : 'text-xl' }} font-black text-slate-800 leading-none mb-1">
                            {{ $label['title'] }}
                        </h2>
                        @if($label['type'] == 'BIN')
                            <p class="text-sm font-mono text-slate-600 bg-slate-100 px-1 rounded w-fit">{{ $label['code'] }}</p>
                        @else
                            <p class="text-xs text-slate-500">{{ $label['subtitle'] }}</p>
                        @endif
                    </div>
                @endif

            </div>

        @endforeach
    </div>

    <script>
        window.onload = function() {
            const labels = @json($labels);
            labels.forEach((label, index) => {
                const size = label.type === 'BIN' || label.type === 'RACK' ? 100 : 200;
                new QRious({
                    element: document.getElementById('qr-' + index),
                    value: label.qr_data,
                    size: size,
                    level: 'H'
                });
            });
        };
    </script>
</body>
</html>