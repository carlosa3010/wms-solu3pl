@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Cartera de Clientes</h1>
            <p class="text-sm text-slate-500 mt-1">Gestiona la información comercial y operativa de tus clientes</p>
        </div>
        <a href="{{ route('admin.clients.create') }}" 
           class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-100 transition-all shadow-sm group">
            <i data-lucide="plus" class="w-4 h-4 mr-2 transition-transform group-hover:scale-110"></i>
            Nuevo Cliente
        </a>
    </div>

    <!-- Alertas -->
    @if(session('success'))
    <div id="successAlert" class="bg-emerald-50 text-emerald-800 border border-emerald-200 p-4 rounded-xl shadow-sm flex items-start animate-in fade-in slide-in-from-top-2">
        <div class="bg-white p-2 rounded-lg border border-emerald-100 shadow-sm mr-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-500"></i>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-sm">Operación Exitosa</h3>
            <p class="text-sm mt-1 leading-relaxed">{{ session('success') }}</p>
        </div>
        <button onclick="document.getElementById('successAlert').remove()" class="text-emerald-400 hover:text-emerald-600 transition-colors">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
    @endif

    <!-- Tabla -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <!-- Filtros -->
        <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 flex flex-col sm:flex-row gap-4 justify-between items-center">
            <div class="relative w-full sm:w-72">
                <i data-lucide="search" class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"></i>
                <input type="text" placeholder="Buscar cliente..." class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition-all">
            </div>
            <div class="text-xs text-slate-500 font-medium">
                Total: <span class="text-slate-900 font-bold">{{ $clients->count() }}</span> registros
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-semibold tracking-wider">
                        <th class="px-6 py-3">Empresa / Contacto</th>
                        <th class="px-6 py-3">Contacto</th>
                        <th class="px-6 py-3">Facturación</th>
                        <th class="px-6 py-3">Estado</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($clients as $client)
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-50 border border-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs shadow-sm uppercase">
                                    {{ substr($client->company_name, 0, 2) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900 group-hover:text-blue-600 transition-colors">{{ $client->company_name }}</p>
                                    <p class="text-xs text-slate-500 font-medium">Tax ID: {{ $client->tax_id ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="space-y-1">
                                <p class="text-sm text-slate-700 font-medium flex items-center">
                                    <i data-lucide="user" class="w-3.5 h-3.5 mr-1.5 text-slate-400"></i>
                                    {{ $client->contact_name }}
                                </p>
                                <p class="text-xs text-slate-500 flex items-center">
                                    <i data-lucide="mail" class="w-3.5 h-3.5 mr-1.5 text-slate-400"></i>
                                    {{ $client->email }}
                                </p>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                {{ ucfirst($client->billing_type) }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <form action="{{ route('admin.clients.toggle', $client->id) }}" method="POST">
                                @csrf @method('PATCH')
                                <button type="submit" class="flex items-center group/status focus:outline-none" title="Cambiar Estado">
                                    @if($client->is_active)
                                        <div class="h-2 w-2 rounded-full bg-emerald-500 mr-2 group-hover/status:scale-125 transition-transform"></div>
                                        <span class="text-xs font-medium text-emerald-700 group-hover/status:text-emerald-800">Activo</span>
                                    @else
                                        <div class="h-2 w-2 rounded-full bg-slate-300 mr-2 group-hover/status:scale-125 transition-transform"></div>
                                        <span class="text-xs font-medium text-slate-500 group-hover/status:text-slate-600">Inactivo</span>
                                    @endif
                                </button>
                            </form>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2 opacity-60 group-hover:opacity-100 transition-opacity">
                                <a href="{{ route('admin.clients.edit', $client->id) }}" class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar Cliente">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </a>
                                <form action="{{ route('admin.clients.destroy', $client->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este cliente? Esta acción no se puede deshacer.');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Eliminar">
                                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <div class="flex flex-col items-center justify-center">
                                <div class="bg-slate-50 p-4 rounded-full mb-3">
                                    <i data-lucide="users" class="w-8 h-8 text-slate-300"></i>
                                </div>
                                <p class="font-medium">No hay clientes registrados</p>
                                <a href="{{ route('admin.clients.create') }}" class="text-sm text-blue-600 hover:underline mt-1">Registrar el primero</a>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        if(typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>
@endsection