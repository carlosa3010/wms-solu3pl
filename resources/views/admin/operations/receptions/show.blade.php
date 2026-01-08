@extends('layouts.admin')

@section('content')
<div class="p-6">
    
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Detalle de Recepción #{{ $asn->asn_number }}</h1>
            <p class="text-gray-500">Cliente: <span class="font-bold">{{ $asn->client->company_name }}</span></p>
        </div>
        
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.receptions.print_labels', $asn->id) }}" target="_blank" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 font-bold rounded-lg shadow-sm hover:bg-gray-50 transition flex items-center gap-2">
                <i class="fa-solid fa-print text-gray-500"></i> Etiquetas Master
            </a>

            <span class="px-4 py-2 rounded-lg font-bold text-sm 
                {{ $asn->status == 'completed' ? 'bg-green-100 text-green-800' : 
                  ($asn->status == 'in_process' ? 'bg-blue-100 text-blue-800' : 
                  ($asn->status == 'partial' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800')) }}">
                {{ strtoupper($asn->status) }}
            </span>
        </div>
    </div>

    @if($asn->notes)
        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 rounded-r-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fa-solid fa-triangle-exclamation text-yellow-400 mt-0.5"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-bold text-yellow-800">Reporte de Operaciones:</h3>
                    <p class="text-sm text-yellow-700 mt-1 whitespace-pre-line">
                        {!! e($asn->notes) !!}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Cumplimiento</h3>
            <div class="mt-2 flex items-end gap-2">
                <span class="text-3xl font-black {{ $progress >= 100 ? 'text-green-600' : 'text-blue-600' }}">{{ $progress }}%</span>
                <span class="text-gray-400 text-sm mb-1">Recibido</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2 mt-3 overflow-hidden">
                <div class="h-full {{ $progress >= 100 ? 'bg-green-500' : 'bg-blue-500' }} transition-all duration-1000" style="width: {{ $progress }}%"></div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider">Conteo de Unidades</h3>
            <div class="mt-3 flex items-baseline gap-1">
                <span class="text-3xl font-black text-gray-800">{{ $totalReceived }}</span>
                <span class="text-lg text-gray-400 font-medium">/ {{ $totalExpected }}</span>
            </div>
            <p class="text-xs text-gray-400 mt-1">Total Confirmado vs Esperado</p>
        </div>

        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-center">
            <h3 class="text-gray-500 text-xs font-bold uppercase tracking-wider mb-2">Auditoría</h3>
            @if($hasDiscrepancies)
                <div class="flex items-center gap-3 text-red-600">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-circle-exclamation text-xl"></i>
                    </div>
                    <div>
                        <span class="font-bold block">Discrepancias</span>
                        <span class="text-xs text-red-400">Revisar faltantes/sobrantes</span>
                    </div>
                </div>
            @else
                <div class="flex items-center gap-3 text-green-600">
                    <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center">
                        <i class="fa-solid fa-check-double text-xl"></i>
                    </div>
                    <div>
                        <span class="font-bold block">Conteo Perfecto</span>
                        <span class="text-xs text-green-400">100% Coincidencia</span>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-50 bg-gray-50/50">
            <h3 class="font-bold text-gray-700">Desglose de Productos</h3>
        </div>
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50 text-gray-500 text-xs uppercase font-bold tracking-wider text-center">
                <tr>
                    <th class="px-6 py-3 text-left">Producto / SKU</th>
                    <th class="px-6 py-3">Esperado</th>
                    <th class="px-6 py-3">Recibido</th>
                    <th class="px-6 py-3">Diferencia</th>
                    <th class="px-6 py-3">Status</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200 text-sm">
                @foreach($asn->items as $item)
                    @php
                        $diff = $item->received_quantity - $item->expected_quantity;
                        // Resaltar filas con problemas si la orden ya se cerró o va avanzada
                        $rowClass = '';
                        if($diff != 0 && $asn->status == 'completed') {
                            $rowClass = $diff < 0 ? 'bg-red-50/50' : 'bg-yellow-50/50';
                        }
                    @endphp
                    <tr class="{{ $rowClass }} hover:bg-gray-50 transition">
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="font-bold text-gray-800">{{ $item->product->sku }}</span>
                                <span class="text-gray-500 text-xs">{{ Str::limit($item->product->name, 40) }}</span>
                                @if($item->product->requires_serial_number)
                                    <span class="inline-flex mt-1 w-fit items-center px-2 py-0.5 rounded text-[10px] font-bold bg-purple-100 text-purple-700 border border-purple-200">
                                        <i class="fa-solid fa-barcode mr-1"></i> SERIALIZADO
                                    </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-500 font-medium">
                            {{ $item->expected_quantity }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded-md font-bold {{ $item->received_quantity > 0 ? 'bg-slate-100 text-slate-800' : 'text-gray-400' }}">
                                {{ $item->received_quantity }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center font-bold">
                            @if($diff > 0)
                                <span class="text-yellow-600">+{{ $diff }}</span>
                            @elseif($diff < 0)
                                <span class="text-red-500">{{ $diff }}</span>
                            @else
                                <span class="text-gray-300">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($diff == 0)
                                <i class="fa-solid fa-circle-check text-green-500 text-lg" title="Correcto"></i>
                            @elseif($diff < 0)
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-red-100 text-red-700">
                                    FALTANTE
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-bold bg-yellow-100 text-yellow-700">
                                    EXCEDENTE
                                </span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-8 flex justify-end gap-4">
        <a href="{{ route('admin.receptions.index') }}" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 font-bold rounded-xl hover:bg-gray-50 transition shadow-sm">
            Volver al Listado
        </a>
        
        @if($hasDiscrepancies || $asn->notes)
            <button class="px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg shadow-red-500/30 transition flex items-center gap-2">
                <i class="fa-solid fa-file-pdf"></i> Descargar Reporte de Incidencias
            </button>
        @endif
    </div>

</div>
@endsection