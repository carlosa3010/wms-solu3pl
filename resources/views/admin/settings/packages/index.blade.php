@extends('layouts.admin')

@section('title', 'Configuración de Empaquetado')
@section('header_title', 'Cajas y Empaques')

@section('content')
    <div class="flex justify-between items-center mb-6">
        <div>
            <h2 class="text-xl font-bold text-slate-800">Tipos de Caja</h2>
            <p class="text-xs text-slate-500">Defina las dimensiones para el algoritmo de Cartonización.</p>
        </div>
        <a href="{{ route('admin.settings.packages.create') }}" class="bg-custom-primary text-white px-4 py-2 rounded-lg font-bold text-sm shadow hover:brightness-95 transition">
            <i class="fa-solid fa-plus"></i> Nueva Caja
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4 text-sm font-bold flex items-center gap-2">
            <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                <tr>
                    <th class="px-6 py-4">Nombre / Referencia</th>
                    <th class="px-6 py-4">Propietario</th>
                    <th class="px-6 py-4">Dimensiones (L x W x H)</th>
                    <th class="px-6 py-4 text-center">Peso Vacío</th>
                    <th class="px-6 py-4 text-center">Peso Máx</th>
                    <th class="px-6 py-4 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($packages as $pkg)
                <tr class="hover:bg-slate-50 transition">
                    <td class="px-6 py-4 font-bold text-slate-700">
                        <i class="fa-solid fa-box text-custom-primary mr-2"></i> {{ $pkg->name }}
                    </td>
                    <td class="px-6 py-4">
                        @if($pkg->client)
                            <span class="bg-indigo-100 text-indigo-700 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide">{{ $pkg->client->company_name }}</span>
                        @else
                            <span class="bg-slate-100 text-slate-600 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wide">GLOBAL (3PL)</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 font-mono text-xs text-slate-600">
                        {{ $pkg->length }} x {{ $pkg->width }} x {{ $pkg->height }} cm
                        <br>
                        <span class="text-slate-400 text-[10px]">Vol: {{ number_format($pkg->volume / 1000, 2) }} L</span>
                    </td>
                    <td class="px-6 py-4 text-center">{{ $pkg->empty_weight }} kg</td>
                    <td class="px-6 py-4 text-center">{{ $pkg->max_weight }} kg</td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex justify-end gap-2">
                            <a href="{{ route('admin.settings.packages.edit', $pkg->id) }}" class="text-slate-400 hover:text-blue-600 transition p-2 rounded-full hover:bg-blue-50" title="Editar">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>

                            <form action="{{ route('admin.settings.packages.destroy', $pkg->id) }}" method="POST" onsubmit="return confirm('¿Borrar?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-red-600 transition p-2 rounded-full hover:bg-red-50" title="Eliminar">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" class="p-12 text-center text-slate-400">
                        <i class="fa-solid fa-box-open text-4xl mb-3 block opacity-30"></i>
                        No hay tipos de caja configurados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-4 border-t border-slate-100">
            {{ $packages->links() }}
        </div>
    </div>
@endsection