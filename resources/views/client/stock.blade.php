@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <!-- Encabezado -->
    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl font-black text-slate-800 tracking-tight">Inventario en Tiempo Real</h2>
            <p class="text-sm text-slate-500">Consulta la disponibilidad de tus productos en nuestras bodegas.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="px-4 py-2 bg-blue-50 rounded-lg border border-blue-100 text-blue-700 text-xs font-bold">
                Total SKUs: {{ $stocks->unique('product_id')->count() }}
            </div>
            <!-- Botón actualizado para Exportar PDF en lugar de impresión de pantalla -->
            <a href="{{ route('client.stock.export') }}" target="_blank" class="flex items-center gap-2 px-4 py-2 bg-slate-800 text-white rounded-lg text-xs font-bold hover:bg-slate-900 transition-all shadow-lg shadow-slate-200" title="Descargar Resumen PDF">
                <i data-lucide="file-down" class="w-4 h-4"></i>
                <span>Exportar PDF</span>
            </a>
        </div>
    </div>

    <!-- Tabla de Stock -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Producto / SKU</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Ubicación</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Bodega / Sucursal</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Cantidad</th>
                        <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($stocks as $stock)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <p class="text-sm font-bold text-slate-800">{{ $stock->product->name ?? 'Producto no encontrado' }}</p>
                            <p class="text-xs text-slate-400 font-mono">{{ $stock->product->sku ?? 'S/S' }}</p>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 bg-slate-100 rounded text-xs font-mono font-bold text-slate-600 border border-slate-200">
                                {{ $stock->location?->code ?? 'POR ASIGNAR' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col">
                                <span class="text-xs font-bold text-slate-700">
                                    {{ $stock->location?->warehouse?->name ?? 'Sin Bodega' }}
                                </span>
                                <span class="text-[10px] text-slate-400 uppercase tracking-tighter">
                                    {{ $stock->location?->warehouse?->branch?->name ?? 'Sin Sucursal' }}
                                </span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-black text-slate-800">{{ number_format($stock->quantity) }}</span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($stock->quantity > ($stock->product->min_stock_level ?? 0))
                                <span class="px-2 py-1 bg-emerald-100 text-emerald-700 rounded-full text-[10px] font-black uppercase">En Stock</span>
                            @else
                                <span class="px-2 py-1 bg-amber-100 text-amber-700 rounded-full text-[10px] font-black uppercase">Stock Bajo</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i data-lucide="package-open" class="w-12 h-12 text-slate-200 mb-2"></i>
                                <p class="text-slate-400 text-sm font-medium">No hay existencias registradas actualmente.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection