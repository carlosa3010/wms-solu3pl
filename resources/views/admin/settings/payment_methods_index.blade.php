@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Métodos de Pago</h2>
            <p class="text-sm text-slate-500">Configura las opciones de pago visibles para tus clientes.</p>
        </div>
        <button onclick="openCreateModal()" class="flex items-center space-x-2 bg-blue-600 text-white px-4 py-2 rounded-lg font-bold hover:bg-blue-700 transition-colors">
            <i data-lucide="plus" class="w-5 h-5"></i>
            <span>Nuevo Método</span>
        </button>
    </div>

    <!-- Lista de Métodos -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @foreach($methods as $method)
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm flex flex-col {{ $method->is_active ? '' : 'opacity-60 grayscale' }}">
            <div class="p-6 flex-1">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500">
                            <i data-lucide="credit-card" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <h3 class="font-bold text-slate-800">{{ $method->name }}</h3>
                            <span class="text-[10px] font-mono text-slate-400 uppercase">{{ $method->slug }}</span>
                        </div>
                    </div>
                    <form action="{{ route('admin.payment_methods.toggle', $method->id) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors focus:outline-none {{ $method->is_active ? 'bg-emerald-500' : 'bg-slate-200' }}">
                            <span class="translate-x-1 inline-block h-4 w-4 transform rounded-full bg-white transition {{ $method->is_active ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                    </form>
                </div>
                
                <div class="space-y-3">
                    <div class="bg-slate-50 p-3 rounded-lg text-xs text-slate-600 font-mono">
                        {{ Str::limit($method->details, 100) }}
                    </div>
                    @if($method->instructions)
                    <p class="text-xs text-slate-500 italic">
                        <i data-lucide="info" class="w-3 h-3 inline mr-1"></i>
                        Tiene instrucciones detalladas
                    </p>
                    @endif
                </div>
            </div>
            
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-between items-center rounded-b-xl">
                <button onclick="openEditModal({{ json_encode($method) }})" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                    <i data-lucide="pencil" class="w-3 h-3"></i> Editar
                </button>
                
                <form action="{{ route('admin.payment_methods.destroy', $method->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este método permanentemente?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-bold text-rose-600 hover:text-rose-800 flex items-center gap-1">
                        <i data-lucide="trash-2" class="w-3 h-3"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Modal Crear/Editar -->
<div id="methodModal" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-lg">
            <form id="methodForm" method="POST">
                @csrf
                <input type="hidden" name="_method" id="methodField" value="POST">
                
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-4" id="modalTitle">Nuevo Método de Pago</h3>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre del Método</label>
                            <input type="text" name="name" id="nameInput" required class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none">
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Datos de Pago (Visible en select)</label>
                            <textarea name="details" id="detailsInput" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ej: Banco Mercantil - 0105..."></textarea>
                            <p class="text-[10px] text-slate-400 mt-1">Esta información aparece cuando el cliente selecciona la opción.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Instrucciones Adicionales</label>
                            <textarea name="instructions" id="instructionsInput" rows="3" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none" placeholder="Ej: Enviar captura al whatsapp..."></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                    <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-blue-600 px-3 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">Guardar</button>
                    <button type="button" onclick="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-bold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Cancelar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function openCreateModal() {
        document.getElementById('modalTitle').textContent = 'Nuevo Método de Pago';
        document.getElementById('methodForm').action = "{{ route('admin.payment_methods.store') }}";
        document.getElementById('methodField').value = "POST"; // Método POST para crear
        
        // Limpiar campos
        document.getElementById('nameInput').value = '';
        document.getElementById('detailsInput').value = '';
        document.getElementById('instructionsInput').value = '';
        
        document.getElementById('methodModal').classList.remove('hidden');
    }

    function openEditModal(method) {
        document.getElementById('modalTitle').textContent = 'Editar Método';
        // Construir la ruta de update dinámicamente
        let url = "{{ route('admin.payment_methods.update', ':id') }}";
        url = url.replace(':id', method.id);
        
        document.getElementById('methodForm').action = url;
        document.getElementById('methodField').value = "PUT"; // Simular PUT para update
        
        // Llenar campos
        document.getElementById('nameInput').value = method.name;
        document.getElementById('detailsInput').value = method.details;
        document.getElementById('instructionsInput').value = method.instructions;
        
        document.getElementById('methodModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('methodModal').classList.add('hidden');
    }
</script>
@endsection