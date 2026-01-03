<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('bin_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Ej: Pallet Standard, Gaveta Pequeña
            $table->string('code')->unique(); // Ej: PLT-STD, BIN-S
            
            // Dimensiones en CM
            $table->decimal('length', 8, 2); 
            $table->decimal('width', 8, 2);
            $table->decimal('height', 8, 2);
            
            // Capacidad Máxima en KG
            $table->decimal('max_weight', 8, 2)->default(0);
            
            $table->timestamps();
        });

        // Insertar datos semilla básicos
        DB::table('bin_types')->insert([
            [
                'name' => 'Pallet Standard', 'code' => 'PLT-STD', 
                'length' => 120, 'width' => 100, 'height' => 150, 'max_weight' => 1000
            ],
            [
                'name' => 'Caja Master', 'code' => 'BOX-MST', 
                'length' => 60, 'width' => 40, 'height' => 40, 'max_weight' => 30
            ],
            [
                'name' => 'Gaveta Picking', 'code' => 'BIN-PICK', 
                'length' => 30, 'width' => 20, 'height' => 15, 'max_weight' => 5
            ],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('bin_types');
    }
};