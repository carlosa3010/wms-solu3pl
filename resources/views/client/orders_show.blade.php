@extends('layouts.client_layout')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('client.orders.index') }}" class="w-10 h-10 bg-white border border-slate-200 rounded-xl flex items-center justify-center text-slate-400 hover:text-blue-600 hover:border-blue-200 transition-all">
                <i data-lucide="arrow-left" class="w-5 h-5"></i>
            </a>
            <div>
                <h2 class="text-2xl font-black text-slate-800 flex items-center gap-3">
                    Pedido #{{ $order->order_number }}
                    @php
                        $statusClasses = [
                            'pending' => 'bg-slate-100 text-slate-600',
                            'processing' => 'bg-blue-100 text-blue-700',
                            'shipped' => 'bg-emerald-100 text-emerald-700',
                            'completed' => 'bg-gray-800 text-white',
                            'cancelled' => 'bg-rose-100 text-rose-700',
                        ];
                        $statusLabels = [
                            'pending' => 'Pendiente',
                            'processing' => 'Procesando',
                            'shipped' => 'Enviado',
                            'completed' => 'Completado',
                            'cancelled' => 'Cancelado',
                        ];
                    @endphp
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider {{ $statusClasses[$order->status] ?? 'bg-slate-100 text-slate-500' }}">
                        {{ $statusLabels[$order->status] ?? $order->status }}
                    </span>
                </h2>
                <p class="text-sm text-slate-500">Creado el {{ $order->created_at->format('d M, Y \a \l\a\s H:i') }}</p>
            </div>
        </div>
        
        @if($order->status === 'pending')
        <div class="flex gap-3">
            <a href="{{ route('client.orders.edit', $order->id) }}" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 font-bold rounded-xl text-sm hover:bg-slate-50 hover:text-blue-600 transition-all flex items-center gap-2">
                <i data-lucide="pencil" class="w-4 h-4"></i> Editar
            </a>
        </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Columna Izquierda: Detalles e Items -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Productos -->
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50">
                    <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide">Productos a Enviar</h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @foreach($order->items as $item)
                    <div class="p-4 flex items-center justify-between hover:bg-slate-50 transition-colors">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-slate-100 border border-slate-200 rounded-lg flex items-center justify-center text-slate-400 font-bold text-xs">
                                IMG
                            </div>
                            <div>
                                <p class="text-sm font-bold text-slate-800">{{ $item->product->name }}</p>
                                <p class="text-xs text-slate-500 font-mono">SKU: {{ $item->product->sku }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <span class="block text-sm font-black text-slate-900">{{ $item->quantity }} unds.</span>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-500 uppercase">Total Piezas</span>
                    <span class="text-lg font-black text-slate-800">{{ $order->items->sum('quantity') }}</span>
                </div>
            </div>

            <!-- Notas -->
            @if($order->notes)
            <div class="bg-amber-50 rounded-2xl border border-amber-100 p-6">
                <h3 class="text-xs font-bold text-amber-600 uppercase mb-2 flex items-center gap-2">
                    <i data-lucide="sticky-note" class="w-4 h-4"></i> Notas de Despacho
                </h3>
                <p class="text-sm text-amber-800">{{ $order->notes }}</p>
            </div>
            @endif

        </div>

        <!-- Columna Derecha: Info de Envío -->
        <div class="space-y-6">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-bold text-slate-800 text-sm mb-4 flex items-center gap-2">
                    <i data-lucide="map-pin" class="w-4 h-4 text-blue-600"></i>
                    Datos de Entrega
                </h3>
                
                <div class="space-y-4">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Destinatario</p>
                        <p class="text-sm font-bold text-slate-800">{{ $order->customer_name }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Dirección</p>
                        <p class="text-sm text-slate-600">{{ $order->customer_address }}</p>
                        <p class="text-sm text-slate-600">
                            {{ $order->customer_city }}, {{ $order->customer_state }} {{ $order->customer_zip }}
                        </p>
                        <p class="text-sm text-slate-600">{{ $order->customer_country }}</p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase">Contacto</p>
                        <p class="text-sm text-slate-600 flex items-center gap-2">
                            <i data-lucide="phone" class="w-3 h-3"></i> {{ $order->customer_phone }}
                        </p>
                        <p class="text-sm text-slate-600 flex items-center gap-2">
                            <i data-lucide="mail" class="w-3 h-3"></i> {{ $order->customer_email }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                <h3 class="font-bold text-slate-800 text-sm mb-4">Referencia Interna</h3>
                <div class="flex items-center justify-between py-2 border-b border-slate-100">
                    <span class="text-xs text-slate-500">Tu Ref.</span>
                    <span class="text-sm font-mono font-bold text-slate-700">{{ $order->reference_number ?? 'N/A' }}</span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-xs text-slate-500">Método Envío</span>
                    <span class="text-sm font-bold text-slate-700 uppercase">{{ $order->shipping_method ?? 'Estándar' }}</span>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection