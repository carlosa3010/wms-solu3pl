@extends('layouts.warehouse')

@section('content')
<div class="h-full flex flex-col items-center justify-center text-center p-6">
    
    <div class="w-24 h-24 bg-green-500/20 rounded-full flex items-center justify-center text-green-500 mb-6 animate-pulse">
        <i class="fa-solid fa-check-to-slot text-5xl"></i>
    </div>

    <h1 class="text-3xl font-black text-white mb-2">¡Picking Completo!</h1>
    <p class="text-slate-400 mb-8 max-w-xs mx-auto">
        La orden <strong class="text-white">#{{ $order->order_number }}</strong> ha sido recolectada en su totalidad.
    </p>

    <form action="{{ route('warehouse.picking.complete', $order->id) }}" method="POST" class="w-full max-w-sm">
        @csrf
        <button type="submit" class="w-full bg-green-600 hover:bg-green-500 text-white font-bold py-4 rounded-2xl shadow-lg shadow-green-900/50 transition transform active:scale-95 text-lg">
            Enviar a Packing <i class="fa-solid fa-arrow-right ml-2"></i>
        </button>
    </form>

</div>
@endsection