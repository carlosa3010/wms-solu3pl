@extends('layouts.admin')

@section('title', 'Detalle de Recepción')
@section('header_title', 'ASN ' . $asn->asn_number)

@section('content')

    <div class="max-w-6xl mx-auto">
        
        <nav class="flex items-center text-sm text-slate-500 mb-6">
            <a href="{{ route('admin.receptions.index') }}" class="hover:text-custom-primary transition font-medium">Recepciones</a>
            <i class="fa-solid fa-chevron-right text-[10px] mx-2"></i>
            <span class="font-bold text-slate-700">{{ $asn->asn_number }}</span>
        </nav>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8 flex flex-col md:flex-row justify-between gap-6 relative overflow-hidden">
            
            @php
                $statusColors = [
                    'pending'   => 'bg-yellow-500',
                    'in_process'=> 'bg-blue-500',
                    'completed' => 'bg-emerald-500',
                    'cancelled' => 'bg-red-500'
                ];
                $currentColor = $statusColors[$asn->status] ?? 'bg-slate-500';
            @endphp
            
            <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $currentColor }}"></div>

            <div class="flex gap-5 items-start">
                <div class="w-14 h-14 rounded-2xl {{ $currentColor }} bg-opacity-10 flex items-center justify-center text-2xl {{ str_replace('bg-', 'text-', $currentColor) }}">
                    <i class="fa-solid fa-dolly"></i>
                </div>
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h2 class="text-2xl font-black text-slate-800">{{ $asn->asn_number }}</h2>
                        <span class="{{ $currentColor }} text-white px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm">
                            {{ ucfirst($asn->status) }}
                        </span>
                    </div>
                    <p class="text-sm font-bold text-slate-600 italic">Cliente: {{ $asn->client->company_name }}</p>
                </div>
            </div>

            <div class="flex gap-4">
                @if($asn->status === 'pending')
                    <a href="{{ route('admin.receptions.print_labels', $asn->id) }}" target="_blank" class="bg-indigo-50 text-indigo-600 px-4 py-2 rounded-xl font-bold hover:bg-indigo-100 transition flex items-center gap-2">
                        <i class="fa-solid fa-print"></i> Etiquetas
                    </a>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center gap-2 text-slate-700 font-bold text-xs uppercase tracking-wider">
                        <i class="fa-solid fa-circle-info text-custom-primary"></i> Datos Generales
                    </div>
                    <div class="p-5 space-y-4 text-sm">
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Fecha Estimada</p>
                            <p class="font-bold text-slate-700">{{ \Carbon\Carbon::parse($asn->expected_arrival_date)->format('d M, Y') }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Transportista / Tracking</p>
                            <p class="font-bold text-slate-700">{{ $asn->carrier_name ?? 'N/A' }}</p>
                            <p class="text-xs text-slate-500 font-mono">{{ $asn->tracking_number ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Ref. Documento</p>
                            <p class="font-bold text-slate-700">{{ $asn->document_ref ?? 'N/A' }}</p>
                        </div>
                        <div class="bg-blue-50 p-3 rounded-lg border border-blue-100">
                            <p class="text-[10px] font-bold text-blue-400 uppercase tracking-widest">Total Bultos Declarados</p>
                            <p class="font-black text-blue-700 text-lg">{{ $asn->total_packages }} <span class="text-xs font-normal text-blue-500">Cajas</span></p>
                        </div>
                        @if($asn->notes)
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Notas</p>
                                <p class="text-slate-600 italic bg-slate-50 p-2 rounded border border-slate-100 text-xs">{{ $asn->notes }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="lg:col-span-2">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Productos Esperados</h3>
                        <span class="text-[10px] font-black bg-slate-800 text-white px-2 py-1 rounded-lg uppercase tracking-tighter shadow-sm">Total SKUs: {{ $asn->items->count() }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-400 font-bold text-[10px] uppercase border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 tracking-widest">Producto</th>
                                    <th class="px-6 py-4 text-center tracking-widest">Cant. Esperada</th>
                                    <th class="px-6 py-4 text-center tracking-widest">Recibido</th>
                                    <th class="px-6 py-4 text-left tracking-widest">Ubicación Planificada</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($asn->items as $item)
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-slate-700">{{ $item->product->name }}</p>
                                            <p class="text-[10px] font-mono text-slate-400 font-bold uppercase">{{ $item->product->sku }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="font-bold text-slate-700">{{ $item->expected_quantity }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="font-bold {{ $item->received_quantity >= $item->expected_quantity ? 'text-emerald-600' : 'text-slate-400' }}">
                                                {{ $item->received_quantity }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4">
                                            @if($item->allocations->count() > 0)
                                                <div class="space-y-1">
                                                    @foreach($item->allocations as $alloc)
                                                        <div class="flex items-center gap-2 text-xs">
                                                            <span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded font-mono font-bold">{{ $alloc->location->code }}</span>
                                                            <span class="text-slate-400">x {{ $alloc->quantity }} unids</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-xs text-orange-400 font-bold italic flex items-center gap-1">
                                                    <i class="fa-solid fa-triangle-exclamation"></i> Sin Asignar
                                                </span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection