@extends('layouts.warehouse')
@section('station_title', 'Consulta: ' . $product->sku)

@section('content')
<div class="max-w-4xl mx-auto p-4">
    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 shadow-lg mb-6">
        <div class="flex items-start gap-5">
            @if($product->image_path)
                <img src="{{ asset('storage/' . $product->image_path) }}" class="w-24 h-24 object-cover rounded-lg border border-slate-600">
            @else
                <div class="w-24 h-24 bg-slate-700 rounded-lg flex items-center justify-center text-slate-500">
                    <i class="fa-solid fa-image text-3xl"></i>
                </div>
            @endif
            <div>
                <h2 class="text-2xl font-bold text-white mb-1">{{ $product->name }}</h2>
                <span class="text-blue-400 font-mono text-lg font-bold">{{ $product->sku }}</span>
                <p class="text-slate-400 text-sm mt-2">{{ $product->description }}</p>
            </div>
        </div>
    </div>

    <h3 class="text-slate-400 text-sm font-bold uppercase tracking-wider mb-3">Ubicaciones con Stock</h3>
    <div class="grid gap-3">
        @forelse($product->inventory as $inv)
            <div class="bg-slate-800 p-4 rounded-lg border border-slate-700 flex justify-between items-center">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-location-dot text-emerald-500"></i>
                    <span class="text-white font-mono font-bold text-lg">{{ $inv->location->code }}</span>
                </div>
                <div class="text-right">
                    <span class="block text-2xl font-bold text-white">{{ $inv->quantity }}</span>
                    <span class="text-xs text-slate-500">Unidades</span>
                </div>
            </div>
        @empty
            <div class="p-8 text-center text-slate-500 bg-slate-800 rounded-xl border border-slate-700 border-dashed">
                <i class="fa-solid fa-box-open text-3xl mb-2 opacity-50"></i>
                <p>Sin stock disponible en esta sucursal.</p>
            </div>
        @endforelse
    </div>
    
    <div class="mt-8 text-center">
        <a href="{{ route('warehouse.index') }}" class="text-slate-400 hover:text-white underline">Volver al Dashboard</a>
    </div>
</div>
@endsection