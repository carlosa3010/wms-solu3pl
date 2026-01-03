<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estación PC - Packing</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <style>
        .custom-scrollbar::-webkit-scrollbar { width: 8px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: #1f2937; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #4b5563; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #6b7280; }
    </style>
</head>
<body class="bg-gray-900 h-screen flex flex-col text-white font-mono overflow-hidden">

    <!-- Header Operativo -->
    <header class="bg-black h-14 flex items-center justify-between px-4 border-b border-gray-700 shrink-0 shadow-md z-10">
        <div class="flex items-center gap-4">
            <span class="bg-purple-600 px-2 py-0.5 rounded text-sm font-bold shadow-lg shadow-purple-900/50 tracking-wider">PACKING MODE</span>
            <span class="text-gray-400 text-sm">Estación: <span class="text-white font-bold">PACK-01</span></span>
        </div>
        <div class="flex items-center gap-4 text-xs">
            <span class="text-green-500 font-bold border border-green-900 bg-green-900/20 px-2 py-0.5 rounded flex items-center gap-1">
                <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span> ONLINE
            </span>
            
            <!-- Botón Salir (Logout) -->
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="bg-red-900/40 hover:bg-red-700 border border-red-800 text-red-200 px-3 py-1 rounded transition flex items-center gap-2 font-bold">
                    <i class="fa-solid fa-power-off"></i> SALIR
                </button>
            </form>
        </div>
    </header>

    <main class="flex-1 flex gap-0 overflow-hidden relative">
        <!-- PANEL IZQUIERDO: Acción Principal -->
        <div class="w-2/3 flex flex-col p-6 gap-6 border-r border-gray-800 relative bg-gray-900">
            
            @if($currentJob)
                <div class="bg-gray-800 p-6 rounded-xl border-l-4 border-blue-500 shadow-2xl flex justify-between items-start transition-all hover:bg-gray-800/80">
                    <div>
                        <h2 class="text-4xl font-bold text-white mb-2 tracking-tight">Pedido #{{ $currentJob->order_number }}</h2>
                        <div class="flex flex-col gap-2 mt-2">
                            <p class="text-blue-400 font-bold text-lg flex items-center">
                                <i class="fa-solid fa-building mr-2 w-5 text-center"></i>
                                {{ $currentJob->client->company_name ?? 'Cliente Desconocido' }}
                            </p>
                            <p class="text-gray-400 text-sm flex items-center">
                                <i class="fa-solid fa-user mr-2 w-5 text-center"></i>
                                Destinatario: <span class="text-gray-200 font-semibold ml-1">{{ $currentJob->customer_name }}</span>
                            </p>
                        </div>
                    </div>
                    <div class="text-right flex flex-col items-end gap-2">
                        <span class="bg-yellow-600 text-white px-4 py-1.5 rounded text-sm font-bold animate-pulse shadow-lg shadow-yellow-900/40 uppercase tracking-widest">En Proceso</span>
                    </div>
                </div>
            @else
                <div class="flex-1 flex flex-col items-center justify-center">
                    <div class="bg-gray-800 p-12 rounded-2xl text-center border-2 border-gray-700 border-dashed max-w-lg mx-auto opacity-75">
                        <i class="fa-solid fa-mug-hot text-5xl text-gray-400 mb-4"></i>
                        <h2 class="text-3xl font-bold text-gray-300 mb-2">Sin pedidos pendientes</h2>
                        <p class="text-gray-500 text-lg">Tomate un descanso.</p>
                    </div>
                </div>
            @endif

            <!-- Input de Escaneo -->
            <div class="mt-auto pt-6 border-t border-gray-800 sticky bottom-0 bg-gray-900 pb-2">
                <label class="text-xs text-yellow-500 font-bold uppercase mb-2 block ml-1 tracking-wider">
                    <i class="fa-solid fa-barcode mr-1"></i> Escáner Listo
                </label>
                <div class="relative group">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fa-solid fa-barcode text-yellow-500 text-2xl group-focus-within:text-yellow-400 transition-colors"></i>
                    </div>
                    <input type="text" 
                           class="w-full bg-black text-yellow-400 text-3xl font-bold pl-14 pr-4 py-5 rounded-lg border-2 border-yellow-700 focus:outline-none focus:border-yellow-500 focus:ring-4 focus:ring-yellow-900/40 uppercase placeholder-yellow-900/50 transition-all shadow-xl" 
                           placeholder="ESCANEAR SKU..." 
                           autofocus
                           autocomplete="off">
                </div>
            </div>
        </div>

        <!-- PANEL DERECHO: Cola de Trabajo -->
        <div class="w-1/3 bg-gray-800 border-l border-gray-700 p-6 flex flex-col shadow-2xl z-10">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-gray-400 text-sm font-bold uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-list-ul"></i> Cola de Trabajo
                </h3>
                <span class="bg-blue-900 text-blue-200 px-2.5 py-1 rounded-full text-xs font-bold border border-blue-700">{{ $pendingQueue->count() }}</span>
            </div>
            
            <div class="space-y-3 overflow-y-auto pr-2 custom-scrollbar flex-1 -mr-2">
                @foreach($pendingQueue as $order)
                    <div class="p-4 bg-gray-700/30 hover:bg-gray-700 rounded-lg border border-gray-600/50 transition-all cursor-pointer group hover:border-gray-500 hover:shadow-lg {{ $loop->first ? 'border-l-4 border-l-blue-500 bg-blue-900/10' : '' }}">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-bold text-white text-lg group-hover:text-blue-300 transition font-mono tracking-tight">{{ $order->order_number }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-xs text-gray-400 truncate font-medium">{{ $order->client->company_name ?? 'N/A' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
            
             <div class="mt-4 pt-4 border-t border-gray-700 text-center">
                <button class="w-full bg-gray-700 hover:bg-gray-600 text-gray-300 hover:text-white text-sm py-3 rounded-lg transition-colors font-bold flex items-center justify-center gap-2 shadow-sm border border-gray-600 hover:border-gray-500" onclick="window.location.reload();">
                    <i class="fa-solid fa-rotate"></i> Actualizar Lista
                </button>
            </div>
        </div>
    </main>
</body>
</html>