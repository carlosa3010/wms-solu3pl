<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Verifica si la columna ya existe antes de agregarla para evitar errores
            if (!Schema::hasColumn('locations', 'deleted_at')) {
                $table->softDeletes(); // Agrega la columna deleted_at
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};