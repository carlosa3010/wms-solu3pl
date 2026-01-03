<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Esta tabla guarda de qué BIN específico se debe sacar cada producto del pedido
        Schema::create('order_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_item_id')->constrained('order_items')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations');
            $table->integer('quantity'); // Cantidad a extraer de este bin
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('order_allocations');
    }
};