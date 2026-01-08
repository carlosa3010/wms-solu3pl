@extends('layouts.admin')

@section('title', 'Gestión de Picking')

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Módulo de Picking</h1>
            <p class="text-sm text-slate-500">Gestión de Olas y Asignación de Stock</p>
        </div>
        
        <button form="wave-form" type="submit" 
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-lg font-bold shadow-lg flex items-center gap-2 transition">
            <i class="fa-solid fa-water"></i> Ejecutar Ola de Picking
        </button>
    </div>

    <div class="bg-white p-4 rounded-xl shadow-sm border border-slate-200 mb-6">
        <form action="{{ route('admin.picking.index') }}" method="GET" class="flex gap-4">
            <input type="text" name="search" placeholder="Buscar orden..." class="border-slate-300 rounded-lg text-sm w-full md:w-64">
            <button type="submit" class="bg-slate-100 hover:bg-slate-200 text-slate-600 px-4 py-2 rounded-lg font-bold">Filtrar</button>
        </form>
    </div>

    @if(session('success'))
        <div class="bg-green-100 text-green-700 p-4 rounded-lg mb-4 font-bold text-sm">{{ session('success') }}</div>
    @endif
    
    @if(session('error'))
        <div class="bg-red-100 text-red-700 p-4 rounded-lg mb-4 font-bold text-sm">{{ session('error') }}</div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <form id="wave-form" action="{{ route('admin.picking.wave') }}" method="POST">
            @csrf
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-slate-500 font-bold uppercase text-xs">
                    <tr>
                        <th class="p-4 w-10">
                            <input type="checkbox" onclick="toggleAll(this)" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        </th>
                        <th class="p-4">Orden</th>
                        <th class="p-4">Cliente</th>
                        <th class="p-4 text-center">Items</th>
                        <th class="p-4">Fecha</th>
                        <th class="p-4 text-center">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($pendingOrders as $order)
                    <tr class="hover:bg-slate-50">
                        <td class="p-4">
                            <input type="checkbox" name="order_ids[]" value="{{ $order->id }}" class="order-checkbox rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                        </td>
                        <td class="p-4 font-bold text-slate-700">
                            {{ $order->order_number }}
                            @if($order->status == 'backorder')
                                <span class="bg-orange-100 text-orange-600 px-2 py-0.5 rounded text-[10px] ml-2">BACKORDER</span>
                            @endif
                        </td>
                        <td class="p-4 text-slate-600">{{ $order->client->company_name }}</td>
                        <td class="p-4 text-center">
                            <span class="bg-slate-100 px-2 py-1 rounded text-xs font-bold">{{ $order->items->count() }} SKUs</span>
                        </td>
                        <td class="p-4 text-slate-500 text-xs">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td class="p-4 text-center">
                            <button type="submit" formaction="{{ route('admin.picking.allocate_single', $order->id) }}" 
                                    class="text-blue-600 hover:text-blue-800 font-bold text-xs underline decoration-dotted">
                                Asignar Ahora
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="p-8 text-center text-slate-400 italic">No hay órdenes pendientes de asignación.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </form>
        
        <div class="p-4 border-t border-slate-100">
            {{ $pendingOrders->links() }}
        </div>
    </div>
</div>

<script>
    function toggleAll(source) {
        checkboxes = document.querySelectorAll('.order-checkbox');
        for(var i=0, n=checkboxes.length;i<n;i++) {
            checkboxes[i].checked = source.checked;
        }
    }
</script>
@endsection