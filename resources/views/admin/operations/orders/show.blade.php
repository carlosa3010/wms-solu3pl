@extends('layouts.admin')

@section('title', 'Detalle de Pedido')
@section('header_title', 'Orden #' . $order->order_number)

@section('content')

    <div class="max-w-6xl mx-auto">
        
        <!-- Navegación Breadcrumb -->
        <nav class="flex items-center text-sm text-slate-500 mb-6">
            <a href="{{ route('admin.orders.index') }}" class="hover:text-custom-primary transition font-medium">Pedidos</a>
            <i class="fa-solid fa-chevron-right text-[10px] mx-2"></i>
            <span class="font-bold text-slate-700">{{ $order->order_number }}</span>
        </nav>

        <!-- Alertas de Feedback -->
        @if(session('success'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm flex items-center gap-3 animate-fade-in">
                <i class="fa-solid fa-check-circle text-lg"></i>
                <p class="text-sm font-bold">{{ session('success') }}</p>
            </div>
        @endif

        @if($errors->any())
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm">
                <ul class="list-disc list-inside text-xs font-bold">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Cabecera de Estado -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-8 flex flex-col md:flex-row justify-between gap-6 relative overflow-hidden">
            
            @php
                $statusColors = [
                    'pending'   => 'bg-yellow-500',
                    'allocated' => 'bg-blue-500',
                    'picking'   => 'bg-indigo-500',
                    'packing'   => 'bg-purple-500',
                    'shipped'   => 'bg-green-500',
                    'cancelled' => 'bg-red-500'
                ];
                $currentColor = $statusColors[$order->status] ?? 'bg-slate-500';
            @endphp
            
            <div class="absolute left-0 top-0 bottom-0 w-1.5 {{ $currentColor }}"></div>

            <div class="flex gap-5 items-start">
                <div class="w-14 h-14 rounded-2xl {{ $currentColor }} bg-opacity-10 flex items-center justify-center text-2xl {{ str_replace('bg-', 'text-', $currentColor) }}">
                    <i class="fa-solid fa-cart-flatbed"></i>
                </div>
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h2 class="text-2xl font-black text-slate-800">{{ $order->order_number }}</h2>
                        <span class="{{ $currentColor }} text-white px-3 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider shadow-sm">
                            {{ $order->status_label }}
                        </span>
                    </div>
                    <p class="text-sm font-bold text-slate-600 italic">Dueño: {{ $order->client->company_name }}</p>
                    <p class="text-[11px] text-slate-400 mt-1 uppercase font-bold tracking-tighter">
                        <i class="fa-regular fa-calendar-check mr-1"></i>Registrado: {{ $order->created_at->format('d/m/Y - h:i A') }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap gap-8 items-center md:text-right">
                <div class="bg-slate-50 px-4 py-2 rounded-xl border border-slate-100">
                    <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1 text-center">Identificación Destinatario</p>
                    <p class="font-mono font-black text-custom-primary text-sm">{{ $order->customer_id_number }}</p>
                </div>
                <div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1">Carrier / Envío</p>
                    <p class="font-bold text-slate-700 uppercase flex items-center md:justify-end gap-2">
                        <i class="fa-solid fa-truck-fast text-custom-primary"></i> {{ $order->shipping_method ?? 'Estándar' }}
                    </p>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- INFORMACIÓN IZQUIERDA -->
            <div class="lg:col-span-2 space-y-6">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Card: Destinatario -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                        <div class="p-4 bg-slate-50 border-b border-slate-100 flex items-center gap-2 text-slate-700 font-bold text-xs uppercase tracking-wider">
                            <i class="fa-solid fa-user-tag text-custom-primary"></i> Destinatario y Entrega
                        </div>
                        <div class="p-5 space-y-4 text-sm">
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Nombre / Razón Social</p>
                                <p class="font-black text-slate-700 text-base leading-tight">{{ $order->customer_name }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Dirección Exacta</p>
                                <p class="text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-lg border border-slate-100 italic">"{{ $order->shipping_address }}"</p>
                                <p class="text-[11px] font-black text-slate-800 mt-2 uppercase">
                                    <i class="fa-solid fa-map-pin text-red-500 mr-1"></i>{{ $order->city }}, {{ $order->state }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Card: Sede Responsable -->
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden border-t-4 border-t-custom-primary">
                        <div class="p-4 bg-blue-50 border-b border-blue-100 flex items-center gap-2 text-custom-primary font-bold text-xs uppercase tracking-wider">
                            <i class="fa-solid fa-building-circle-check"></i> Sede Asignada
                        </div>
                        <div class="p-5 flex flex-col items-center justify-center text-center h-full min-h-[160px]">
                            @if($order->branch)
                                <div class="w-14 h-14 bg-custom-primary text-white rounded-full flex items-center justify-center mb-3 shadow-lg shadow-blue-500/20">
                                    <i class="fa-solid fa-warehouse text-xl"></i>
                                </div>
                                <h4 class="text-lg font-black text-slate-800 uppercase tracking-tighter">{{ $order->branch->name }}</h4>
                                <p class="text-[10px] text-slate-500 font-bold uppercase mt-1">Centro de Distribución</p>
                                <div class="mt-4 px-3 py-1 bg-blue-50 text-custom-primary border border-blue-100 rounded-full text-[9px] font-black uppercase tracking-widest">
                                    Zona: {{ $order->state }}
                                </div>
                            @else
                                <div class="w-12 h-12 bg-slate-100 text-slate-300 rounded-full flex items-center justify-center mb-3">
                                    <i class="fa-solid fa-circle-question"></i>
                                </div>
                                <p class="text-xs font-bold text-slate-400 uppercase">Pendiente de Asignación</p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Detalle de Productos -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                        <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Contenido de la Orden</h3>
                        <span class="text-[10px] font-black bg-slate-800 text-white px-2 py-1 rounded-lg uppercase tracking-tighter shadow-sm">Total SKUs: {{ $order->items->count() }}</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm whitespace-nowrap">
                            <thead class="bg-slate-50 text-slate-400 font-bold text-[10px] uppercase border-b border-slate-100">
                                <tr>
                                    <th class="px-6 py-4 tracking-widest">Producto / SKU</th>
                                    <th class="px-6 py-4 text-center tracking-widest">Pedido</th>
                                    <th class="px-6 py-4 text-center tracking-widest">Asignado</th>
                                    <th class="px-6 py-4 text-center tracking-widest">Pickeado</th>
                                    <th class="px-6 py-4 text-right tracking-widest">Estado Línea</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($order->items as $item)
                                    <tr class="hover:bg-slate-50 transition group">
                                        <td class="px-6 py-4">
                                            <p class="font-bold text-slate-700 group-hover:text-custom-primary transition">{{ $item->product->name }}</p>
                                            <p class="text-[10px] font-mono text-slate-400 font-bold uppercase">{{ $item->product->sku }}</p>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-lg font-black text-slate-800">{{ $item->requested_quantity }}</span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-sm font-bold {{ $item->allocated_quantity > 0 ? 'text-blue-600 bg-blue-50 border-blue-100' : 'text-slate-300 bg-slate-50 border-slate-100' }} px-2.5 py-1 rounded-lg border">
                                                {{ $item->allocated_quantity }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="text-sm font-bold {{ $item->picked_quantity > 0 ? 'text-emerald-600 bg-emerald-50 border-emerald-100' : 'text-slate-300 bg-slate-50 border-slate-100' }} px-2.5 py-1 rounded-lg border">
                                                {{ $item->picked_quantity }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            @php
                                                $progress = $item->requested_quantity > 0 ? ($item->picked_quantity / $item->requested_quantity) * 100 : 0;
                                            @endphp
                                            <div class="flex flex-col items-end gap-1.5">
                                                <span class="text-[10px] font-black {{ $progress >= 100 ? 'text-emerald-500' : 'text-slate-400' }}">
                                                    {{ $progress >= 100 ? 'COMPLETADO' : round($progress) . '%' }}
                                                </span>
                                                <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden shadow-inner">
                                                    <div class="h-full transition-all duration-700 {{ $progress >= 100 ? 'bg-emerald-500' : 'bg-custom-primary' }}" style="width: {{ $progress }}%"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- PANEL DERECHO: ACCIONES -->
            <div class="space-y-6">
                <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-xl sticky top-6 border border-white/5">
                    <h3 class="font-bold text-lg mb-6 border-b border-white/10 pb-4 flex items-center gap-2">
                        <i class="fa-solid fa-terminal text-blue-400"></i> Control Operativo
                    </h3>
                    
                    <div class="space-y-3">
                        <!-- 1. Botón Picking List (PDF) -->
                        <a href="{{ route('admin.orders.picking', $order->id) }}" target="_blank" class="w-full bg-custom-primary text-white py-3.5 rounded-xl font-bold flex items-center justify-center gap-3 hover:brightness-110 transition shadow-lg shadow-blue-500/20 active:scale-95">
                            <i class="fa-solid fa-print"></i> Lista de Picking (PDF)
                        </a>

                        <!-- 2. Botón Ejecutar Asignación (POST) -->
                        @if($order->status === 'pending')
                            <form action="{{ route('admin.orders.allocate', $order->id) }}" method="POST">
                                @csrf
                                <button type="submit" class="w-full bg-white text-slate-900 py-3.5 rounded-xl font-bold flex items-center justify-center gap-3 hover:bg-slate-100 transition shadow-lg active:scale-95 group">
                                    <i class="fa-solid fa-wand-magic-sparkles text-purple-600 group-hover:rotate-12 transition-transform"></i> Ejecutar Asignación
                                </button>
                            </form>
                        @endif

                        <div class="h-px bg-white/10 my-4"></div>

                        <!-- 3. Botón Anular Pedido (DELETE) -->
                        @if(in_array($order->status, ['pending', 'allocated']))
                            <form action="{{ route('admin.orders.destroy', $order->id) }}" method="POST" onsubmit="return confirm('¿Está seguro de anular esta orden? Se revertirá cualquier asignación de stock.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full bg-transparent text-red-400 py-3.5 rounded-xl font-bold flex items-center justify-center gap-3 border border-red-400/20 hover:bg-red-500/10 transition">
                                    <i class="fa-solid fa-ban"></i> Anular Pedido
                                </button>
                            </form>
                        @endif
                    </div>

                    <!-- Notas de Despacho -->
                    <div class="mt-8 pt-6 border-t border-white/10">
                        <p class="text-[10px] text-slate-400 uppercase tracking-widest mb-3">Instrucciones Especiales</p>
                        <div class="bg-white/5 rounded-xl p-4 text-xs italic text-slate-300 leading-relaxed border border-white/5">
                            {{ $order->notes ?? 'Sin comentarios adicionales.' }}
                        </div>
                    </div>
                </div>

                <!-- Log Visual de Trazabilidad -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wider mb-5 flex items-center gap-2">
                        <i class="fa-solid fa-history text-slate-400"></i> Historial de Orden
                    </h3>
                    <div class="space-y-6 relative before:absolute before:left-[7px] before:top-2 before:bottom-2 before:w-px before:bg-slate-100">
                        
                        <div class="flex gap-4 relative z-10">
                            <div class="w-4 h-4 rounded-full bg-emerald-500 border-4 border-white shadow-sm shrink-0 mt-0.5"></div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Orden Creada</p>
                                <p class="text-[10px] text-slate-400">{{ $order->created_at->format('d M Y - H:i') }}</p>
                            </div>
                        </div>

                        @if($order->branch)
                            <div class="flex gap-4 relative z-10">
                                <div class="w-4 h-4 rounded-full bg-blue-500 border-4 border-white shadow-sm shrink-0 mt-0.5"></div>
                                <div>
                                    <p class="text-xs font-bold text-slate-800">Asignada a Sede</p>
                                    <p class="text-[10px] text-slate-500 font-bold uppercase">{{ $order->branch->name }}</p>
                                </div>
                            </div>
                        @endif
                        
                        @if($order->status === 'cancelled')
                            <div class="flex gap-4 relative z-10">
                                <div class="w-4 h-4 rounded-full bg-red-500 border-4 border-white shadow-sm shrink-0 mt-0.5"></div>
                                <div>
                                    <p class="text-xs font-bold text-red-600 uppercase">Orden Anulada</p>
                                    <p class="text-[10px] text-slate-400">{{ $order->updated_at->format('d M Y - H:i') }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection