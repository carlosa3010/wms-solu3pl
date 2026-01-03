@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <div>
        <h2 class="text-2xl font-black text-slate-800">Existencias en Almacén</h2>
        <p class="text-sm text-slate-500">Visualiza el stock de tus productos desglosado por ubicación.</p>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">SKU</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Producto</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Sucursal</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Bodega / Pasillo</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Disponible</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($stocks as $stock)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 font-mono text-xs font-bold text-blue-600">{{ $stock->sku->code }}</td>
                    <td class="px-6 py-4 text-sm font-bold text-slate-700">{{ $stock->sku->name }}</td>
                    <td class="px-6 py-4 text-xs text-slate-600">{{ $stock->branch->name }}</td>
                    <td class="px-6 py-4 text-xs text-slate-400 font-medium">{{ $stock->warehouse->name }}</td>
                    <td class="px-6 py-4 text-right">
                        <span class="text-sm font-black {{ $stock->quantity < 10 ? 'text-rose-600' : 'text-slate-900' }}">
                            {{ $stock->quantity }} unds.
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection