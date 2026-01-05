<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\QueryException;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // 1. Verificamos cada columna antes de agregarla para evitar errores de duplicado
            
            if (!Schema::hasColumn('locations', 'aisle')) {
                $table->string('aisle', 50)->nullable()->after('warehouse_id')->comment('Identificador del Pasillo');
            }
            
            if (!Schema::hasColumn('locations', 'side')) {
                // Intentamos ubicarla después de 'aisle', si no, después de 'warehouse_id'
                $after = Schema::hasColumn('locations', 'aisle') ? 'aisle' : 'warehouse_id';
                $table->string('side', 10)->nullable()->after($after)->comment('Lado del Pasillo (A/B, Izq/Der)');
            }

            if (!Schema::hasColumn('locations', 'rack')) {
                $after = Schema::hasColumn('locations', 'side') ? 'side' : 'warehouse_id';
                $table->string('rack', 50)->nullable()->after($after)->comment('Identificador del Rack');
            }

            if (!Schema::hasColumn('locations', 'level')) {
                $after = Schema::hasColumn('locations', 'rack') ? 'rack' : 'warehouse_id';
                $table->string('level', 50)->nullable()->after($after)->comment('Nivel o Estante');
            }

            if (!Schema::hasColumn('locations', 'position')) {
                $after = Schema::hasColumn('locations', 'level') ? 'level' : 'warehouse_id';
                $table->string('position', 50)->nullable()->after($after)->comment('Posición o Bin específico');
            }
        });

        // 2. Intentamos crear índices en un bloque separado con try-catch
        // Esto previene que falle si los índices ya existen
        try {
            Schema::table('locations', function (Blueprint $table) {
                // Solo intentamos crear si no chocan con nombres generados por defecto
                // Laravel genera nombres como: locations_warehouse_id_aisle_index
                $table->index(['warehouse_id', 'aisle']);
                $table->index(['warehouse_id', 'aisle', 'rack']);
            });
        } catch (QueryException $e) {
            // Código 1061 es "Duplicate key name" en MySQL
            if ($e->errorInfo[1] != 1061) {
                // Si es otro error, lo lanzamos, si es duplicado, lo ignoramos
                throw $e;
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            // Eliminamos índices primero (best effort)
            try {
                $table->dropIndex(['warehouse_id', 'aisle']);
                $table->dropIndex(['warehouse_id', 'aisle', 'rack']);
            } catch (\Exception $e) {}

            // Eliminamos columnas si existen
            $columns = ['aisle', 'side', 'rack', 'level', 'position'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('locations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};