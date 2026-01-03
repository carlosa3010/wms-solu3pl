@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Métodos de Envío</h2>
            <p class="text-sm text-slate-500">Define las opciones de transporte disponibles para los pedidos.</p>
        </div>
        <button onclick="openShippingModal()" class="flex items-center space-x-2 bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 transition-colors">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>Nuevo Método</span>
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Nombre</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Código (Slug)</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase">Descripción / Tiempo</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-center">Estado</th>
                    <th class="px-6 py-4 text-xs font-bold text-slate-500 uppercase text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($methods as $method)
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4 font-bold text-slate-800">{{ $method->name }}</td>
                    <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ $method->code }}</td>
                    <td class="px-6 py-4 text-sm text-slate-600">{{ $method->description ?? '-' }}</td>
                    <td class="px-6 py-4 text-center">
                        <form action="{{ route('admin.shipping_methods.toggle', $method->id) }}" method="POST">
                            @csrf @method('PATCH')
                            <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none {{ $method->is_active ? 'bg-emerald-500' : 'bg-slate-200' }}">
                                <span class="translate-x-1 inline-block h-4 w-4 transform rounded-full bg-white transition {{ $method->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                            </button>
                        </form>
                    </td>
                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end gap-3">
                            <button onclick="editShippingModal({{ json_encode($method) }})" class="text-blue-600 hover:text-blue-800">
                                <i data-lucide="pencil" class="w-4 h-4"></i>
                            </button>
                            <form action="{{ route('admin.shipping_methods.destroy', $method->id) }}" method="POST" onsubmit="return confirm('¿Eliminar?');">
                                @csrf @method('DELETE')
                                <button class="text-rose-600 hover:text-rose-800">
                                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div id="shippingModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeShippingModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-lg">
            <form id="shippingForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="shippingMethodField" value="POST">
                
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-4" id="shippingModalTitle">Nuevo Método</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre</label>
                            <input type="text" name="name" id="shippingName" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ej: Envío Express">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Descripción (Tiempo estimado)</label>
                            <input type="text" name="description" id="shippingDescription" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ej: 1-2 días hábiles">
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">Guardar</button>
                    <button type="button" onclick="closeShippingModal()" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-bold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openShippingModal() {
        document.getElementById('shippingModalTitle').textContent = 'Nuevo Método';
        document.getElementById('shippingForm').action = "{{ route('admin.shipping_methods.store') }}";
        document.getElementById('shippingMethodField').value = "POST";
        document.getElementById('shippingName').value = '';
        document.getElementById('shippingDescription').value = '';
        document.getElementById('shippingModal').classList.remove('hidden');
    }

    function editShippingModal(method) {
        document.getElementById('shippingModalTitle').textContent = 'Editar Método';
        let url = "{{ route('admin.shipping_methods.update', ':id') }}";
        document.getElementById('shippingForm').action = url.replace(':id', method.id);
        document.getElementById('shippingMethodField').value = "PUT";
        document.getElementById('shippingName').value = method.name;
        document.getElementById('shippingDescription').value = method.description;
        document.getElementById('shippingModal').classList.remove('hidden');
    }

    function closeShippingModal() {
        document.getElementById('shippingModal').classList.add('hidden');
    }
</script>
@endsection