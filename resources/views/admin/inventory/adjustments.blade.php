@extends('layouts.admin')

@section('title', 'Ajustes de Inventario')
@section('header_title', 'Corrección de Stock')

@section('content')

    {{-- Feedback Mensajes --}}
    @if(session('success'))
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded shadow-sm animate-fade-in flex items-center gap-3">
            <i class="fa-solid fa-check-circle text-xl"></i>
            <div>
                <p class="font-bold text-sm">Operación Exitosa</p>
                <p class="text-xs">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded shadow-sm animate-fade-in">
            <div class="flex items-center gap-3 mb-2">
                <i class="fa-solid fa-triangle-exclamation text-xl"></i>
                <p class="font-bold text-sm">Error de Validación</p>
            </div>
            <ul class="list-disc list-inside text-xs ml-8">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="max-w-5xl mx-auto">
        
        <!-- Tarjeta Principal de Ajuste -->
        <div class="bg-white rounded-2xl shadow-lg border border-slate-200 overflow-hidden">
            
            <!-- Cabecera -->
            <div class="p-6 border-b border-slate-100 bg-slate-50 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                        <i class="fa-solid fa-scale-balanced text-custom-primary"></i> Nuevo Ajuste Manual
                    </h3>
                    <p class="text-xs text-slate-500 mt-1">Utilice este formulario para corregir diferencias de inventario (Mermas, Sobrantes, Daños).</p>
                </div>
                <div class="flex items-center gap-2 bg-white px-3 py-1.5 rounded-lg border border-slate-200 shadow-sm">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Ref:</span>
                    <span class="text-xs font-mono text-slate-600 font-bold">ADJ-{{ date('Ymd-Hi') }}</span>
                </div>
            </div>
            
            <form action="{{ route('admin.inventory.adjustments.store') }}" method="POST" class="p-8">
                @csrf
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-12">
                    
                    <!-- Columna Izquierda: QUÉ y DÓNDE -->
                    <div class="space-y-6">
                        <div class="border-b border-slate-100 pb-2 mb-4">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Identificación</h4>
                        </div>

                        <!-- Selector de Producto -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2">Producto Afectado <span class="text-red-500">*</span></label>
                            <div class="relative group">
                                <span class="absolute left-4 top-3.5 text-slate-400 group-focus-within:text-custom-primary transition"><i class="fa-solid fa-box-open"></i></span>
                                <select name="product_id" required class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary border-custom-primary outline-none bg-white transition shadow-sm appearance-none">
                                    <option value="" disabled selected>-- Seleccionar SKU --</option>
                                    @foreach($products as $product)
                                        <option value="{{ $product->id }}">{{ $product->sku }} - {{ $product->name }}</option>
                                    @endforeach
                                </select>
                                <span class="absolute right-4 top-4 text-slate-400 pointer-events-none"><i class="fa-solid fa-chevron-down text-xs"></i></span>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1.5 ml-1">Seleccione el ítem del catálogo maestro.</p>
                        </div>

                        <!-- Ubicación (Bin) -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2">Ubicación Física (Bin) <span class="text-red-500">*</span></label>
                            <div class="relative group">
                                <span class="absolute left-4 top-3.5 text-slate-400 group-focus-within:text-custom-primary transition"><i class="fa-solid fa-qrcode"></i></span>
                                <input type="text" name="location_code" list="locationsList" required 
                                       class="w-full pl-10 pr-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary border-custom-primary outline-none font-mono uppercase transition shadow-sm" 
                                       placeholder="Ej: BOD1-P01-A-R05-N1-B1">
                                
                                {{-- Datalist para autocompletado simple (opcional) --}}
                                <datalist id="locationsList">
                                    @foreach($locations as $loc)
                                        <option value="{{ $loc->code }}">{{ $loc->warehouse->name ?? 'Sin Bodega' }}</option>
                                    @endforeach
                                </datalist>
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1.5 ml-1"><i class="fa-solid fa-info-circle mr-1"></i>Escanee la etiqueta del rack o ingrese el código manualmente.</p>
                        </div>
                    </div>

                    <!-- Columna Derecha: CUÁNTO y POR QUÉ -->
                    <div class="space-y-6">
                        <div class="border-b border-slate-100 pb-2 mb-4">
                            <h4 class="text-xs font-black text-slate-400 uppercase tracking-widest">Detalles del Ajuste</h4>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <!-- Tipo de Ajuste -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2">Tipo de Movimiento <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <select name="type" id="adjustmentType" onchange="updateTypeStyles()" required class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none bg-white transition shadow-sm appearance-none font-bold text-slate-600">
                                        <option value="in" class="text-green-600 font-bold">🟢 Entrada (+) Sobrante</option>
                                        <option value="out" class="text-red-600 font-bold">🔴 Salida (-) Merma/Daño</option>
                                    </select>
                                    <span class="absolute right-4 top-4 text-slate-400 pointer-events-none"><i class="fa-solid fa-chevron-down text-xs"></i></span>
                                </div>
                            </div>

                            <!-- Cantidad -->
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2">Cantidad <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input type="number" name="quantity" min="1" required 
                                           class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none text-center font-bold text-slate-700 transition shadow-sm" 
                                           placeholder="0">
                                </div>
                            </div>
                        </div>

                        <!-- Motivo -->
                        <div>
                            <label class="block text-xs font-bold text-slate-600 mb-2">Motivo / Justificación <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <textarea name="reason" rows="3" required 
                                          class="w-full px-4 py-3 border border-slate-300 rounded-xl text-sm focus:ring-2 ring-custom-primary outline-none transition shadow-sm resize-none" 
                                          placeholder="Describa brevemente la razón del ajuste (Ej: Conteo cíclico, Producto dañado en traslado, etc.)"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Acciones -->
                <div class="flex flex-col sm:flex-row justify-end items-center gap-4 mt-8 pt-6 border-t border-slate-100">
                    <a href="{{ route('admin.inventory.stock') }}" class="text-slate-500 text-sm font-bold hover:text-slate-700 transition px-4 py-2">
                        Cancelar
                    </a>
                    <button type="submit" class="w-full sm:w-auto bg-custom-primary text-white px-8 py-3 rounded-xl font-bold hover:shadow-lg hover:brightness-95 transition transform active:scale-95 flex items-center justify-center gap-2">
                        <i class="fa-solid fa-floppy-disk"></i> Procesar Ajuste
                    </button>
                </div>
            </form>
        </div>

        <!-- Tips Rápidos -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-blue-50 p-4 rounded-xl border border-blue-100 flex items-start gap-3">
                <i class="fa-solid fa-lightbulb text-blue-500 mt-1"></i>
                <div>
                    <h5 class="text-xs font-bold text-blue-700 mb-1">Impacto en Kardex</h5>
                    <p class="text-[10px] text-blue-600 leading-snug">Cada ajuste genera un registro histórico inmutable en el Kardex para auditoría.</p>
                </div>
            </div>
            <div class="bg-orange-50 p-4 rounded-xl border border-orange-100 flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation text-orange-500 mt-1"></i>
                <div>
                    <h5 class="text-xs font-bold text-orange-700 mb-1">Stock Negativo</h5>
                    <p class="text-[10px] text-orange-600 leading-snug">El sistema no permite realizar salidas si el stock resultante es menor a cero.</p>
                </div>
            </div>
            <div class="bg-purple-50 p-4 rounded-xl border border-purple-100 flex items-start gap-3">
                <i class="fa-solid fa-barcode text-purple-500 mt-1"></i>
                <div>
                    <h5 class="text-xs font-bold text-purple-700 mb-1">LPN / Lotes</h5>
                    <p class="text-[10px] text-purple-600 leading-snug">Si el producto maneja LPN, asegúrese de que el ajuste se aplique al lote correcto.</p>
                </div>
            </div>
        </div>

    </div>

@endsection

@section('scripts')
<script>
    function updateTypeStyles() {
        const select = document.getElementById('adjustmentType');
        // Simple lógica visual si quisieras cambiar el borde o fondo según selección
        // Por ahora se mantiene limpio
    }
</script>
@endsection