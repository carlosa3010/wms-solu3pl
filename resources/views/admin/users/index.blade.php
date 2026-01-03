@extends('layouts.admin')

@section('content')
<div class="p-6 space-y-6 bg-slate-50/50 min-h-screen font-sans text-slate-900">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="space-y-0.5">
            <nav class="flex text-[9px] font-black text-slate-400 uppercase tracking-widest mb-1">
                <span>Configuración Operativa</span>
                <span class="mx-2 text-slate-300">/</span>
                <span class="text-blue-600">Usuarios & Roles</span>
            </nav>
            <h2 class="text-2xl font-black text-slate-900 tracking-tight italic uppercase">Control de Accesos</h2>
        </div>
        <button onclick="openCreateModal()" class="flex items-center justify-center space-x-2 bg-slate-900 text-white px-6 py-2.5 rounded-xl font-bold text-xs uppercase tracking-widest hover:bg-blue-600 transition-all shadow-lg group">
            <i data-lucide="user-plus" class="w-4 h-4 transition-transform group-hover:scale-110"></i>
            <span>Nuevo Registro</span>
        </button>
    </div>

    <!-- ALERTA DE ÉXITO Y CREDENCIALES -->
    @if(session('success'))
    <div id="successAlert" class="bg-white border-l-4 border-emerald-500 p-4 rounded-xl shadow-xl shadow-emerald-100/50 flex items-start space-x-4 animate-in fade-in slide-in-from-top-4 duration-500">
        <div class="bg-emerald-100 text-emerald-600 p-2.5 rounded-lg shadow-inner">
            <i data-lucide="shield-check" class="w-5 h-5"></i>
        </div>
        <div class="flex-1">
            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Sistema de Seguridad • Notificación</p>
            <div class="bg-slate-50 p-2.5 rounded-lg border border-slate-100">
                <p class="text-xs text-slate-800 font-bold leading-relaxed whitespace-pre-wrap">{{ session('success') }}</p>
            </div>
            <p class="text-[9px] text-slate-400 mt-1.5 font-medium italic">* Si se muestra una contraseña, guárdela ahora. No se podrá ver después.</p>
        </div>
        <button onclick="document.getElementById('successAlert').remove()" class="text-slate-300 hover:text-slate-500 p-1.5"><i data-lucide="x" class="w-4 h-4"></i></button>
    </div>
    @endif

    <!-- Tabla -->
    <div class="bg-white rounded-3xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-900 border-b border-slate-800">
                        <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Identidad</th>
                        <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Rol</th>
                        <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Permisos / Cliente</th>
                        <th class="px-4 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest">Estado</th>
                        <th class="px-6 py-4 text-[9px] font-black text-slate-400 uppercase tracking-widest text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($users as $u)
                    <tr class="hover:bg-blue-50/20 transition-all group">
                        <!-- User Info -->
                        <td class="px-6 py-4">
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-400 font-black text-[10px] uppercase shadow-inner">
                                    {{ substr($u->name, 0, 2) }}
                                </div>
                                <div>
                                    <p class="text-xs font-black text-slate-900 group-hover:text-blue-600 transition-colors uppercase">{{ $u->name }}</p>
                                    <p class="text-[10px] text-slate-400 font-bold lowercase flex items-center">
                                        <i data-lucide="mail" class="w-3 h-3 mr-1 opacity-50"></i>{{ $u->email }}
                                    </p>
                                </div>
                            </div>
                        </td>

                        <!-- Role Badge -->
                        <td class="px-4 py-4">
                            @php
                                $roleBadge = [
                                    'admin'      => 'bg-rose-50 text-rose-600 border-rose-100',
                                    'manager'    => 'bg-purple-50 text-purple-600 border-purple-100',
                                    'supervisor' => 'bg-amber-50 text-amber-600 border-amber-100',
                                    'operator'   => 'bg-blue-50 text-blue-600 border-blue-100',
                                    'user'       => 'bg-emerald-50 text-emerald-600 border-emerald-100'
                                ];
                                $currentBadge = $roleBadge[$u->role] ?? 'bg-slate-50 text-slate-500 border-slate-100';
                                
                                $roleLabel = match($u->role) {
                                    'user' => 'Cliente',
                                    'manager' => 'Gerente',
                                    'supervisor' => 'Supervisor',
                                    'operator' => 'Operador',
                                    'admin' => 'Administrador',
                                    default => ucfirst($u->role)
                                };
                            @endphp
                            <span class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-tighter border {{ $currentBadge }}">
                                {{ $roleLabel }}
                            </span>
                        </td>

                        <!-- Permissions -->
                        <td class="px-4 py-4">
                            @if($u->role === 'user')
                                <div class="flex items-center space-x-1.5">
                                    <i data-lucide="building" class="w-3 h-3 text-slate-300"></i>
                                    <span class="text-[10px] font-black text-slate-500 uppercase italic">{{ $u->client->company_name ?? 'N/A' }}</span>
                                </div>
                            @elseif($u->role === 'manager' || $u->role === 'supervisor')
                                <div class="flex flex-wrap gap-1 max-w-[180px]">
                                    @forelse($u->permissions ?? [] as $perm)
                                        <span class="text-[8px] bg-slate-100 text-slate-500 px-1.5 py-0.5 rounded font-bold uppercase border border-slate-200">{{ $perm }}</span>
                                    @empty
                                        <span class="text-[9px] text-slate-300 italic">Sin módulos</span>
                                    @endforelse
                                </div>
                            @else
                                <span class="text-[9px] text-slate-300 font-black uppercase tracking-widest italic opacity-50">Acceso Total</span>
                            @endif
                        </td>

                        <!-- Status -->
                        <td class="px-4 py-4">
                            <div class="flex items-center">
                                <div class="w-1.5 h-1.5 rounded-full {{ $u->status == 'active' ? 'bg-emerald-500' : 'bg-slate-300' }} mr-2"></div>
                                <span class="text-[10px] font-bold text-slate-600 uppercase">{{ $u->status == 'active' ? 'Activo' : 'Inactivo' }}</span>
                            </div>
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end space-x-1.5 opacity-30 group-hover:opacity-100 transition-opacity">
                                <!-- Reset Password -->
                                <form action="{{ route('admin.users.reset_password', $u->id) }}" method="POST" onsubmit="return confirm('¿GENERAR NUEVA CONTRASEÑA para {{ $u->name }}?\nLa clave anterior dejará de funcionar.')">
                                    @csrf @method('PATCH')
                                    <button type="submit" class="p-2 bg-slate-900 text-white rounded-lg hover:bg-blue-600 transition-all shadow-sm group/btn" title="Restablecer Clave">
                                        <i data-lucide="key-round" class="w-3.5 h-3.5 transition-transform group-hover/btn:rotate-12"></i>
                                    </button>
                                </form>
                                <!-- Edit -->
                                <button onclick='openEditModal(@json($u))' class="p-2 bg-white text-slate-400 border border-slate-100 hover:text-blue-600 hover:border-blue-200 rounded-lg transition-all shadow-sm">
                                    <i data-lucide="settings-2" class="w-3.5 h-3.5"></i>
                                </button>
                                <!-- Delete -->
                                <form action="{{ route('admin.users.destroy', $u->id) }}" method="POST" onsubmit="return confirm('¿ELIMINAR USUARIO?\nEsta acción es irreversible.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="p-2 bg-rose-50 text-rose-300 border border-rose-100 hover:text-rose-600 rounded-lg transition-all">
                                        <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-100">
            {{ $users->links() }}
        </div>
    </div>
