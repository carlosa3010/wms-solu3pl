@extends('layouts.admin')

@section('title', 'Editar Caja')
@section('header_title', 'Editar Tipo de Caja')

@section('content')
<div class="max-w-2xl mx-auto">
    <nav class="flex text-sm text-slate-500 mb-6">
        <a href="{{ route('admin.settings.packages.index') }}" class="hover:text-blue-600 font-medium">Cajas</a>
        <span class="mx-2">/</span>
        <span class="font-bold text-slate-700">Editar: {{ $package->name }}</span>
    </nav>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="p-5 bg-slate-50 border-b border-slate-100">
            <h3 class="font-bold text-slate-800">Modificar Empaque</h3>
        </div>

        <form action="{{ route('admin.settings.packages.update', $package->id) }}" method="POST" class="p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Nombre</label>
                    <input type="text" name="name" value="{{ old('name', $package->name) }}" required class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-blue-500 outline-none">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase mb-2">Asignar a Cliente</label>
                    <select name="client_id" class="w-full px-4 py-2 border border-slate-300 rounded-xl text-sm bg-white">
                        <option value="">-- Caja Global --</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}" {{ $package->client_id == $client->id ? 'selected' : '' }}>{{ $client->company_name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="border-slate-100">

            <div>
                <h4 class="text-sm font-bold text-slate-700 mb-4 flex items-center gap-2"><i class="fa-solid fa-ruler-combined text-slate-400"></i> Dimensiones (cm)</h4>
                <div class="grid grid-cols-3 gap-4">
                    <div><label class="block text-xs font-bold text-slate-500 mb-1">Largo</label><input type="number" step="0.01" name="length" value="{{ old('length', $package->length) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-xl"></div>
                    <div><label class="block text-xs font-bold text-slate-500 mb-1">Ancho</label><input type="number" step="0.01" name="width" value="{{ old('width', $package->width) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-xl"></div>
                    <div><label class="block text-xs font-bold text-slate-500 mb-1">Alto</label><input type="number" step="0.01" name="height" value="{{ old('height', $package->height) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-xl"></div>
                </div>
            </div>

            <hr class="border-slate-100">

            <div class="grid grid-cols-2 gap-6">
                <div><label class="block text-xs font-bold text-slate-500 mb-1">Peso Vacío (kg)</label><input type="number" step="0.01" name="empty_weight" value="{{ old('empty_weight', $package->empty_weight) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-xl"></div>
                <div><label class="block text-xs font-bold text-slate-500 mb-1">Peso Máx (kg)</label><input type="number" step="0.01" name="max_weight" value="{{ old('max_weight', $package->max_weight) }}" required class="w-full px-3 py-2 border border-slate-300 rounded-xl"></div>
            </div>

            <div class="pt-6 flex justify-end gap-3">
                <a href="{{ route('admin.settings.packages.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-600 font-bold text-sm hover:bg-slate-50">Cancelar</a>
                <button type="submit" class="bg-custom-primary text-white px-6 py-2.5 rounded-xl font-bold text-sm shadow-lg hover:brightness-95">Actualizar</button>
            </div>
        </form>
    </div>
</div>
@endsection