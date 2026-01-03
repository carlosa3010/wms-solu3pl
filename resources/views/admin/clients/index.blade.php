@extends('layouts.admin')

@section('content')
<div class="container mx-auto px-4 py-8">
    <!-- Encabezado -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Gestión de Clientes</h1>
        <a href="{{ route('admin.clients.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300 ease-in-out flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="12 4v16m8-8H4"></path>
            </svg>
            Nuevo Cliente
        </a>
    </div>

    <!-- Mensajes de Sesión -->
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm" role="alert">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    @if(session('info'))
        <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 rounded shadow-sm" role="alert">
            <p>{{ session('info') }}</p>
        </div>
    @endif

    <!-- Tabla de Clientes -->
    <div class="bg-white shadow-md rounded-lg overflow-hidden">
        <table class="min-w-full leading-normal text-sm">
            <thead>
                <tr class="bg-gray-100 text-gray-600 text-left text-xs font-semibold uppercase tracking-wider">
                    <th class="px-5 py-3 border-b-2 border-gray-200">Compañía / Tax ID</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200">Contacto</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200">Email</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 text-center">Estado</th>
                    <th class="px-5 py-3 border-b-2 border-gray-200 text-right">Acciones</th>
                </tr>
            </thead>
            <tbody class="text-gray-700">
                @forelse($clients as $client)
                <tr>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white">
                        <div class="flex items-center">
                            @if($client->logo_url)
                                <div class="flex-shrink-0 w-10 h-10 mr-3">
                                    <img class="w-full h-full rounded-full object-cover border" src="{{ asset('storage/' . $client->logo_url) }}" alt="Logo">
                                </div>
                            @endif
                            <div>
                                <p class="text-gray-900 whitespace-no-wrap font-bold">{{ $client->company_name }}</p>
                                <p class="text-gray-500 text-xs">{{ $client->tax_id }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white">
                        {{ $client->contact_name }}
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white">
                        {{ $client->email }}
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-center">
                        @if($client->is_active)
                            <span class="relative inline-block px-3 py-1 font-semibold text-green-900 leading-tight">
                                <span aria-hidden class="absolute inset-0 bg-green-200 opacity-50 rounded-full"></span>
                                <span class="relative text-xs">Activo</span>
                            </span>
                        @else
                            <span class="relative inline-block px-3 py-1 font-semibold text-red-900 leading-tight">
                                <span aria-hidden class="absolute inset-0 bg-red-200 opacity-50 rounded-full"></span>
                                <span class="relative text-xs">Suspendido</span>
                            </span>
                        @endif
                    </td>
                    <td class="px-5 py-5 border-b border-gray-200 bg-white text-right">
                        <div class="flex justify-end items-center space-x-2">
                            
                            <!-- Botón Reset Contraseña -->
                            <form action="{{ route('admin.clients.reset_password', $client->id) }}" method="POST" onsubmit="return confirm('¿Restablecer la contraseña de {{ $client->company_name }}?')">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-yellow-600 hover:text-yellow-900 bg-yellow-100 p-2 rounded-lg transition" title="Resetear Contraseña">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                    </svg>
                                </button>
                            </form>

                            <!-- Botón Suspender / Reactivar -->
                            <form action="{{ route('admin.clients.toggle', $client->id) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                @if($client->is_active)
                                    <button type="submit" class="text-red-600 hover:text-red-900 bg-red-100 p-2 rounded-lg transition" title="Suspender Cliente">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                        </svg>
                                    </button>
                                @else
                                    <button type="submit" class="text-green-600 hover:text-green-900 bg-green-100 p-2 rounded-lg transition" title="Reactivar Cliente">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </button>
                                @endif
                            </form>

                            <!-- Botón Editar -->
                            <a href="{{ route('admin.clients.edit', $client->id) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-100 p-2 rounded-lg transition" title="Editar">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </a>

                            <!-- Botón Eliminar -->
                            <form action="{{ route('admin.clients.destroy', $client->id) }}" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este cliente?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-gray-600 hover:text-gray-900 bg-gray-100 p-2 rounded-lg transition" title="Eliminar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-5 py-5 border-b border-gray-200 bg-white text-sm text-center text-gray-500 italic">
                        No se encontraron clientes registrados.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
        
        <!-- Paginación -->
        @if($clients->hasPages())
        <div class="px-5 py-5 bg-white border-t flex flex-col xs:flex-row items-center xs:justify-between">
            {{ $clients->links() }}
        </div>
        @endif
    </div>
</div>
@endsection