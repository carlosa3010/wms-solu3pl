@extends('layouts.admin')

@section('title', 'Gestión de RMA')
@section('header_title', 'Revisión RMA: ' . $rma->rma_number)

@section('content')

<div class="max-w-7xl mx-auto">

    <nav class="flex items-center text-sm text-slate-500 mb-6">
        <a href="{{ route('admin.rma.index') }}" class="hover:text-blue-600 font-medium">RMA</a>
        <i class="fa-solid fa-chevron-right text-[10px] mx-2"></i>
        <span class="font-bold text-slate-700">{{ $rma->rma_number }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2 space-y-6">
            
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-4 bg-slate-50 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700 uppercase text-xs tracking-wider">
                        <i class="fa-solid fa-box-open mr-2"></i> Ítems Inspeccionados
                    </h3>
                    <span class="text-xs font-bold bg-blue-100 text-blue-700 px-2 py-1 rounded">
                        {{ $rma->items->count() }} Productos
                    </span>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach($rma->items as $item)
                    <div class="p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h4 class="font-bold text-slate-800 text-lg">{{ $item->product->name }}</h4>
                                <p class="text-xs font-mono text-slate-500">{{ $item->product->sku }}</p>
                            </div>
                            <div class="text-right">
                                <span class="block font-bold text-slate-700 text-lg">{{ $item->qty }} Unids.</span>
                                <span class="inline-block px-2 py-1 rounded text-[10px] font-bold uppercase 
                                    {{ $item->condition == 'damaged' ? 'bg-red-100 text-red-600' : 'bg-green-100 text-green-600' }}">
                                    {{ $item->condition ?? 'No especificado' }}
                                </span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Evidencia Fotográfica (Recepción)</p>
                            
                            @if($item->reception_photos && count($item->reception_photos) > 0)
                                <div class="flex gap-2 overflow-x-auto pb-2">
                                    @foreach($item->reception_photos as $photo)
                                        <a href="{{ asset($photo) }}" target="_blank" class="block w-24 h-24 flex-shrink-0 rounded-lg overflow-hidden border border-slate-200 hover:opacity-75 transition">
                                            <img src="{{ asset($photo) }}" class="w-full h-full object-cover" alt="Evidencia">
                                        </a>
                                    @endforeach
                                </div>
                            @else
                                <div class="bg-slate-50 border border-dashed border-slate-300 rounded-lg p-4 text-center text-slate-400 text-xs italic">
                                    <i class="fa-solid fa-camera-slash mb-1 block text-lg"></i>
                                    No se adjuntaron fotos en la recepción.
                                </div>
                            @endif
                        </div>

                        <div class="bg-yellow-50 p-3 rounded-lg border border-yellow-100 text-sm text-slate-700">
                            <span class="font-bold text-yellow-700 text-xs uppercase"><i class="fa-solid fa-clipboard-check"></i> Reporte de Bodega:</span>
                            <p class="mt-1">{{ $item->inspection_notes ?? 'Sin observaciones adicionales por parte del operador.' }}</p>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="space-y-6">

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Estado Actual</p>
                
                @php
                    $colors = [
                        'pending' => 'text-yellow-600 bg-yellow-100',
                        'processing' => 'text-blue-600 bg-blue-100',
                        'approved' => 'text-emerald-600 bg-emerald-100',
                        'rejected' => 'text-red-600 bg-red-100',
                        'waiting_client' => 'text-purple-600 bg-purple-100',
                    ];
                    $statusClass = $colors[$rma->status] ?? 'text-slate-600 bg-slate-100';
                @endphp

                <div class="flex items-center gap-3 mb-4">
                    <span class="px-3 py-1 rounded-full text-sm font-black uppercase tracking-wide {{ $statusClass }}">
                        {{ ucfirst(str_replace('_', ' ', $rma->status)) }}
                    </span>
                </div>

                <div class="border-t border-slate-100 pt-4 mt-4">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Cliente</p>
                    <p class="font-bold text-slate-800 text-lg">{{ $rma->client->company_name }}</p>
                    <p class="text-xs text-slate-500">{{ $rma->client->email }}</p>
                </div>
                
                <div class="border-t border-slate-100 pt-4 mt-4 grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Carrier</p>
                        <p class="font-bold text-slate-700 text-sm">{{ $rma->carrier_name ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Tracking</p>
                        <p class="font-mono text-slate-700 text-sm">{{ $rma->tracking_number ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            @if(!in_array($rma->status, ['approved', 'rejected']))
            <div class="bg-white rounded-2xl shadow-lg border border-indigo-100 p-6 relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-indigo-500 to-purple-500"></div>
                
                <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-gavel text-indigo-500"></i> Decisión Administrativa
                </h3>

                <form action="{{ route('admin.rma.update_status', $rma->id) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label class="text-xs font-bold text-slate-500 mb-1 block">Notas de Resolución (Internas)</label>
                        <textarea name="admin_notes" rows="2" class="w-full border-slate-300 rounded-lg text-sm focus:ring-indigo-500" placeholder="Ej: Mercancía aceptada, enviar a refurbish..."></textarea>
                    </div>

                    <div class="grid grid-cols-1 gap-2">
                        <button type="submit" name="status" value="approved" class="w-full bg-emerald-500 hover:bg-emerald-600 text-white font-bold py-2 px-4 rounded-lg shadow-sm text-sm flex justify-center items-center gap-2 transition">
                            <i class="fa-solid fa-check"></i> Aprobar RMA
                        </button>

                        <div class="grid grid-cols-2 gap-2">
                            <button type="submit" name="status" value="rejected" class="bg-red-50 hover:bg-red-100 text-red-600 font-bold py-2 px-4 rounded-lg border border-red-200 text-xs flex justify-center items-center gap-2 transition">
                                <i class="fa-solid fa-xmark"></i> Rechazar
                            </button>
                            
                            <button type="submit" name="status" value="waiting_client" class="bg-purple-50 hover:bg-purple-100 text-purple-600 font-bold py-2 px-4 rounded-lg border border-purple-200 text-xs flex justify-center items-center gap-2 transition">
                                <i class="fa-regular fa-comments"></i> Decisión Cliente
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            @endif

        </div>
    </div>
</div>
@endsection