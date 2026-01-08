<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas Master - {{ $asn->asn_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; -webkit-print-color-adjust: exact; }
            .page-break { page-break-after: always; }
            /* Ajuste para impresoras térmicas de 4x6 pulgadas (100x150mm) */
            .label-container {
                width: 100%;
                height: 100vh;
                border: none;
                margin: 0;
            }
        }
        
        /* Simulación en pantalla */
        .label-container {
            width: 100mm; 
            height: 150mm;
            border: 2px solid #000;
            margin: 0 auto 20px auto;
            background: white;
            position: relative;
            display: flex;
            flex-direction: column;
        }
    </style>
</head>
<body class="bg-gray-200">

    <div class="no-print fixed top-0 left-0 right-0 bg-white shadow-md z-50 px-6 py-3 flex justify-between items-center">
        <div>
            <h1 class="font-bold text-lg text-slate-800">Etiquetas de Recepción (Master)</h1>
            <p class="text-xs text-slate-500">Total Bultos: <span class="font-bold text-blue-600">{{ count($labels) }}</span></p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('admin.receptions.show', $asn->id) }}" class="text-slate-500 font-bold text-sm hover:text-slate-700 px-4 py-2">Volver</a>
            <button onclick="window.print()" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Imprimir Bultos
            </button>
        </div>
    </div>

    <div class="pt-24 pb-12 print:pt-0 print:pb-0">
        @foreach($labels as $label)
            <div class="label-container">
                
                <div class="bg-black text-white text-center py-4">
                    <h1 class="text-3xl font-black uppercase tracking-tight leading-none px-2">
                        {{ Str::limit($label['client_name'], 20) }}
                    </h1>
                </div>

                <div class="flex-1 p-4 flex flex-col justify-between">
                    
                    <div class="flex justify-between items-end border-b-2 border-black pb-2">
                        <div>
                            <p class="text-[10px] font-bold uppercase text-gray-500">ASN Ref.</p>
                            <p class="text-xl font-bold font-mono">{{ $label['asn_number'] }}</p>
                        </div>
                        <div class="text-right">
                            <p class="text-[10px] font-bold uppercase text-gray-500">Fecha</p>
                            <p class="text-lg font-bold">{{ $label['date'] }}</p>
                        </div>
                    </div>

                    <div class="text-center py-4">
                        <p class="text-sm font-bold uppercase text-gray-400 mb-[-5px]">Bulto / Caja</p>
                        <p class="text-[90px] font-black leading-none tracking-tighter">{{ $label['box_number'] }}</p>
                    </div>

                    <div class="text-xs font-mono border-t border-dashed border-gray-400 pt-2 mb-2">
                        <p><strong>Carrier:</strong> {{ $label['carrier'] }}</p>
                        <p><strong>Tracking:</strong> {{ $label['tracking'] }}</p>
                    </div>

                    <div class="flex justify-between items-end border-t-4 border-black pt-2 mt-auto">
                        <div>
                            <p class="font-black text-2xl uppercase">INBOUND</p>
                            <p class="text-[10px] font-bold uppercase tracking-widest">WMS SOLU3PL</p>
                        </div>
                        <div>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=100x100&data={{ $label['qr_data'] }}" alt="QR" class="w-20 h-20">
                        </div>
                    </div>

                </div>
            </div>
            
            <div class="page-break"></div>
        @endforeach
    </div>

</body>
</html>