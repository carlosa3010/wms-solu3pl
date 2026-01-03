<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');

        // 1. Cabecera del Pedido
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('client_id')->constrained('clients');
            $table->foreignId('branch_id')->nullable()->constrained('branches');
            
            // Datos del Destinatario
            $table->string('customer_name');
            $table->string('customer_id_number'); // NUEVO: Cédula o RIF
            $table->string('customer_email')->nullable();
            $table->text('shipping_address');
            $table->string('city');
            $table->string('state'); 
            $table->string('country')->default('Venezuela');
            $table->string('phone')->nullable();

            // Estado del Pedido
            $table->string('status')->default('pending');
            
            $table->string('shipping_method')->nullable();
            $table->string('external_ref')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });

        // 2. Detalle del Pedido
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            
            $table->integer('requested_quantity');
            $table->integer('allocated_quantity')->default(0);
            $table->integer('picked_quantity')->default(0);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};