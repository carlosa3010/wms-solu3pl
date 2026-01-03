@extends('layouts.admin')

@section('title', 'Mi Perfil')
@section('header_title', 'Configuración de Cuenta')

@section('content')
<div class="max-w-5xl mx-auto">
    
    <!-- Mensajes de Retroalimentación -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-xl shadow-sm animate-fade-in flex items-center gap-3">
            <div class="w-8 h-8 bg-green-200 rounded-full flex items-center justify-center text-green-600">
                <i class="fa-solid fa-check"></i>
            </div>
            <div>
                <p class="font-bold text-sm">Cambios Guardados</p>
                <p class="text-xs">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        
        <!-- Tarjeta 1: Información de Perfil -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden transition-all hover:shadow-md">
            <div class="p-6 border-b border-slate-100 bg-slate-50 flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-custom-primary bg-opacity-10 text-custom-primary flex items-center justify-center text-xl shadow-inner">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 leading-tight">Información de Perfil</h2>
                    <p class="text-[10px] text-slate-400 uppercase font-black tracking-widest">Identidad del Usuario</p>
                </div>
            </div>
            
            <form action="{{ route('profile.update') }}" method="POST" class="p-6">
                @csrf 
                @method('PUT')
                <div class="space-y-5">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2">Nombre Completo</label>
                        <div class="relative group">
                            <span class="absolute left-4 top-3 text-slate-400 group-focus-within:text-custom-primary transition-colors">
                                <i class="fa-solid fa-user text-xs"></i>
                            </span>
                            <input type="text" name="name" value="{{ $user->name }}" required
                                   class="w-full pl-10 pr-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none transition bg-slate-50 focus:bg-white">
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2">Correo Electrónico</label>
                        <div class="relative">
                            <span class="absolute left-4 top-3 text-slate-300">
                                <i class="fa-solid fa-envelope text-xs"></i>
                            </span>
                            <input type="email" value="{{ $user->email }}" disabled
                                   class="w-full pl-10 pr-4 py-2.5 border border-slate-100 rounded-xl text-sm text-slate-400 bg-slate-50 cursor-not-allowed">
                        </div>
                        <p class="text-[9px] text-slate-400 mt-2 flex items-center gap-1 italic">
                            <i class="fa-solid fa-lock"></i> El correo electrónico solo puede ser modificado por Soporte Técnico.
                        </p>
                    </div>

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-custom-primary text-white py-3 rounded-xl font-bold text-sm shadow-lg shadow-blue-500/20 hover:brightness-110 active:scale-95 transition">
                            Actualizar Información
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tarjeta 2: Cambio de Contraseña -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden transition-all hover:shadow-md">
            <div class="p-6 border-b border-slate-100 bg-slate-50 flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl border border-amber-100 shadow-inner">
                    <i class="fa-solid fa-shield-halved"></i>
                </div>
                <div>
                    <h2 class="text-lg font-bold text-slate-800 leading-tight">Seguridad</h2>
                    <p class="text-[10px] text-slate-400 uppercase font-black tracking-widest">Protección de Cuenta</p>
                </div>
            </div>
            
            <form action="{{ route('profile.password') }}" method="POST" class="p-6">
                @csrf 
                @method('PUT')
                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2">Contraseña Actual</label>
                        <input type="password" name="current_password" required
                               class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 ring-amber-500 outline-none transition bg-slate-50 focus:bg-white @error('current_password') border-red-500 @enderror">
                        @error('current_password') 
                            <p class="text-[10px] text-red-500 font-bold mt-1 uppercase tracking-tighter">{{ $message }}</p> 
                        @enderror
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2">Nueva Contraseña</label>
                            <input type="password" name="password" required
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 ring-amber-500 outline-none transition bg-slate-50 focus:bg-white @error('password') border-red-500 @enderror">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-500 uppercase tracking-wider mb-2">Confirmar Nueva</label>
                            <input type="password" name="password_confirmation" required
                                   class="w-full px-4 py-2.5 border border-slate-200 rounded-xl text-sm focus:ring-2 ring-amber-500 outline-none transition bg-slate-50 focus:bg-white">
                        </div>
                    </div>
                    @error('password') 
                        <p class="text-[10px] text-red-500 font-bold mt-1 uppercase tracking-tighter">{{ $message }}</p> 
                    @enderror

                    <div class="pt-4">
                        <button type="submit" class="w-full bg-slate-800 text-white py-3 rounded-xl font-bold text-sm shadow-lg hover:bg-slate-700 active:scale-95 transition">
                            Actualizar Credenciales
                        </button>
                    </div>
                </div>
            </form>
        </div>

    </div>
    
    <!-- Aviso de Pie -->
    <div class="mt-8 text-center">
        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">
            Ultima modificación: {{ $user->updated_at->diffForHumans() }}
        </p>
    </div>
</div>
@endsection