<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Picking List - {{ $order->order_number }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: white; padding: 0; margin: 0; }
            .print-container { box-shadow: none; border: none; width: 100%; max-width: none; }
            @page { margin: 1cm; size: letter; }
        }
        body { font-family: 'Courier New', Courier, monospace; }
        .row-item:nth-child(even) { background-color: #f8fafc; }
    </style>
</head>
<body class="bg-slate-100 p-4 md:p-10 text-slate-900">

    <!-- Herramientas de impresión -->
    <div class="no-print max-w-4xl mx-auto mb-6 bg-slate-800 text-white p-4 rounded-xl flex justify-between items-center shadow-lg">
        <div>
            <h1 class="font-bold text-lg"><i class="fa-solid fa-print mr-2"></i> Orden de Picking</h1>
            <p class="text-[10px] text-slate-400 uppercase tracking-widest">Documento de recolección para operarios de bodega</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.close()" class="px-4 py-2 bg-slate-700 hover:bg-slate-600 rounded-lg text-xs font-bold transition">Cerrar</button>
            <button onclick="window.print()" class="px-6 py-2 bg-blue-600 hover:bg-blue-500 rounded-lg text-xs font-bold transition flex items-center gap-2">
                <i class="fa-solid fa-file-pdf"></i> Imprimir Documento
            </button>
        </div>
    </div>

    <!-- Documento Principal -->
    <div class="print-container max-w-4xl mx-auto bg-white p-8 border border-slate-300 shadow-sm">
        
        <!-- Encabezado Logístico -->
        <div class="flex justify-between items-start border-b-4 border-black pb-6 mb-8">
            <div>
                <h1 class="text-3xl font-black uppercase tracking-tighter mb-1">Picking List</h1>
                <p class="text-xl font-mono font-bold text-blue-600">{{ $order->order_number }}</p>
                <div class="mt-4 space-y-1 text-xs">
                    <p><strong>CLIENTE:</strong> {{ strtoupper($order->client->company_name) }}</p>
                    <p><strong>FECHA:</strong> {{ $order->created_at->format('d/m/Y H:i') }}</p>
                    <p><strong>SEDE ORIGEN:</strong> {{ strtoupper($order->branch->name ?? 'PRINCIPAL') }}</p>
                </div>
            </div>
            <div class="text-right flex flex-col items-end">
                <canvas id="order-qr" class="mb-2"></canvas>
                <p class="text-[9px] font-mono text-slate-500">REF: {{ $order->external_ref ?? 'S/R' }}</p>
            </div>
        </div>

        <!-- Datos del Destino -->
        <div class="grid grid-cols-2 gap-8 mb-10">
            <div class="p-4 border-2 border-black rounded-lg">
                <h4 class="text-[10px] font-bold bg-black text-white px-2 py-0.5 rounded inline-block uppercase mb-2">Destinatario Final</h4>
                <p class="text-sm font-black">{{ $order->customer_name }}</p>
                <p class="text-xs text-slate-600 mt-1 leading-tight">{{ $order->shipping_address }}</p>
                <p class="text-xs font-bold mt-1 uppercase">{{ $order->city }}, {{ $order->state }}</p>
                <p class="text-xs font-mono font-bold text-blue-600 mt-3 border-t border-slate-100 pt-1">ID: {{ $order->customer_id_number }}</p>
            </div>
            <div class="p-4 border-2 border-dashed border-slate-300 rounded-lg">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase mb-2">Instrucciones / Notas</h4>
                <p class="text-xs italic text-slate-600">{{ $order->notes ?? 'Sin instrucciones adicionales.' }}</p>
                <div class="mt-4 flex justify-between items-end">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Courier: <span class="text-black font-black uppercase">{{ $order->shipping_method ?? 'N/A' }}</span></p>
                </div>
            </div>
        </div>

        <!-- Listado de Picking -->
        <div class="mb-12">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-slate-100 text-slate-800 text-[10px] uppercase font-bold tracking-widest">
                        <th class="p-3 text-left border border-slate-300">Ubicación (Bin)</th>
                        <th class="p-3 text-left border border-slate-300">Producto / SKU</th>
                        <th class="p-3 text-center border border-slate-300">Cant.</th>
                        <th class="p-3 text-center border border-slate-300 w-16">OK</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        @if($item->allocations->count() > 0)
                            @foreach($item->allocations as $alloc)
                            <tr class="row-item border-b border-slate-200">
                                <td class="p-3">
                                    <div class="bg-blue-600 text-white font-mono font-black text-center py-1.5 rounded shadow-sm text-sm">
                                        {{ $alloc->location->code }}
                                    </div>
                                    <p class="text-[8px] text-center mt-1 font-bold text-slate-400 uppercase">Pasillo: {{ $alloc->location->aisle }}</p>
                                </td>
                                <td class="p-3">
                                    <p class="font-black text-sm text-slate-800 leading-tight">{{ $item->product->name }}</p>
                                    <p class="text-xs font-mono font-bold text-blue-500 uppercase">{{ $item->product->sku }}</p>
                                </td>
                                <td class="p-3 text-center">
                                    <span class="text-2xl font-black">{{ $alloc->quantity }}</span>
                                    <p class="text-[8px] font-bold uppercase text-slate-400">unidades</p>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="w-8 h-8 border-2 border-black mx-auto rounded"></div>
                                </td>
                            </tr>
                            @endforeach
                        @else
                            {{-- Fila con advertencia pero visible para el operario --}}
                            <tr class="bg-red-50 border-b border-slate-200">
                                <td class="p-3">
                                    <div class="bg-red-600 text-white font-mono font-black text-center py-1.5 rounded shadow-sm text-xs uppercase">
                                        Manual
                                    </div>
                                    <p class="text-[7px] text-center mt-1 font-bold text-red-500 uppercase">Sin Stock en Sistema</p>
                                </td>
                                <td class="p-3">
                                    <p class="font-black text-sm text-slate-800 leading-tight opacity-60">{{ $item->product->name }}</p>
                                    <p class="text-xs font-mono font-bold text-red-500 uppercase">{{ $item->product->sku }}</p>
                                </td>
                                <td class="p-3 text-center">
                                    <span class="text-2xl font-black text-red-600">{{ $item->requested_quantity }}</span>
                                    <p class="text-[8px] font-bold uppercase text-red-400">solicitadas</p>
                                </td>
                                <td class="p-3 text-center">
                                    <div class="w-8 h-8 border-2 border-red-300 mx-auto rounded border-dashed"></div>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Espacios para Firmas -->
        <div class="grid grid-cols-3 gap-8 mt-20 text-center">
            <div class="border-t-2 border-black pt-2">
                <p class="text-[9px] font-bold uppercase">Operador de Picking</p>
            </div>
            <div class="border-t-2 border-black pt-2">
                <p class="text-[9px] font-bold uppercase">Verificador (Packing)</p>
            </div>
            <div class="border-t-2 border-black pt-2">
                <p class="text-[9px] font-bold uppercase">Transporte / Courier</p>
            </div>
        </div>

        <!-- Footer del Sistema -->
        <div class="mt-16 pt-4 border-t border-slate-100 flex justify-between items-center opacity-40">
             <div class="flex items-center gap-2 font-black text-[10px] text-slate-800 uppercase tracking-widest">
                <i class="fa-solid fa-cube"></i>
                <span>Solu3PL WMS Engine</span>
            </div>
            <p class="text-[8px] font-mono">PRINT_SESSION: {{ strtoupper(uniqid()) }}</p>
        </div>
    </div>

    <script>
        window.onload = function() {
            new QRious({
                element: document.getElementById('order-qr'),
                value: '{{ $order->order_number }}',
                size: 80,
                level: 'M'
            });
        };
    </script>
</body>
</html>