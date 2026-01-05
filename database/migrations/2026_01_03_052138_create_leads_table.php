<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Limpieza preventiva de duplicados antes de aplicar la restricción
        // Mantenemos el ID más reciente y borramos los antiguos con el mismo código en la misma bodega
        $duplicates = DB::select("
            SELECT id 
            FROM locations 
            WHERE id NOT IN (
                SELECT MAX(id) 
                FROM locations 
                GROUP BY warehouse_id, code
            )
        ");

        if (!empty($duplicates)) {
            $idsToDelete = array_column($duplicates, 'id');
            // Usamos forceDelete o delete directo DB para limpiar la tabla física
            DB::table('locations')->whereIn('id', $idsToDelete)->delete();
        }

        // 2. Aplicar restricción UNIQUE
        Schema::table('locations', function (Blueprint $table) {
            // Aseguramos que la combinación bodega + código sea única
            // Nota: Usamos un nombre corto para el índice para evitar errores de longitud
            $table->unique(['warehouse_id', 'code'], 'loc_wh_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropUnique('loc_wh_code_unique');
        });
    }
};