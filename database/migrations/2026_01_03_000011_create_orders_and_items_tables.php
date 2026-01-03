<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. Cabecera de la Devolución
        Schema::create('rmas', function (Blueprint $table) {
            $table->id();
            $table->string('rma_number')->unique(); // Ej: RMA-2024-0001
            $table->foreignId('order_id')->nullable()->constrained('orders'); // Vínculo con pedido original
            $table->foreignId('client_id')->constrained('clients');
            
            $table->string('customer_name');
            $table->string('reason'); // defective, wrong_item, delivery_failure, customer_return
            $table->string('status')->default('pending'); // pending, received, inspecting, completed, cancelled
            
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        // 2. Detalle de productos devueltos
        Schema::create('rma_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rma_id')->constrained('rmas')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            
            $table->integer('quantity');
            $table->string('condition')->nullable(); // new, damaged, open_box
            $table->string('action_taken')->nullable(); // restock, quarantine, discard
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('rma_items');
        Schema::dropIfExists('rmas');
    }
};