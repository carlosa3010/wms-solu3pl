@extends('layouts.warehouse')
@section('station_title', 'Ubicación: ' . $location->code)

@section('content')
<div class="max-w-4xl mx-auto p-4">
    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 shadow-lg mb-6 flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-mono font-bold text-white">{{ $location->code }}</h2>
            <span class="inline-block mt-2 px-3 py-1 rounded bg-slate-700 text-slate-300 text-xs font-bold uppercase">
                {{ $location->type }}
            </span>
        </div>
        <div class="text-right">
            <p class="text-slate-400 text-xs uppercase">Zona</p>
            <p class="text-white font-bold">{{ $location->warehouse->name ?? 'N/A' }}</p>
        </div>
    </div>

    <h3 class="text-slate-400 text-sm font-bold uppercase tracking-wider mb-3">Contenido del Bin</h3>
    <div class="bg-slate-800 rounded-xl overflow-hidden border border-slate-700">
        <table class="w-full text-left text-sm text-slate-300">
            <thead class="bg-slate-900 text-xs text-slate-500 uppercase">
                <tr>
                    <th class="px-4 py-3">SKU</th>
                    <th class="px-4 py-3">Producto</th>
                    <th class="px-4 py-3 text-right">Cantidad</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-700">
                @forelse($location->inventory as $inv)
                <tr>
                    <td class="px-4 py-3 font-mono text-blue-400">{{ $inv->product->sku }}</td>
                    <td class="px-4 py-3">{{ Str::limit($inv->product->name, 40) }}</td>
                    <td class="px-4 py-3 text-right font-bold text-white">{{ $inv->quantity }}</td>
                </tr>
                @empty
                <tr>
                    <td colspan="3" class="px-4 py-8 text-center text-slate-500">
                        Ubicación vacía.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    
    <div class="mt-8 text-center">
        <a href="{{ route('warehouse.index') }}" class="text-slate-400 hover:text-white underline">Volver al Dashboard</a>
    </div>
</div>
@endsection