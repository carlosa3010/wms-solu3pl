@extends('layouts.admin')

@section('title', 'Tipos de Contenedores')
@section('header_title', 'Configuración de Bines')

@section('content')
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    
    <!-- Formulario de Creación -->
    <div class="lg:col-span-1">
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200 sticky top-6">
            <h3 class="font-bold text-slate-700 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-plus-circle text-custom-primary"></i> Nuevo Tipo
            </h3>
            <form action="{{ route('admin.bintypes.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Nombre *</label>
                    <input type="text" name="name" required class="w-full border rounded-lg px-3 py-2 text-sm focus:ring-2 ring-blue-500 outline-none" placeholder="Ej: Pallet Americano">
                </div>
                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Código *</label>
                    <input type="text" name="code" required class="w-full border rounded-lg px-3 py-2 text-sm font-mono uppercase focus:ring-2 ring-blue-500 outline-none" placeholder="PLT-US">
                </div>
                
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400">Largo (cm)</label>
                        <input type="number" name="length" step="0.1" required class="w-full border rounded p-2 text-sm text-center">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400">Ancho (cm)</label>
                        <input type="number" name="width" step="0.1" required class="w-full border rounded p-2 text-sm text-center">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400">Alto (cm)</label>
                        <input type="number" name="height" step="0.1" required class="w-full border rounded p-2 text-sm text-center">
                    </div>
                </div>

                <div>
                    <label class="text-xs font-bold text-slate-500 uppercase">Capacidad Max (Kg)</label>
                    <div class="relative">
                        <input type="number" name="max_weight" step="0.1" required class="w-full border rounded-lg px-3 py-2 text-sm pl-8">
                        <span class="absolute left-3 top-2 text-slate-400 text-xs"><i class="fa-solid fa-weight-hanging"></i></span>
                    </div>
                </div>

                <button type="submit" class="w-full bg-custom-primary text-white py-2.5 rounded-lg font-bold text-sm shadow-lg hover:brightness-90 transition">Guardar Tipo</button>
            </form>
        </div>
    </div>

    <!-- Lista de Tipos -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-4 bg-slate-50 border-b border-slate-200">
                <h3 class="font-bold text-slate-700">Catálogo de Contenedores</h3>
            </div>
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                    <tr>
                        <th class="p-4">Nombre / Código</th>
                        <th class="p-4 text-center">Dimensiones (cm)</th>
                        <th class="p-4 text-center">Capacidad</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($binTypes as $bin)
                    <tr class="hover:bg-blue-50 transition">
                        <td class="p-4">
                            <p class="font-bold text-slate-800">{{ $bin->name }}</p>
                            <span class="text-xs font-mono text-slate-400 bg-slate-100 px-1.5 rounded">{{ $bin->code }}</span>
                        </td>
                        <td class="p-4 text-center">
                            <div class="flex items-center justify-center gap-2 text-slate-600">
                                <span class="bg-slate-100 px-2 py-1 rounded text-xs"><i class="fa-solid fa-arrows-left-right mr-1"></i>{{ $bin->width }}</span>
                                <span class="text-slate-300">x</span>
                                <span class="bg-slate-100 px-2 py-1 rounded text-xs"><i class="fa-solid fa-arrows-up-down mr-1"></i>{{ $bin->length }}</span>
                                <span class="text-slate-300">x</span>
                                <span class="bg-slate-100 px-2 py-1 rounded text-xs"><i class="fa-solid fa-layer-group mr-1"></i>{{ $bin->height }}</span>
                            </div>
                        </td>
                        <td class="p-4 text-center">
                            <span class="font-bold text-blue-600">{{ $bin->max_weight }} Kg</span>
                        </td>
                        <td class="p-4 text-right">
                            <form action="{{ route('admin.bintypes.destroy', $bin->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este tipo?');">
                                @csrf @method('DELETE')
                                <button class="text-slate-400 hover:text-red-500 transition"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection