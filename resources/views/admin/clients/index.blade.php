@extends('layouts.admin')

@section('content')
<div class="p-8 space-y-8 bg-slate-50/50 min-h-screen font-sans">
    <!-- Encabezado con Sinergia de Diseño -->
    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
        <div class="space-y-1">
            <nav class="flex text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2">
                <span>Administración</span>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-blue-600">Cartera Comercial</span>
            </nav>
            <h2 class="text-3xl font-black text-slate-900 tracking-tight italic uppercase">Clientes Activos</h2>
            <p class="text-slate-500 font-medium text-sm">Control maestro de cuentas corporativas y monitoreo de accesos.</p>
        </div>
        <a href="{{ route('admin.clients.create') }}" class="flex items-center justify-center space-x-2 bg-slate-900 text-white px-6 py-3.5 rounded-2xl font-bold hover:bg-blue-600 transition-all shadow-xl shadow-slate-200 hover:shadow-blue-100 group">
            <i data-lucide="user-plus" class="w-5 h-5 transition-transform group-hover:scale-110"></i>
            <span>Nuevo Registro</span>
        </a>
    </div>

    <!-- ALERTA CRÍTICA: Aquí se muestra la Contraseña Generada -->
    @if(session('success'))
    <div class="bg-white border-l-4 border-emerald-500 p-6 rounded-2xl shadow-xl shadow-emerald-100/50 flex items-start space-x-5 animate-in fade-in slide-in-from-top-4 duration-500">
        <div class="bg-emerald-100 text-emerald-600 p-3 rounded-xl shadow-inner">
            <i data-lucide="shield-check" class="w-7 h-7"></i>
        </div>
        <div class="flex-1">
            <p class="text-[11px] font-black text-slate-400 uppercase tracking-[0.2em] mb-1">Seguridad del Sistema • Credenciales</p>
            <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                <p class="text-sm text-slate-800 font-bold leading-relaxed">
                    {{ session('success') }}
                </p>
            </div>
            <p class="text-[10px] text-slate-400 mt-2 font-medium italic">* Por seguridad, esta información solo se muestra una vez. Por favor, cópiela antes de recargar la página.</p>
        </div>
        <button onclick="this.parentElement.remove()" class="text-slate-300 hover:text-slate-500 transition-colors p-2">
            <i data-lucide="x" class="w-5 h-5"></i>
        </button>
    </div>
    @endif

    @if(session('info'))
    <div class="bg-slate-900 text-white p-4 rounded-2xl shadow-lg flex items-center space-x-3 animate-in zoom-in duration-300">
        <i data-lucide="info" class="w-5 h-5 text-blue-400"></i>
        <p class="text-xs font-bold uppercase tracking-wider">{{ session('info') }}</p>
    </div>
    @endif

    <!-- Tabla Refinada -->
    <div class="bg-white rounded-[2.5rem] border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900 border-b border-slate-800">
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Nombre de la Empresa</th>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Contacto & Email</th>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em]">Teléfono</th>
                        <th class="px-6 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-center">Estado del Portal</th>
                        <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-[0.2em] text-right">Acciones de Control</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($clients as $client)
                    <tr class="hover:bg-blue-50/30 transition-all duration-200 group">
                        <!-- Empresa -->
                        <td class="px-8 py-6">
                            <div class="space-y-1">
                                <p class="text-sm font-black text-slate-900 group-hover:text-blue-600 transition-colors uppercase tracking-tight">{{ $client->company_name }}</p>
                                <div class="flex items-center space-x-2">
                                    <span class="text-[9px] font-black bg-slate-100 text-slate-500 px-2 py-0.5 rounded tracking-widest uppercase">TAX ID: {{ $client->tax_id }}</span>
                                </div>
                            </div>
                        </td>

                        <!-- Contacto -->
                        <td class="px-6 py-6 border-l border-slate-50">
                            <div class="flex flex-col">
                                <span class="text-xs font-black text-slate-800 uppercase tracking-tight">{{ $client->contact_name }}</span>
                                <span class="text-xs text-slate-400 font-bold mt-0.5 flex items-center">
                                    <i data-lucide="mail" class="w-3 h-3 mr-2 opacity-50"></i>
                                    {{ $client->email }}
                                </span>
                            </div>
                        </td>

                        <!-- Teléfono -->
                        <td class="px-6 py-6 border-l border-slate-50 font-mono text-xs font-bold text-slate-600 tracking-tighter uppercase">
                            <div class="flex items-center">
                                <i data-lucide="phone" class="w-3 h-3 mr-2 text-slate-300"></i>
                                {{ $client->phone ?? 'Sin Registro' }}
                            </div>
                        </td>

                        <!-- Estado -->
                        <td class="px-6 py-6 text-center border-l border-slate-50">
                            <div class="flex justify-center">
                                @if($client->is_active)
                                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-emerald-50 text-emerald-600 border border-emerald-100 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-2 animate-pulse"></span>
                                        Habilitado
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-4 py-1.5 rounded-full text-[10px] font-black uppercase tracking-widest bg-rose-50 text-rose-500 border border-rose-100 shadow-sm">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-2"></span>
                                        Suspendido
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- Acciones -->
                        <td class="px-8 py-6 text-right border-l border-slate-50">
                            <div class="flex items-center justify-end space-x-2 opacity-40 group-hover:opacity-100 transition-opacity">
                                <!-- Reset Password -->
                                <form action="{{ route('admin.clients.reset_password', $client->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" onclick="return confirm('¿Restablecer el acceso para este cliente?')" 
                                            class="p-2.5 bg-slate-900 text-white rounded-xl hover:bg-blue-600 transition-all shadow-sm" title="Resetear Clave">
                                        <i data-lucide="key-round" class="w-4 h-4"></i>
                                    </button>
                                </form>

                                <!-- Toggle Status -->
                                <form action="{{ route('admin.clients.toggle', $client->id) }}" method="POST">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="p-2.5 rounded-xl transition-all border border-slate-100 {{ $client->is_active ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }}" title="Suspender/Activar">
                                        <i data-lucide="{{ $client->is_active ? 'lock' : 'unlock' }}" class="w-4 h-4"></i>
                                    </button>
                                </form>

                                <!-- Editar -->
                                <a href="{{ route('admin.clients.edit', $client->id) }}" class="p-2.5 bg-white text-slate-400 border border-slate-200 hover:text-blue-600 hover:border-blue-200 rounded-xl transition-all" title="Editar">
                                    <i data-lucide="settings-2" class="w-4 h-4"></i>
                                </a>

                                <!-- Eliminar -->
                                <form action="{{ route('admin.clients.destroy', $client->id) }}" method="POST">
                                    @csrf @method('DELETE')
                                    <button type="submit" onclick="return confirm('¿Eliminar permanentemente?')" 
                                            class="p-2.5 bg-rose-50 text-rose-300 border border-rose-100 hover:text-rose-600 rounded-xl transition-all" title="Eliminar">
                                        <i data-lucide="trash" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-8 py-24 text-center">
                            <div class="flex flex-col items-center justify-center space-y-4 max-w-sm mx-auto">
                                <div class="bg-slate-50 p-6 rounded-full border border-slate-100 shadow-inner">
                                    <i data-lucide="database-zap" class="w-12 h-12 text-slate-300"></i>
                                </div>
                                <p class="font-black uppercase tracking-widest text-xs text-slate-400">Sin registros operativos</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($clients->hasPages())
        <div class="px-8 py-6 bg-slate-50/50 border-t border-slate-100">
            {{ $clients->links() }}
        </div>
        @endif
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    });
</script>
@endsection