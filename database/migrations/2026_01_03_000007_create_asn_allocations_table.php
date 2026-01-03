<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('asn_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asn_item_id')->constrained('asn_items')->onDelete('cascade');
            $table->foreignId('location_id')->constrained('locations');
            $table->integer('quantity'); // Cantidad asignada a este bin específico
            $table->string('status')->default('planned'); // planned, put_away
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('asn_allocations');
    }
};