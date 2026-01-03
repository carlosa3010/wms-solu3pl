@extends('layouts.client_layout')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-black text-slate-800">Mi Catálogo</h2>
            <p class="text-sm text-slate-500">Gestiona tus productos autorizados para logística.</p>
        </div>
        <button onclick="document.getElementById('modalSku').classList.remove('hidden')" class="bg-slate-900 text-white px-5 py-2.5 rounded-xl font-bold flex items-center space-x-2 hover:bg-slate-800 transition-all">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>Nuevo SKU</span>
        </button>
    </div>

    <!-- Tabla de SKUs -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Código SKU</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Descripción</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest">Categoría</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-right">Precio</th>
                    <th class="px-6 py-4 text-[10px] font-black text-slate-400 uppercase tracking-widest text-center">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($skus as $sku)
                <tr class="hover:bg-slate-50 transition-colors">
                    <td class="px-6 py-4 font-mono text-xs font-bold text-blue-600">{{ $sku->code }}</td>
                    <td class="px-6 py-4 text-sm font-bold text-slate-700">{{ $sku->name }}</td>
                    <td class="px-6 py-4 text-xs text-slate-500">{{ $sku->category }}</td>
                    <td class="px-6 py-4 text-sm font-black text-slate-900 text-right">${{ number_format($sku->price, 2) }}</td>
                    <td class="px-6 py-4 text-center">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-100 text-emerald-700">
                            {{ $sku->status }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Crear SKU -->
<div id="modalSku" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-[60] flex items-center justify-center p-4">
    <div class="bg-white w-full max-w-md rounded-3xl p-8 shadow-2xl">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-xl font-black text-slate-800">Registrar Nuevo SKU</h3>
            <button onclick="document.getElementById('modalSku').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><i data-lucide="x"></i></button>
        </div>
        <form action="{{ route('client.catalog.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Código de Producto</label>
                <input type="text" name="code" class="w-full p-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ej: SKU-9901" required>
            </div>
            <div>
                <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Nombre Comercial</label>
                <input type="text" name="name" class="w-full p-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Nombre del producto" required>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Precio</label>
                    <input type="number" step="0.01" name="price" class="w-full p-3 border border-slate-200 rounded-xl focus:ring-2 focus:ring-blue-500 outline-none" placeholder="0.00" required>
                </div>
                <div>
                    <label class="text-[10px] font-bold text-slate-400 uppercase block mb-1">Categoría</label>
                    <select name="category" class="w-full p-3 border border-slate-200 rounded-xl bg-white outline-none">
                        <option>Electrónica</option>
                        <option>Hogar</option>
                        <option>Moda</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="w-full py-4 bg-blue-600 text-white rounded-2xl font-black shadow-lg shadow-blue-200 hover:bg-blue-700 transition-all mt-4">
                Solicitar Registro
            </button>
        </form>
    </div>
</div>
@endsection