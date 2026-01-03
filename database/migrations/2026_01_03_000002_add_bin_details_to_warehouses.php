<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('warehouses', function (Blueprint $table) {
            // Dimensiones físicas del Bin (Ej: "1.2x1.0x1.5m")
            $table->string('bin_size')->nullable()->after('cols');
            
            // Cantidad de niveles verticales por Rack (Altura)
            // Esto define la "cantidad" de bins por posición en el suelo
            $table->integer('levels')->default(1)->after('bin_size'); 
        });
    }

    public function down()
    {
        Schema::table('warehouses', function (Blueprint $table) {
            $table->dropColumn(['bin_size', 'levels']);
        });
    }
};