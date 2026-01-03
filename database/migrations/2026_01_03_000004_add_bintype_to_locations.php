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
        Schema::table('locations', function (Blueprint $table) {
            // Agregamos la columna bin_type_id después de warehouse_id
            // nullable() porque un bin podría no tener tipo definido aún
            $table->foreignId('bin_type_id')
                  ->nullable()
                  ->after('warehouse_id')
                  ->constrained('bin_types')
                  ->onDelete('set null'); // Si borras el tipo de bin, la ubicación queda sin tipo (no se borra la ubicación)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropForeign(['bin_type_id']);
            $table->dropColumn('bin_type_id');
        });
    }
};