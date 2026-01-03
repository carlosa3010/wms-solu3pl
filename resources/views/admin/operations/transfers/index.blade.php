@extends('layouts.admin')

@section('title', 'Traslados Internos')
@section('header_title', 'Movimientos entre Bines')

@section('content')

    <!-- Alertas -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm animate-fade-in">
            <p class="text-sm font-bold">{{ session('success') }}</p>
        </div>
    @endif

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Historial de Traslados</h2>
            <p class="text-xs text-slate-500">Trazabilidad de cambios de ubicación física.</p>
        </div>
        
        <a href="{{ route('admin.transfers.create') }}" class="bg-custom-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg hover:brightness-110 transition flex items-center gap-2">
            <i class="fa-solid fa-arrow-right-arrow-left"></i> Nuevo Traslado
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">Fecha / Hora</th>
                        <th class="px-6 py-4">Producto</th>
                        <th class="px-6 py-4 text-center">Ruta: Origen <i class="fa-solid fa-arrow-right mx-1 opacity-30"></i> Destino</th>
                        <th class="px-6 py-4 text-center">Cantidad</th>
                        <th class="px-6 py-4 text-right">Usuario</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($transfers as $trf)
                        <tr class="hover:bg-slate-50/50 transition">
                            <td class="px-6 py-4 text-xs font-medium text-slate-500">
                                {{ $trf->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-700 block">{{ $trf->product->name }}</span>
                                <span class="text-[10px] text-slate-400 font-mono">{{ $trf->product->sku }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-3">
                                    <span class="bg-slate-100 px-2 py-1 rounded text-[10px] font-bold text-slate-600 border border-slate-200 uppercase">
                                        {{ $trf->fromLocation->code }}
                                    </span>
                                    <i class="fa-solid fa-arrow-right text-custom-primary text-[10px]"></i>
                                    <span class="bg-blue-50 px-2 py-1 rounded text-[10px] font-bold text-custom-primary border border-blue-100 uppercase">
                                        {{ $trf->toLocation->code }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="text-base font-black text-slate-800">{{ $trf->quantity }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-[10px] bg-slate-50 text-slate-500 px-2 py-1 rounded-full font-bold">
                                    {{ $trf->user->name ?? 'Sistema' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-20 text-center text-slate-400">
                                <div class="flex flex-col items-center opacity-30">
                                    <i class="fa-solid fa-shuffle text-4xl mb-4"></i>
                                    <p class="font-bold">No hay traslados registrados.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 bg-slate-50 border-t border-slate-100">
            {{ $transfers->links() }}
        </div>
    </div>
@endsection