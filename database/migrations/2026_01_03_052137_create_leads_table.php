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
            // Verificamos si la columna 'status' NO existe para agregarla
            if (!Schema::hasColumn('locations', 'status')) {
                // Se agrega con valor por defecto 'active' para registros existentes
                $table->string('status', 20)->default('active')->after('type')->index();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (Schema::hasColumn('locations', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
};