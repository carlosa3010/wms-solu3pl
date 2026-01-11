@extends('layouts.admin')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-800 tracking-tight">Gestión de Usuarios</h1>
            <p class="text-sm text-slate-500 mt-1">Administra accesos, roles, sucursales y permisos.</p>
        </div>
        <button onclick="openCreateModal()" 
                class="inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-100 transition-all shadow-sm group">
            <i data-lucide="user-plus" class="w-4 h-4 mr-2 transition-transform group-hover:scale-110"></i>
            Nuevo Usuario
        </button>
    </div>

    @if(session('success'))
    <div id="successAlert" class="bg-emerald-50 text-emerald-800 border border-emerald-200 p-4 rounded-xl shadow-sm flex items-start animate-in fade-in slide-in-from-top-2">
        <div class="bg-white p-2 rounded-lg border border-emerald-100 shadow-sm mr-3">
            <i data-lucide="check-circle-2" class="w-5 h-5 text-emerald-500"></i>
        </div>
        <div class="flex-1">
            <h3 class="font-bold text-sm">Operación Exitosa</h3>
            <p class="text-sm mt-1 whitespace-pre-wrap leading-relaxed">{{ session('success') }}</p>
            @if(Str::contains(session('success'), 'Contraseña:'))
                <p class="text-xs text-emerald-600 mt-2 font-medium italic flex items-center">
                    <i data-lucide="alert-triangle" class="w-3 h-3 mr-1"></i>
                    Guarda las credenciales ahora. No se mostrarán nuevamente.
                </p>
            @endif
        </div>
        <button onclick="document.getElementById('successAlert').remove()" class="text-emerald-400 hover:text-emerald-600 transition-colors">
            <i data-lucide="x" class="w-4 h-4"></i>
        </button>
    </div>
    @endif

    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-xs uppercase text-slate-500 font-semibold tracking-wider">
                        <th class="px-6 py-3">Usuario</th>
                        <th class="px-6 py-3">Rol</th>
                        <th class="px-6 py-3">Ubicación / Cliente</th> <th class="px-6 py-3">Estado</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($users as $u)
                    <tr class="hover:bg-slate-50/80 transition-colors group">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-600 uppercase shadow-sm">
                                    {{ substr($u->name, 0, 2) }}
                                </div>
                                <div>
                                    <p class="font-medium text-slate-900 group-hover:text-blue-600 transition-colors">{{ $u->name }}</p>
                                    <p class="text-xs text-slate-500 flex items-center">
                                        <i data-lucide="mail" class="w-3 h-3 mr-1 opacity-70"></i>
                                        {{ $u->email }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4">
                            @php
                                $badges = [
                                    'admin'      => 'bg-purple-50 text-purple-700 border-purple-100 ring-purple-500/10',
                                    'manager'    => 'bg-indigo-50 text-indigo-700 border-indigo-100 ring-indigo-500/10',
                                    'supervisor' => 'bg-sky-50 text-sky-700 border-sky-100 ring-sky-500/10',
                                    'operator'   => 'bg-orange-50 text-orange-700 border-orange-100 ring-orange-500/10',
                                    'user'       => 'bg-emerald-50 text-emerald-700 border-emerald-100 ring-emerald-500/10'
                                ];
                                $roleNames = [
                                    'user' => 'Cliente Portal',
                                    'manager' => 'Gerente',
                                    'operator' => 'Operador',
                                    'admin' => 'Admin Global',
                                    'supervisor' => 'Supervisor'
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border ring-1 ring-inset {{ $badges[$u->role] ?? 'bg-slate-50 text-slate-600 border-slate-100' }}">
                                {{ $roleNames[$u->role] ?? ucfirst($u->role) }}
                            </span>
                        </td>

                        <td class="px-6 py-4">
                            @if($u->role === 'user')
                                <div class="flex items-center gap-2 text-slate-600 bg-slate-50 px-2 py-1 rounded-lg border border-slate-100 inline-block w-fit">
                                    <i data-lucide="building-2" class="w-3.5 h-3.5 text-slate-400"></i>
                                    <span class="text-xs font-medium truncate max-w-[150px]" title="{{ $u->client->company_name ?? 'Sin Asignar' }}">
                                        {{ $u->client->company_name ?? 'N/A' }}
                                    </span>
                                </div>
                            @elseif($u->branch)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100">
                                    <i data-lucide="map-pin" class="w-3 h-3"></i>
                                    {{ $u->branch->name }}
                                </span>
                            @elseif($u->role === 'admin')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium bg-slate-100 text-slate-500 border border-slate-200">
                                    <i data-lucide="globe" class="w-3 h-3"></i>
                                    Global / Todas
                                </span>
                            @else
                                <span class="text-xs text-rose-500 font-medium">⚠ Sin Asignar</span>
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            <div class="flex items-center">
                                @if($u->status === 'active')
                                    <div class="h-2 w-2 rounded-full bg-emerald-500 mr-2 animate-pulse"></div>
                                    <span class="text-xs font-medium text-slate-700">Activo</span>
                                @else
                                    <div class="h-2 w-2 rounded-full bg-rose-400 mr-2"></div>
                                    <span class="text-xs font-medium text-slate-500">Inactivo</span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2 opacity-60 group-hover:opacity-100 transition-opacity">
                                <form action="{{ route('admin.users.reset_password', $u->id) }}" method="POST" onsubmit="return confirm('¿Generar nueva contraseña? La anterior dejará de funcionar.')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="p-1.5 text-slate-500 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Resetear Contraseña">
                                        <i data-lucide="key-round" class="w-4 h-4"></i>
                                    </button>
                                </form>
                                <button onclick='openEditModal(@json($u))' class="p-1.5 text-slate-500 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Editar">
                                    <i data-lucide="pencil" class="w-4 h-4"></i>
                                </button>
                                @if(auth()->id() !== $u->id)
                                    <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('¿Eliminar usuario permanentemente?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-slate-500 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Eliminar">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                            <p class="font-medium">No se encontraron usuarios</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
        <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
            {{ $users->links() }}
        </div>
        @endif
    </div>
</div>

<div id="modalUser" class="fixed inset-0 z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm transition-opacity" onclick="closeModal()"></div>

    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg border border-slate-200">
                
                <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4 border-b border-slate-100">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-full bg-blue-50 sm:mx-0 sm:h-10 sm:w-10">
                            <i data-lucide="user-cog" class="h-5 w-5 text-blue-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:ml-4 sm:mt-0 sm:text-left">
                            <h3 class="text-base font-semibold leading-6 text-slate-900" id="modalTitle">Nuevo Usuario</h3>
                            <p class="text-sm text-slate-500">Configura el acceso y rol del usuario.</p>
                        </div>
                        <button onclick="closeModal()" class="absolute top-4 right-4 text-slate-400 hover:text-slate-500">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <form id="userForm" method="POST" class="bg-slate-50/50">
                    @csrf
                    <div id="methodField"></div>
                    
                    <div class="px-6 py-6 space-y-5">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-700 uppercase">Nombre <span class="text-rose-500">*</span></label>
                                <input type="text" name="name" id="userName" required class="block w-full rounded-lg border-slate-300 py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-700 uppercase">Email <span class="text-rose-500">*</span></label>
                                <input type="email" name="email" id="userEmail" required class="block w-full rounded-lg border-slate-300 py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-700 uppercase">Rol <span class="text-rose-500">*</span></label>
                                <select name="role" id="userRole" onchange="handleRoleChange()" class="block w-full rounded-lg border-slate-300 py-2 px-3 text-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="admin">Administrador Global</option>
                                    <option value="manager">Gerente (Manager)</option>
                                    <option value="supervisor">Supervisor</option>
                                    <option value="operator">Operador de Bodega</option>
                                    <option value="user">Cliente (Portal Externo)</option>
                                </select>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-semibold text-slate-700 uppercase">Estado</label>
                                <select name="status" id="userStatus" class="block w-full rounded-lg border-slate-300 py-2 px-3 text-sm">
                                    <option value="active">Activo</option>
                                    <option value="inactive">Inactivo</option>
                                </select>
                            </div>
                        </div>

                        <div id="branchSelector" class="hidden animate-in fade-in slide-in-from-top-1">
                            <div class="p-4 bg-indigo-50 rounded-lg border border-indigo-100">
                                <label class="block text-xs font-bold text-indigo-800 uppercase mb-2">Asignar a Sucursal <span class="text-rose-500">*</span></label>
                                <select name="branch_id" id="userBranchId" class="block w-full rounded-lg border-indigo-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm text-indigo-900 py-2 px-3">
                                    <option value="">-- Seleccionar Sucursal --</option>
                                    @foreach($branches as $b)
                                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-2 text-[10px] text-indigo-600">
                                    <i data-lucide="info" class="w-3 h-3 inline"></i> 
                                    El usuario solo podrá ver y operar inventario de esta bodega.
                                </p>
                            </div>
                        </div>

                        <div id="clientSelector" class="hidden animate-in fade-in slide-in-from-top-1">
                            <div class="p-4 bg-emerald-50 rounded-lg border border-emerald-100">
                                <label class="block text-xs font-bold text-emerald-800 uppercase mb-2">Vincular a Cliente</label>
                                <select name="client_id" id="userClientId" class="block w-full rounded-lg border-emerald-200 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 sm:text-sm text-emerald-900 py-2 px-3">
                                    <option value="">-- Seleccionar Empresa --</option>
                                    @foreach($clients as $c)
                                        <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="permissionsGrid" class="hidden animate-in fade-in slide-in-from-top-1">
                            <div class="p-4 bg-slate-100 rounded-lg border border-slate-200">
                                <label class="block text-xs font-bold text-slate-600 uppercase mb-3">Módulos Permitidos</label>
                                <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto pr-1 custom-scrollbar">
                                    @foreach($availableModules as $key => $label)
                                    <label class="flex items-center p-2 bg-white rounded border border-slate-200 cursor-pointer hover:border-blue-400">
                                        <input type="checkbox" name="modules[]" value="{{ $key }}" class="h-4 w-4 rounded border-slate-300 text-blue-600 permission-checkbox">
                                        <span class="ml-2 text-xs font-medium text-slate-700">{{ $label }}</span>
                                    </label>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div id="passwordNote" class="text-center bg-blue-50 p-3 rounded-lg border border-blue-100">
                            <p class="text-xs text-blue-700 font-medium">Se enviará la contraseña al correo.</p>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6 border-t border-slate-100">
                        <button type="submit" class="inline-flex w-full justify-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800 sm:ml-3 sm:w-auto transition-colors">
                            <span id="btnSubmitText">Crear Usuario</span>
                        </button>
                        <button type="button" onclick="closeModal()" class="mt-3 inline-flex w-full justify-center rounded-lg bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function handleRoleChange() {
        const role = document.getElementById('userRole').value;
        const clientDiv = document.getElementById('clientSelector');
        const branchDiv = document.getElementById('branchSelector'); // Nuevo Div
        const permDiv = document.getElementById('permissionsGrid');
        
        // Reset visibility
        clientDiv.classList.add('hidden');
        branchDiv.classList.add('hidden');
        permDiv.classList.add('hidden');

        // Lógica de visualización
        if (role === 'user') {
            clientDiv.classList.remove('hidden');
        } 
        else if (role === 'operator' || role === 'supervisor' || role === 'manager') {
            // Operadores, Supervisores y Gerentes pueden estar atados a una sucursal
            branchDiv.classList.remove('hidden');
            
            // Managers y Supervisores ven permisos
            if (role !== 'operator') {
                permDiv.classList.remove('hidden');
            }
        }
        // Admin es Global por defecto (oculta todo)
    }

    function openCreateModal() {
        const form = document.getElementById('userForm');
        form.action = "{{ route('admin.users.store') }}";
        form.reset();
        
        document.getElementById('methodField').innerHTML = '';
        document.getElementById('modalTitle').innerText = "Nuevo Usuario";
        document.getElementById('btnSubmitText').innerText = "Crear Usuario";
        document.getElementById('passwordNote').classList.remove('hidden');
        
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
        
        handleRoleChange();
        toggleModal(true);
    }

    function openEditModal(user) {
        const form = document.getElementById('userForm');
        form.action = `/admin/users/${user.id}`;
        
        document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('modalTitle').innerText = "Editar: " + user.name;
        document.getElementById('btnSubmitText').innerText = "Guardar Cambios";
        document.getElementById('passwordNote').classList.add('hidden');
        
        // Rellenar datos
        document.getElementById('userName').value = user.name;
        document.getElementById('userEmail').value = user.email;
        document.getElementById('userRole').value = user.role;
        document.getElementById('userStatus').value = user.status;
        document.getElementById('userClientId').value = user.client_id || '';
        document.getElementById('userBranchId').value = user.branch_id || ''; // Rellenar Sucursal
        
        // Rellenar permisos
        const userPerms = user.permissions || [];
        document.querySelectorAll('.permission-checkbox').forEach(cb => {
            cb.checked = userPerms.includes(cb.value);
        });
        
        handleRoleChange();
        toggleModal(true);
    }

    function toggleModal(show) {
        const modal = document.getElementById('modalUser');
        if(show) {
            modal.classList.remove('hidden');
            if(typeof lucide !== 'undefined') lucide.createIcons();
        } else {
            modal.classList.add('hidden');
        }
    }

    function closeModal() {
        toggleModal(false);
    }

    document.addEventListener('DOMContentLoaded', () => {
        if(typeof lucide !== 'undefined') lucide.createIcons();
    });
</script>

<style>
    .custom-scrollbar::-webkit-scrollbar { width: 4px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endsection