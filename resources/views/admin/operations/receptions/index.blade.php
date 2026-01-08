@extends('layouts.admin')

@section('title', 'Recepciones (ASN)')
@section('header_title', 'Gestión de Entradas')

@section('content')

    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-3 animate-fade-in">
            <i class="fa-solid fa-check-circle"></i> {{ session('success') }}
        </div>
    @endif

    <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
        
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
                    <option value="in_process" {{ request('status') == 'in_process' ? 'selected' : '' }}>En Proceso</option>
                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Parciales</option>
                    <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completados</option>
                </select>
            </div>
        </form>

        <a href="{{ route('admin.receptions.create') }}" class="bg-custom-primary text-white px-5 py-2.5 rounded-xl font-bold shadow-lg shadow-blue-500/30 hover:brightness-95 transition flex items-center gap-2 whitespace-nowrap">
            <i class="fa-solid fa-plus"></i> Nueva ASN
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm whitespace-nowrap">
                <thead class="bg-slate-50 text-slate-500 font-bold text-[10px] uppercase border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4">ASN #</th>
                        <th class="px-6 py-4">Cliente</th>
                        <th class="px-6 py-4">Llegada Estimada</th>
                        <th class="px-6 py-4 text-center">Progreso</th>
                        <th class="px-6 py-4 text-center">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($asns as $asn)
                        <tr class="hover:bg-slate-50 transition group">
                            <td class="px-6 py-4">
                                <span class="font-bold text-slate-700 block text-base group-hover:text-custom-primary transition">{{ $asn->asn_number }}</span>
                                <span class="text-[10px] text-slate-400 flex items-center gap-1">
                                    <i class="fa-solid fa-file-invoice"></i> {{ $asn->document_ref ?? 'Sin Ref.' }}
                                </span>
                            </td>
                            
                            <td class="px-6 py-4">
                                <div class="font-bold text-slate-600">{{ $asn->client->company_name }}</div>
                                <div class="text-[10px] text-slate-400">
                                    <i class="fa-solid fa-truck"></i> {{ $asn->carrier_name ?? 'Particular' }}
                                </div>
                            </td>
                            
                            <td class="px-6 py-4">
                                <span class="text-xs font-mono text-slate-600 bg-slate-100 px-2 py-1 rounded border border-slate-200">
                                    <i class="fa-regular fa-calendar mr-1 text-slate-400"></i>
                                    {{ \Carbon\Carbon::parse($asn->expected_arrival_date)->format('d/m/Y') }}
                                </span>
                            </td>
                            
                            <td class="px-6 py-4 text-center">
                                @php
                                    $rec = $asn->items->sum('received_quantity');
                                    $exp = $asn->items->sum('expected_quantity');
                                    $pct = $exp > 0 ? round(($rec/$exp)*100) : 0;
                                    $barColor = $pct >= 100 ? 'bg-green-500' : ($pct > 0 ? 'bg-blue-500' : 'bg-slate-200');
                                @endphp
                                <div class="flex flex-col items-center">
                                    <span class="text-xs font-bold text-slate-700">
                                        {{ $rec }} / {{ $exp }}
                                    </span>
                                    <span class="text-[9px] text-slate-400 uppercase">Unidades</span>
                                    
                                    <div class="w-16 h-1.5 bg-slate-100 rounded-full mt-1 overflow-hidden border border-slate-200">
                                        <div class="h-full {{ $barColor }} transition-all duration-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            </td>
                            
                            <td class="px-6 py-4 text-center">
                                @php
                                    $statusMap = [
                                        'pending'    => ['bg' => 'bg-yellow-50', 'text' => 'text-yellow-700', 'icon' => 'fa-clock', 'label' => 'Pendiente'],
                                        'in_process' => ['bg' => 'bg-blue-50', 'text' => 'text-blue-700', 'icon' => 'fa-dolly', 'label' => 'En Proceso'],
                                        'partial'    => ['bg' => 'bg-orange-50', 'text' => 'text-orange-700', 'icon' => 'fa-exclamation-circle', 'label' => 'Parcial'],
                                        'completed'  => ['bg' => 'bg-green-50', 'text' => 'text-green-700', 'icon' => 'fa-check-circle', 'label' => 'Completado'],
                                        'cancelled'  => ['bg' => 'bg-red-50', 'text' => 'text-red-700', 'icon' => 'fa-ban', 'label' => 'Cancelado'],
                                        'draft'      => ['bg' => 'bg-gray-50', 'text' => 'text-gray-600', 'icon' => 'fa-pencil', 'label' => 'Borrador'],
                                    ];
                                    $s = $statusMap[$asn->status] ?? $statusMap['pending'];
                                @endphp
                                <span class="{{ $s['bg'] }} {{ $s['text'] }} px-3 py-1 rounded-full text-[10px] font-bold border border-transparent uppercase tracking-wider inline-flex items-center gap-1.5 shadow-sm">
                                    <i class="fa-solid {{ $s['icon'] }}"></i> {{ $s['label'] }}
                                </span>
                            </td>
                            
                            <td class="px-6 py-4 text-right">
                                <div class="flex justify-end gap-2">
                                    <a href="{{ route('admin.receptions.print_labels', $asn->id) }}" target="_blank" class="text-slate-400 hover:text-purple-600 transition p-2 hover:bg-purple-50 rounded-lg" title="Imprimir Etiquetas de Caja">
                                        <i class="fa-solid fa-print"></i>
                                    </a>

                                    <a href="{{ route('admin.receptions.show', $asn->id) }}" class="text-slate-400 hover:text-custom-primary transition p-2 hover:bg-blue-50 rounded-lg" title="Ver Detalle">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>

                                    @if(in_array($asn->status, ['pending', 'draft']))
                                        <form action="{{ route('admin.receptions.destroy', $asn->id) }}" method="POST" onsubmit="return confirm('¿Confirma eliminar ASN {{ $asn->asn_number }}?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-slate-400 hover:text-red-600 transition p-2 hover:bg-red-50 rounded-lg" title="Eliminar">
                                                <i class="fa-solid fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="p-12 text-center">
                                <div class="flex flex-col items-center justify-center opacity-50">
                                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                                        <i class="fa-solid fa-clipboard-check text-3xl text-slate-300"></i>
                                    </div>
                                    <p class="font-bold text-lg text-slate-600">No hay ASNs registradas</p>
                                    <p class="text-xs text-slate-400">Crea una nueva orden de entrada para comenzar.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($asns->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/30">
                {{ $asns->appends(request()->query())->links() }}
            </div>
        @endif
    </div>

@endsection