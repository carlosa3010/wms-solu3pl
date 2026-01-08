@extends('layouts.warehouse')

@section('station_title', 'Procesar ASN')

@section('content')
<div class="max-w-3xl mx-auto flex flex-col h-full" x-data="{ isDamaged: false }">

    {{-- ========================================== --}}
    {{-- MODO 1: VALIDACIÓN DE BULTOS (CHECK-IN)    --}}
    {{-- ========================================== --}}
    @if(in_array($asn->status, ['sent', 'pending', 'draft']))
        <div class="flex-1 flex flex-col items-center justify-center p-6 text-center space-y-6">
            <div class="w-24 h-24 bg-blue-900/30 rounded-full flex items-center justify-center text-blue-400 mb-4">
                <i class="fa-solid fa-truck-ramp-box text-5xl"></i>
            </div>
            
            <h2 class="text-2xl font-bold text-white">Validación de Entrada</h2>
            <p class="text-slate-400">
                La guía indica que deben llegar <strong class="text-white">{{ $asn->total_packages }} bultos/cajas</strong>.
                <br>Por favor, cuéntalos antes de abrir.
            </p>

            <form action="{{ route('warehouse.reception.checkin', $asn->id) }}" method="POST" class="w-full max-w-sm space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Bultos Recibidos Físicamente</label>
                    <input type="number" name="packages_received" value="{{ $asn->total_packages }}" 
                        class="w-full bg-slate-800 border-2 border-slate-600 text-white text-center text-3xl font-bold rounded-xl py-4 focus:border-blue-500 outline-none">
                </div>
                
                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-500 text-white font-bold py-4 rounded-xl shadow-lg transition transform active:scale-95">
                    <i class="fa-solid fa-check-circle mr-2"></i> Confirmar y Abrir
                </button>
            </form>
        </div>

    {{-- ========================================== --}}
    {{-- MODO 2: ESCANEO DE PRODUCTOS (NORMAL)      --}}
    {{-- ========================================== --}}
    @else
        
        <div class="bg-slate-800 p-4 rounded-2xl shadow-lg border transition-colors duration-300 mb-4 sticky top-0 z-20"
             :class="isDamaged ? 'border-red-500' : 'border-blue-500/50'">
            
            <form action="{{ route('warehouse.reception.scan') }}" method="POST" autocomplete="off" id="scanForm">
                @csrf
                <input type="hidden" name="asn_id" value="{{ $asn->id }}">
                <input type="hidden" name="is_damaged" :value="isDamaged ? 1 : 0">
                
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                        <i class="fa-solid fa-barcode text-slate-400 text-xl"></i>
                    </div>
                    <input type="text" name="barcode" id="scanInput" autofocus
                        class="block w-full p-4 pl-12 text-lg font-mono text-white bg-slate-900 border-2 rounded-xl focus:ring-4 outline-none transition" 
                        :class="isDamaged ? 'border-red-500 focus:ring-red-500/30 placeholder-red-400' : 'border-blue-500 focus:ring-blue-500/30 placeholder-slate-500'"
                        placeholder="Escanear Producto...">
                    
                    <button type="submit" class="absolute inset-y-2 right-2 px-4 rounded-lg font-bold text-white transition"
                        :class="isDamaged ? 'bg-red-600 hover:bg-red-500' : 'bg-blue-600 hover:bg-blue-500'">
                        <i class="fa-solid fa-check"></i>
                    </button>
                </div>

                <div class="mt-3 flex items-center justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider" :class="isDamaged ? 'text-red-400' : 'text-slate-500'">
                        <i class="fa-solid fa-triangle-exclamation mr-1"></i> Modo Avería / Dañado
                    </span>
                    <button type="button" @click="isDamaged = !isDamaged; $nextTick(() => document.getElementById('scanInput').focus())"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none"
                        :class="isDamaged ? 'bg-red-600' : 'bg-slate-700'">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform"
                            :class="isDamaged ? 'translate-x-6' : 'translate-x-1'"></span>
                    </button>
                </div>
            </form>
        </div>

        <div class="flex items-center justify-between px-2 mb-2">
            <div>
                <h2 class="font-bold text-white">{{ $asn->client->company_name }}</h2>
                <p class="text-xs text-slate-400 font-mono">{{ $asn->asn_number }}</p>
            </div>
            <div class="text-right">
                <span class="text-2xl font-black {{ $progress >= 100 ? 'text-green-400' : 'text-blue-400' }}">
                    {{ $totalReceived }} <span class="text-sm text-slate-500">/ {{ $totalExpected }}</span>
                </span>
            </div>
        </div>
        <div class="w-full bg-slate-700 rounded-full h-2.5 mb-6">
            <div class="bg-blue-500 h-2.5 rounded-full transition-all" style="width: {{ $progress }}%"></div>
        </div>

        <div class="flex-1 overflow-y-auto space-y-3 pb-24 px-1">
            @foreach($asn->items as $item)
                @php
                    $isComplete = $item->received_quantity >= $item->expected_quantity;
                    $rowClass = $isComplete ? 'bg-slate-800/40 border-green-900/30' : 'bg-slate-800 border-slate-700';
                    $textClass = $isComplete ? 'text-green-400' : 'text-white';
                @endphp

                <div class="border rounded-xl p-3 flex items-center justify-between transition {{ $rowClass }}">
                    <div class="flex-1 min-w-0 pr-3">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="font-mono text-xs font-bold bg-slate-900 px-2 py-0.5 rounded text-slate-300 border border-slate-700">
                                {{ $item->product->sku }}
                            </span>
                            @if($item->product->requires_serial_number)
                                <span class="text-[9px] bg-purple-900/50 text-purple-400 px-1.5 py-0.5 rounded uppercase font-bold"><i class="fa-solid fa-barcode"></i> Serial</span>
                            @endif
                        </div>
                        <p class="text-sm font-medium {{ $textClass }} truncate">{{ $item->product->name }}</p>
                    </div>
                    
                    <div class="flex items-center gap-3">
                        @if($item->received_quantity > 0)
                            <form action="{{ route('warehouse.reception.undo') }}" method="POST" onsubmit="return confirm('¿Restar 1 unidad?')">
                                @csrf
                                <input type="hidden" name="asn_id" value="{{ $asn->id }}">
                                <input type="hidden" name="product_id" value="{{ $item->product->id }}">
                                <button type="submit" class="w-8 h-8 flex items-center justify-center bg-red-900/30 text-red-400 hover:bg-red-900 hover:text-white rounded-lg border border-red-900/50 transition">
                                    <i class="fa-solid fa-minus"></i>
                                </button>
                            </form>
                        @endif

                        <div class="text-right w-14">
                            <span class="text-xl font-bold {{ $textClass }}">{{ $item->received_quantity }}</span>
                            <span class="text-[10px] text-slate-500 block">/ {{ $item->expected_quantity }}</span>
                        </div>

                        <a href="{{ route('warehouse.reception.label', $item->product->id) }}" target="_blank" 
                           class="w-10 h-10 flex items-center justify-center bg-slate-700 hover:bg-slate-600 text-slate-300 hover:text-white rounded-lg border border-slate-600 transition">
                            <i class="fa-solid fa-print"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="fixed bottom-0 left-0 right-0 p-4 bg-slate-900/90 backdrop-blur border-t border-slate-700 flex justify-center z-30">
            @if($totalReceived > 0 && $progress < 100)
                <form action="{{ route('warehouse.reception.finish', $asn->id) }}" method="POST" class="w-full max-w-3xl" onsubmit="return confirm('¿Seguro que deseas finalizar con FALTANTES?')">
                    @csrf
                    <button type="submit" class="w-full py-3 bg-yellow-600/20 hover:bg-yellow-600 text-yellow-500 hover:text-white font-bold rounded-xl border border-yellow-600/50 transition flex items-center justify-center gap-2">
                        <i class="fa-solid fa-triangle-exclamation"></i> Cerrar con Faltantes
                    </button>
                </form>
            @elseif($progress >= 100)
                <a href="{{ route('warehouse.reception.index') }}" class="w-full max-w-3xl py-3 bg-green-600 hover:bg-green-500 text-white font-bold rounded-xl shadow-lg shadow-green-900/50 text-center flex items-center justify-center gap-2">
                    <i class="fa-solid fa-check-double"></i> Todo Listo - Volver
                </a>
            @endif
        </div>

    @endif

    {{-- ========================================== --}}
    {{-- MODAL SERIAL (Se activa desde el backend)  --}}
    {{-- ========================================== --}}
    @if(session('ask_serial'))
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4">
            <div class="bg-slate-800 rounded-2xl border border-purple-500 shadow-2xl w-full max-w-md p-6 relative animate-bounce-in">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 bg-purple-500/20 rounded-full flex items-center justify-center mx-auto mb-4 text-purple-400">
                        <i class="fa-solid fa-barcode text-3xl"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white">Requiere Número de Serie</h3>
                    <p class="text-sm text-slate-400 mt-1">{{ session('ask_serial')['name'] }}</p>
                    <span class="inline-block mt-2 px-2 py-1 bg-slate-900 rounded border border-slate-700 font-mono text-xs text-slate-300">
                        SKU: {{ session('ask_serial')['sku'] }}
                    </span>
                </div>

                <form action="{{ route('warehouse.reception.scan') }}" method="POST">
                    @csrf
                    <input type="hidden" name="asn_id" value="{{ $asn->id }}">
                    <input type="hidden" name="barcode" value="{{ session('ask_serial')['barcode'] }}">
                    <input type="hidden" name="is_damaged" value="0"> 

                    <div class="mb-6">
                        <label class="block text-xs font-bold text-purple-400 uppercase mb-2">Escanear Serial (S/N)</label>
                        <input type="text" name="serial_number" autofocus required
                            class="w-full bg-slate-900 border-2 border-purple-500 text-white text-lg rounded-xl p-3 focus:ring-4 focus:ring-purple-500/30 outline-none placeholder-slate-600"
                            placeholder="S/N...">
                    </div>

                    <div class="flex gap-3">
                        <a href="{{ route('warehouse.reception.show', $asn->id) }}" class="flex-1 py-3 bg-slate-700 text-white rounded-xl font-bold text-center">Cancelar</a>
                        <button type="submit" class="flex-1 py-3 bg-purple-600 hover:bg-purple-500 text-white rounded-xl font-bold shadow-lg shadow-purple-900/50">
                            Confirmar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
@endsection