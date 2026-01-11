@extends('layouts.warehouse')
@section('station_title', 'Inspección RMA #' . $rma->id)

@section('content')
<div class="max-w-4xl mx-auto p-4 space-y-6">

    <div class="bg-slate-800 rounded-xl p-5 border border-slate-700 shadow-lg">
        <div class="flex justify-between items-start mb-4">
            <div>
                <p class="text-xs text-slate-500 uppercase font-bold">Cliente</p>
                <h3 class="text-white text-lg font-bold">{{ $rma->client->company_name }}</h3>
            </div>
            <div class="text-right">
                <p class="text-xs text-slate-500 uppercase font-bold">Orden Original</p>
                <p class="text-white font-mono">{{ $rma->order->order_number ?? 'N/A' }}</p>
            </div>
        </div>
        
        <div class="bg-slate-900/50 p-3 rounded-lg border border-slate-700">
            <p class="text-xs text-slate-400 mb-1"><i class="fa-solid fa-comment-dots mr-1"></i> Motivo del Cliente:</p>
            <p class="text-sm text-white italic">"{{ $rma->reason ?? 'Sin motivo especificado' }}"</p>
        </div>
    </div>

    <div class="bg-slate-800 rounded-xl border border-slate-700 overflow-hidden">
        <div class="p-4 bg-slate-900/50 border-b border-slate-700">
            <h4 class="text-white font-bold text-sm uppercase tracking-wider">Productos a Inspeccionar</h4>
        </div>
        <div class="divide-y divide-slate-700">
            @foreach($rma->items as $item)
            <div class="p-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 bg-slate-700 rounded-lg flex items-center justify-center text-slate-300">
                        <i class="fa-solid fa-box"></i>
                    </div>
                    <div>
                        <p class="text-white font-bold text-sm">{{ $item->product->sku }}</p>
                        <p class="text-xs text-slate-400">{{ Str::limit($item->product->name, 40) }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <span class="block text-xl font-bold text-red-400">{{ $item->quantity }}</span>
                    <span class="text-[10px] text-slate-500 uppercase">A Recibir</span>
                </div>
            </div>
            @endforeach
        </div>
    </div>

    <form action="{{ route('warehouse.rma.complete', $rma->id) }}" method="POST" enctype="multipart/form-data" 
          class="bg-slate-800 rounded-xl p-6 border border-slate-700 shadow-xl"
          onsubmit="return confirm('¿Confirmas que has inspeccionado y documentado la devolución?');">
        @csrf
        
        <div class="mb-6">
            <label class="block text-white font-bold text-sm mb-2 flex items-center gap-2">
                <i class="fa-solid fa-camera text-blue-400"></i> Evidencia Fotográfica (Obligatorio)
            </label>
            
            <div class="relative border-2 border-dashed border-slate-600 rounded-xl p-8 text-center hover:border-blue-500 transition-colors bg-slate-900/30 group">
                <input type="file" name="photos[]" multiple accept="image/*" required 
                       class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10"
                       onchange="updateFileName(this)">
                
                <div class="pointer-events-none">
                    <i class="fa-solid fa-cloud-arrow-up text-4xl text-slate-500 group-hover:text-blue-400 mb-3 transition-colors"></i>
                    <p class="text-sm text-slate-300 font-medium" id="fileLabel">
                        Toca aquí para tomar o subir fotos
                    </p>
                    <p class="text-xs text-slate-500 mt-1">Máx 4MB por imagen</p>
                </div>
            </div>
        </div>

        <div class="mb-6">
            <label class="block text-xs font-bold text-slate-400 uppercase mb-2">Notas de Inspección / Estado</label>
            <textarea name="notes" rows="3" class="w-full bg-slate-900 border border-slate-600 text-white rounded-xl p-3 focus:border-blue-500 outline-none text-sm" placeholder="Ej: Caja dañada, producto abierto, falta accesorio..."></textarea>
        </div>

        <div class="flex gap-3">
            <a href="{{ route('warehouse.rma.index') }}" class="w-1/3 bg-slate-700 hover:bg-slate-600 text-slate-300 font-bold py-3 rounded-xl text-center text-sm transition flex items-center justify-center">
                Cancelar
            </a>
            <button type="submit" class="w-2/3 bg-red-600 hover:bg-red-500 text-white font-bold py-3 rounded-xl shadow-lg transition text-sm flex items-center justify-center gap-2">
                <i class="fa-solid fa-check-circle"></i> CONFIRMAR RECEPCIÓN
            </button>
        </div>
    </form>

</div>

<script>
    function updateFileName(input) {
        const label = document.getElementById('fileLabel');
        const count = input.files.length;
        if (count > 0) {
            label.innerText = `${count} foto(s) seleccionada(s)`;
            label.classList.add('text-blue-400');
        } else {
            label.innerText = 'Toca aquí para tomar o subir fotos';
            label.classList.remove('text-blue-400');
        }
    }
</script>
@endsection