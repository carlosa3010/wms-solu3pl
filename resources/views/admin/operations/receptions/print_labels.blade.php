<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas ASN - {{ $asn->asn_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; }
            .page-break { page-break-after: always; }
        }
        
        /* Estilo Etiqueta Zebra / Térmica (4x6 pulgadas aprox simulado) */
        .label-container {
            width: 100mm; 
            height: 150mm;
            border: 1px dashed #ccc;
            margin: 0 auto 10px auto;
            background: white;
            padding: 10px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            page-break-inside: avoid;
        }

        @media print {
            .label-container {
                border: none;
                margin: 0;
                width: 100%;
                height: 100vh; /* Ajustar según tamaño papel real */
            }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800">

    <div class="no-print fixed top-0 left-0 right-0 bg-white shadow-md z-50 px-6 py-3 flex justify-between items-center">
        <div>
            <h1 class="font-bold text-lg text-slate-800">Impresión de Etiquetas</h1>
            <p class="text-xs text-slate-500">ASN: <span class="font-mono font-bold">{{ $asn->asn_number }}</span> | Bultos Totales Doc: <span class="font-bold text-blue-600">{{ $asn->total_packages }}</span></p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.receptions.show', $asn->id) }}" class="text-slate-500 font-bold text-sm hover:text-slate-700 px-4 py-2">Volver</a>
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Imprimir Todo
            </button>
        </div>
    </div>

    <div class="pt-24 pb-12">
        <div class="max-w-4xl mx-auto">
            
            @if(count($labels) == 0)
                <div class="text-center py-20">
                    <p class="text-slate-400 text-xl font-bold">No hay etiquetas generadas.</p>
                    <p class="text-slate-400">Verifique que la ASN tenga productos y cantidades asignadas.</p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 print:block">
                @foreach($labels as $label)
                    <div class="label-container relative overflow-hidden">
                        
                        <div class="border-b-2 border-black pb-2 mb-2 flex justify-between items-start">
                            <div>
                                <p class="text-[10px] font-bold uppercase">RECEPCIÓN / INBOUND</p>
                                <p class="text-xl font-black">{{ $label['asn_number'] }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-bold">{{ date('d/m/Y') }}</p>
                                <p class="text-[10px] uppercase">Box ID</p>
                            </div>
                        </div>

                        <div class="flex-1 flex flex-col justify-center text-center py-2">
                            <p class="text-sm font-bold uppercase mb-1">{{ Str::limit($label['product_name'], 40) }}</p>
                            <h2 class="text-3xl font-black tracking-tighter mb-1">{{ $label['sku'] }}</h2>
                            <p class="text-xs uppercase">SKU / Part Number</p>
                        </div>

                        <div class="border-t-2 border-black pt-4 flex justify-between items-end">
                            <div>
                                <p class="text-[10px] font-bold uppercase mb-1">Destino / Ubicación</p>
                                <div class="bg-black text-white px-3 py-1 text-xl font-mono font-bold inline-block">
                                    {{ $label['location_code'] }}
                                </div>
                            </div>
                            <div class="text-center">
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data={{ $label['qr_data'] }}" alt="QR" class="w-20 h-20">
                            </div>
                        </div>

                        <div class="mt-4 pt-2 border-t border-dashed border-gray-400 flex justify-between items-center text-xs font-bold">
                            <span>CNT: {{ $label['counter'] }}</span>
                            <span>QTY: {{ $label['quantity'] }}</span>
                        </div>

                    </div>
                    
                    <div class="page-break"></div>
                @endforeach
            </div>

        </div>
    </div>

</body>
</html>