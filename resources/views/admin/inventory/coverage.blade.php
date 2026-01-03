@extends('layouts.admin')

@section('title', 'Cobertura Geográfica')
@section('header_title', 'Configuración de Sedes')

@section('content')
    <div class="max-w-6xl mx-auto">
        <div class="mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-slate-800">Inteligencia de Asignación</h2>
                <p class="text-slate-500">Defina qué estados atiende cada sucursal y asigne permisos de exportación.</p>
            </div>
            <a href="{{ route('admin.inventory.map') }}" class="text-sm font-bold text-custom-primary hover:underline">
                <i class="fa-solid fa-warehouse mr-1"></i> Volver a Infraestructura
            </a>
        </div>

        <div class="grid grid-cols-1 gap-8">
            @foreach($branches as $branch)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <form action="{{ route('admin.branches.coverage', $branch->id) }}" method="POST">
                        @csrf @method('PUT')
                        
                        <div class="p-6 bg-slate-50 border-b border-slate-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center text-custom-primary shadow-sm border border-slate-200">
                                    <i class="fa-solid fa-building-shield text-xl"></i>
                                </div>
                                <div>
                                    <h3 class="font-bold text-slate-800 text-lg">{{ $branch->name }}</h3>
                                    <p class="text-[10px] text-slate-400 font-mono uppercase tracking-widest">{{ $branch->code }} | {{ $branch->city }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-6 bg-white px-4 py-2 rounded-xl border border-slate-200 shadow-inner">
                                <label class="flex items-center gap-3 cursor-pointer group">
                                    <div class="relative">
                                        <input type="checkbox" name="can_export" value="1" {{ $branch->can_export ? 'checked' : '' }} class="sr-only peer">
                                        <div class="w-10 h-5 bg-slate-200 rounded-full peer peer-checked:bg-emerald-500 transition-colors"></div>
                                        <div class="absolute left-1 top-1 w-3 h-3 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                                    </div>
                                    <span class="text-xs font-bold text-slate-600 group-hover:text-emerald-600 transition-colors">Habilitar Exportación</span>
                                </label>
                                <button type="submit" class="bg-custom-primary text-white px-4 py-1.5 rounded-lg text-xs font-bold shadow-md hover:brightness-110 transition">
                                    Guardar Cambios
                                </button>
                            </div>
                        </div>

                        <div class="p-6">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-4">Estados de Venezuela en Cobertura</p>
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                                @foreach($states as $state)
                                    <label class="flex items-center gap-2 p-2 rounded-lg border border-slate-100 hover:bg-blue-50 hover:border-blue-200 transition cursor-pointer">
                                        <input type="checkbox" name="covered_states[]" value="{{ $state }}" 
                                               {{ in_array($state, $branch->covered_states ?? []) ? 'checked' : '' }}
                                               class="w-4 h-4 rounded border-slate-300 text-custom-primary focus:ring-custom-primary">
                                        <span class="text-xs text-slate-600 font-medium">{{ $state }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </form>
                </div>
            @endforeach
        </div>
    </div>
@endsection