</div>

<!-- Modal -->
<div id="modalUser" class="fixed inset-0 z-[60] hidden bg-slate-900/60 backdrop-blur-sm transition-all overflow-y-auto p-4">
    <div class="flex items-center justify-center min-h-screen">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden animate-in zoom-in duration-200 border border-slate-200">
            <!-- Modal Header -->
            <div class="bg-slate-900 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-blue-500/10 rounded-lg border border-blue-500/20">
                        <i data-lucide="shield" class="w-5 h-5 text-blue-500 italic"></i>
                    </div>
                    <div>
                        <h3 id="modalTitle" class="text-white font-black uppercase tracking-widest text-xs italic">Perfil Operativo</h3>
                        <p class="text-[9px] text-slate-500 font-bold uppercase">Gestión de Credenciales</p>
                    </div>
                </div>
                <button onclick="closeModal()" class="text-slate-400 hover:text-white p-1 bg-slate-800 rounded-full transition-colors"><i data-lucide="x" class="w-4 h-4"></i></button>
            </div>

            <!-- Modal Form -->
            <form id="userForm" method="POST" class="p-6 space-y-6 bg-slate-50/50">
                @csrf
                <div id="methodField"></div>

                <!-- Basic Info -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Nombre Completo <span class="text-rose-500">*</span></label>
                        <input type="text" name="name" id="userName" required class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-blue-500 uppercase transition-all shadow-sm">
                    </div>
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Email <span class="text-rose-500">*</span></label>
                        <input type="email" name="email" id="userEmail" required class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none focus:border-blue-500 transition-all shadow-sm">
                    </div>
                </div>

                <!-- Roles & Status -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Rol <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <select name="role" id="userRole" onchange="handleRoleChange()" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none appearance-none cursor-pointer shadow-sm">
                                <option value="admin">Administrador</option>
                                <option value="manager">Manager</option>
                                <option value="supervisor">Supervisor</option>
                                <option value="operator">Operador</option>
                                <option value="user">Cliente (Portal)</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 pointer-events-none"></i>
                        </div>
                    </div>
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-slate-400 uppercase tracking-widest ml-1">Estado <span class="text-rose-500">*</span></label>
                        <div class="relative">
                            <select name="status" id="userStatus" class="w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-xs font-bold text-slate-700 outline-none appearance-none cursor-pointer shadow-sm">
                                <option value="active">Activo</option>
                                <option value="inactive">Inactivo</option>
                            </select>
                            <i data-lucide="chevron-down" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-300 pointer-events-none"></i>
                        </div>
                    </div>
                </div>

                <!-- Client Selector (Solo si es User/Cliente) -->
                <div id="clientSelector" class="hidden animate-in fade-in slide-in-from-top-2 p-4 bg-emerald-50 border border-emerald-100 rounded-xl">
                    <label class="text-[9px] font-black text-emerald-800 uppercase mb-2 block tracking-widest">Empresa Vinculada</label>
                    <div class="relative">
                        <select name="client_id" id="userClientId" class="w-full px-4 py-2.5 bg-white border border-emerald-200 rounded-lg text-xs font-bold text-emerald-900 appearance-none outline-none">
                            <option value="">-- Seleccionar Cliente --</option>
                            @foreach($clients as $c)
                                <option value="{{ $c->id }}">{{ $c->company_name }}</option>
                            @endforeach
                        </select>
                        <i data-lucide="building" class="absolute right-4 top-1/2 -translate-y-1/2 w-4 h-4 text-emerald-300 pointer-events-none"></i>
                    </div>
                </div>

                <!-- Permissions Grid (Solo para Manager/Supervisor) -->
                <div id="permissionsGrid" class="hidden space-y-4 animate-in fade-in slide-in-from-top-2 p-4 bg-slate-100 border border-slate-200 rounded-xl">
                    <p class="text-[9px] font-black text-slate-500 uppercase tracking-widest border-b border-slate-200 pb-1.5 flex items-center">
                        <i data-lucide="layout-grid" class="w-3 h-3 mr-2"></i>Módulos Autorizados
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        @foreach($availableModules as $key => $label)
                        <label class="flex items-center p-2 bg-white border border-slate-200 rounded-lg cursor-pointer hover:border-blue-300 transition-all group shadow-sm">
                            <input type="checkbox" name="modules[]" value="{{ $key }}" class="w-3.5 h-3.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500/20 mr-2 permission-checkbox">
                            <span class="text-[9px] font-bold text-slate-600 uppercase group-hover:text-blue-600 transition-colors">{{ $label }}</span>
                        </label>
                        @endforeach
                    </div>
                </div>

                <div id="passwordNote" class="p-3 bg-blue-50 border border-blue-100 rounded-xl text-center">
                    <p class="text-[10px] text-blue-600 font-bold">
                        <i data-lucide="info" class="w-3 h-3 inline mr-1"></i>
                        La contraseña será generada automáticamente por el sistema.
                    </p>
                </div>

                <!-- Footer -->
                <div class="pt-4 border-t border-slate-200 flex items-center justify-end space-x-4">
                    <button type="button" onclick="closeModal()" class="text-[10px] font-black text-slate-400 uppercase tracking-widest hover:text-slate-600 transition-colors">Cancelar</button>
                    <button type="submit" class="bg-slate-900 text-white px-8 py-3 rounded-xl font-black text-[10px] uppercase tracking-widest hover:bg-blue-600 transition-all shadow-xl shadow-slate-200 group">
                        <span id="btnSubmitText">Confirmar Operación</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function handleRoleChange() {
        const role = document.getElementById('userRole').value;
        const clientDiv = document.getElementById('clientSelector');
        const permDiv = document.getElementById('permissionsGrid');
        
        // Mostrar selector cliente si es 'user'
        clientDiv.classList.toggle('hidden', role !== 'user');
        
        // Mostrar permisos si es manager o supervisor
        const showPerms = (role === 'manager' || role === 'supervisor');
        permDiv.classList.toggle('hidden', !showPerms);
    }

    function openCreateModal() {
        const form = document.getElementById('userForm');
        form.action = "{{ route('admin.users.store') }}";
        
        document.getElementById('methodField').innerHTML = '';
        document.getElementById('modalTitle').innerText = "Nuevo Perfil de Acceso";
        document.getElementById('btnSubmitText').innerText = "Crear y Generar Clave";
        document.getElementById('passwordNote').classList.remove('hidden');
        
        form.reset();
        document.querySelectorAll('.permission-checkbox').forEach(cb => cb.checked = false);
        
        handleRoleChange();
        toggleModal(true);
    }

    function openEditModal(user) {
        const form = document.getElementById('userForm');
        form.action = `/admin/users/${user.id}`;
        
        document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
        document.getElementById('modalTitle').innerText = "Modificar: " + user.name;
        document.getElementById('btnSubmitText').innerText = "Guardar Cambios";
        document.getElementById('passwordNote').classList.add('hidden');
        
        document.getElementById('userName').value = user.name;
        document.getElementById('userEmail').value = user.email;
        document.getElementById('userRole').value = user.role;
        document.getElementById('userClientId').value = user.client_id || '';
        document.getElementById('userStatus').value = user.status;
        
        const userPerms = user.permissions || [];
        document.querySelectorAll('.permission-checkbox').forEach(cb => {
            // Aseguramos que sea string para la comparación
            cb.checked = userPerms.includes(cb.value);
        });
        
        handleRoleChange();
        toggleModal(true);
    }

    function toggleModal(show) {
        const modal = document.getElementById('modalUser');
        modal.classList.toggle('hidden', !show);
        document.body.classList.toggle('overflow-hidden', show);
        if(show && typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeModal() { toggleModal(false); }

    document.addEventListener('DOMContentLoaded', () => {
        if (typeof lucide !== 'undefined') lucide.createIcons();
    });

    window.onclick = function(event) {
        const modal = document.getElementById('modalUser');
        if (event.target == modal) closeModal();
    }
</script>

<style>
    .animate-in { animation: animateIn 0.2s ease-out; }
    @keyframes animateIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
</style>
@endsection