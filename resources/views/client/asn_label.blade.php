<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Etiquetas ASN - {{ $asn->asn_number }}</title>
    <!-- Usamos Tailwind para el diseño y JsBarcode para el código de barras -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <style>
        @media print {
            @page { 
                margin: 0; 
                size: 10cm 15cm; 
            }
            body { 
                margin: 0; 
                padding: 0; 
                background: white; 
            }
            .no-print { display: none; }
            .label-page { 
                page-break-after: always; 
                height: 15cm; 
                border: none !important; 
                box-shadow: none !important; 
                margin: 0 !important;
            }
        }
        body { 
            font-family: 'Inter', sans-serif; 
            background: #f1f5f9; 
        }
    </style>
</head>
<body class="p-4 md:p-10">

    <!-- Panel de Control (No se imprime) -->
    <div class="max-w-md mx-auto no-print mb-8 bg-white p-6 rounded-2xl shadow-xl border border-slate-200">
        <h1 class="text-xl font-black text-slate-800 mb-2">Impresión de Etiquetas</h1>
        <p class="text-sm text-slate-500 mb-6">
            Se han generado <strong>{{ $asn->total_packages }}</strong> etiquetas. 
            Por favor, pegue una etiqueta en el exterior de cada caja/bulto.
        </p>
        <button onclick="window.print()" class="w-full bg-blue-600 text-white py-4 rounded-xl font-black uppercase tracking-widest shadow-lg hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
            </svg>
            Imprimir Etiquetas
        </button>
    </div>

    <!-- Generación de Etiquetas por Bulto -->
    @for($i = 1; $i <= $asn->total_packages; $i++)
    <div class="label-page w-[10cm] h-[14.8cm] bg-white mx-auto border-4 border-black mb-10 p-6 flex flex-col relative overflow-hidden shadow-md">
        
        <!-- Encabezado de la Etiqueta -->
        <div class="flex justify-between items-start border-b-4 border-black pb-4 mb-4">
            <div class="font-black text-3xl italic tracking-tighter">SOLU3PL</div>
            <div class="text-right">
                <div class="text-[10px] font-black uppercase leading-none">Bulto / Caja</div>
                <div class="text-4xl font-black leading-none">{{ $i }} / {{ $asn->total_packages }}</div>
            </div>
        </div>

        <!-- Información del ASN -->
        <div class="flex-1 space-y-6">
            <div>
                <span class="text-[10px] font-black uppercase text-slate-400 block tracking-[0.2em] mb-1">ID de Aviso de Envío</span>
                <h2 class="text-3xl font-black text-black leading-none">{{ $asn->asn_number }}</h2>
            </div>

            <div class="grid grid-cols-2 gap-4 border-y-2 border-slate-100 py-4">
                <div>
                    <span class="text-[9px] font-black uppercase text-slate-400 block mb-1">Cliente</span>
                    <p class="text-sm font-bold text-black uppercase leading-tight">
                        {{ $asn->client->company_name ?? $asn->client->name }}
                    </p>
                </div>
                <div>
                    <span class="text-[9px] font-black uppercase text-slate-400 block mb-1">Fecha Emisión</span>
                    <p class="text-sm font-bold text-black">{{ $asn->created_at->format('d/m/Y') }}</p>
                </div>
            </div>

            <!-- Referencia Externa -->
            <div class="bg-slate-50 p-3 rounded-lg border-2 border-slate-200">
                <span class="text-[8px] font-black uppercase text-slate-500 block mb-1 tracking-widest">Referencia del Cliente</span>
                <p class="text-sm font-mono font-bold text-black truncate">{{ $asn->reference_number ?? 'N/A' }}</p>
            </div>

            <!-- Código de Barras para Escaneo en Recepción -->
            <div class="flex flex-col items-center justify-center pt-4">
                <svg class="barcode-gen" 
                     jsbarcode-value="{{ $asn->asn_number }}" 
                     jsbarcode-format="CODE128" 
                     jsbarcode-height="80" 
                     jsbarcode-fontSize="14"></svg>
                <p class="text-[8px] font-black text-slate-400 mt-2 uppercase tracking-[0.4em]">Scan to Start Receiving</p>
            </div>
        </div>

        <!-- Pie de Etiqueta -->
        <div class="border-t-2 border-slate-200 pt-4 mt-auto text-center">
            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Documento de Control Logístico Solu3PL</p>
        </div>
    </div>
    @endfor

    <script>
        // Inicializar los códigos de barras al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            JsBarcode(".barcode-gen").init();
        });
    </script>
</body>
</html>