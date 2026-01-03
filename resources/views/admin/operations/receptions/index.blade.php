@extends('layouts.admin')

@section('title', 'Recepciones (ASN)')
@section('header_title', 'Gestión de Entradas')

@section('content')

    <!-- Alertas de Éxito/Error -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-3 animate-fade-in">
            <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm animate-fade-in">
            <ul class="list-disc list-inside text-sm">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Filtros y Acciones -->
    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        
        <!-- Buscador y Filtros -->
        <form action="{{ route('admin.receptions.index') }}" method="GET" class="w-full md:w-auto flex-1 max-w-4xl flex gap-3">
            <div class="relative group flex-1">
                <span class="absolute left-3 top-2.5 text-slate-400 group-focus-within:text-custom-primary transition">
                    <i class="fa-solid fa-search"></i>
                </span>
                <input type="text" name="search" value="{{ request('search') }}" 
                       class="w-full pl-10 pr-4 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none transition shadow-sm"
                       placeholder="Buscar por ASN, Cliente o Referencia...">
            </div>
            
            <div class="w-40">
                <select name="status" onchange="this.form.submit()" class="w-full px-3 py-2 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none bg-white cursor-pointer">
                    <option value="">Todos los Estados</option>
                    <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pendientes</option>
                    <option value="receiving" {{ request('status') == 'receiving' ? 'selected' : '' }}>En Proceso</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completados</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelados</option>
                </select>
            </div>
        </form>

        <!-- Botón Crear -->
        <a href="{{ route('admin.receptions.create') }}" class="bg-custom-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:brightness-95 transition flex items-center gap-2 whitespace-nowrap">
            <i class="fa-solid fa-plus"></i> Nueva ASN
        </a>
    </div>

    <!-- Tabla de ASNs -->
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">ASN #</th>
                        <th class="px-6 py-4">Cliente</th>
                        <th class="px-6 py-4">Fecha Llegada</th>
                        <th class="px-6 py-4 text-center">Progreso</th>
                        <th class="px-6 py-4 text-center">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($asns as $asn)
                        <tr class="hover:bg-slate-50 transition group">
                            <!-- ID y Referencia -->
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-700 block text-base group-hover:text-custom-primary transition">{{ $asn->asn_number }}</span>
                                <span class="text-[10px] text-slate-400 flex items-center gap-1">
                                    <i class="fa-solid fa-file-invoice"></i> {{ $asn->document_ref ?? 'Sin Ref.' }}
                                </span>
                            </td>
                            
                            <!-- Cliente -->
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-600">{{ $asn->client->company_name }}</div>
                                <div class="text-[10px] text-slate-400">{{ $asn->carrier_name ?? 'Transporte N/A' }}</div>
                            </td>
                            
                            <!-- Fecha -->
                            <td class="px-6 py-4">
                                <span class="text-xs font-mono text-slate-600 bg-slate-100 px-2 py-1 rounded border border-slate-200">
                                    <i class="fa-regular fa-calendar mr-1 text-slate-400"></i>
                                    {{ $asn->expected_arrival_date->format('d/m/Y') }}
                                </span>
                            </td>
                            
                            <!-- Progreso (Items) -->
                            <td class="px-6 py-4 text-center">
                                <div class="flex flex-col items-center">
                                    <span class="text-xs font-bold text-slate-700">
                                        {{ $asn->total_received }} / {{ $asn->total_expected }}
                                    </span>
                                    <span class="text-[9px] text-slate-400 uppercase">Unidades</span>
                                    
                                    <!-- Barra de Progreso Mini -->
                                    <div class="w-16 h-1 bg-slate-100 rounded-full mt-1 overflow-hidden">
                                        @php
                                            $percent = $asn->total_expected > 0 ? ($asn->total_received / $asn->total_expected) * 100 : 0;
                                            $barColor = $percent >= 100 ? 'bg-green-500' : 'bg-blue-500';
                                        @endphp
                                        <div class="h-full {{ $barColor }}" style="width: {{ $percent }}%"></div>
                                    </div>
                                </div>
                            </td>
                            
                            <!-- Estado -->
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusConfig = [
                                        'pending' => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'border' => 'border-yellow-200', 'icon' => 'fa-clock', 'label' => 'Pendiente'],
                                        'receiving' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'border' => 'border-blue-200', 'icon' => 'fa-dolly', 'label' => 'Recibiendo'],
                                        'completed' => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'border' => 'border-green-200', 'icon' => 'fa-check-circle', 'label' => 'Completado'],
                                        'cancelled' => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200', 'icon' => 'fa-ban', 'label' => 'Cancelado'],
                                        'draft' => ['bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'border' => 'border-gray-200', 'icon' => 'fa-pencil', 'label' => 'Borrador'],
                                    ];
                                    $s = $statusConfig[$asn->status] ?? $statusConfig['pending'];
                                @endphp
                                <span class="{{ $s['bg'] }} {{ $s['text'] }} {{ $s['border'] }} px-3 py-1 rounded-full text-[10px] font-bold border uppercase tracking-wider inline-flex items-center gap-1.5 shadow-sm">
                                    <i class="fa-solid {{ $s['icon'] }}"></i> {{ $s['label'] }}
                                </span>
                            </td>
                            
                            <!-- Acciones -->
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <!-- Ver Detalle -->
                                    <a href="{{ route('admin.receptions.show', $asn->id) }}" class="text-slate-400 hover:text-custom-primary transition p-2 hover:bg-slate-100 rounded-lg" title="Ver Planificación">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <!-- Eliminar (Solo si está pendiente/borrador) -->
                                    @if(in_array($asn->status, ['pending', 'draft']))
                                        <form action="{{ route('admin.receptions.destroy', $asn->id) }}" method="POST" onsubmit="return confirm('¿Está seguro de eliminar esta ASN {{ $asn->asn_number }}? Esta acción no se puede deshacer.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-slate-400 hover:text-red-600 transition p-2 hover:bg-red-50 rounded-lg" title="Eliminar ASN">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center opacity-50">
                                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-truck-ramp-box text-3xl text-slate-300"></i>
                                    </div>
                                    <p class="font-bold text-lg text-slate-600">No hay recepciones registradas</p>
                                    <p class="text-xs max-w-xs mx-auto">Las ASNs aparecerán aquí cuando los clientes anuncien envíos entrantes.</p>
                                </div>
                                <div class="mt-6">
                                    <a href="{{ route('admin.receptions.create') }}" class="text-custom-primary font-bold text-sm hover:underline">Crear primera ASN</a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Paginación -->
        @if($asns->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $asns->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

@endsection