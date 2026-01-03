<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. Crear 'warehouses' SOLO si no existe
        if (!Schema::hasTable('warehouses')) {
            Schema::create('warehouses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('branches')->onDelete('cascade');
                $table->string('name');
                $table->string('code')->unique();
                $table->integer('rows')->default(10);
                $table->integer('cols')->default(10);
                $table->timestamps();
            });
        }

        // 2. Modificar 'locations'
        if (Schema::hasTable('locations')) {
            
            // FASE A: LIMPIEZA QUIRÚRGICA (Raw SQL)
            // Ejecutamos esto ANTES de Schema::table para evitar bloqueos del Blueprint
            if (Schema::hasColumn('locations', 'branch_id')) {
                
                // Lista de posibles nombres de FKs e Indices que pueden estar estorbando
                // 'unique_location_code' es CRÍTICO porque usa branch_id
                $cleanupTargets = [
                    'FK' => ['locations_ibfk_1', 'locations_branch_id_foreign'],
                    'INDEX' => ['unique_location_code', 'locations_branch_id_index', 'branch_id', 'locations_ibfk_1']
                ];

                // 1. Intentar eliminar Foreign Keys
                foreach ($cleanupTargets['FK'] as $fk) {
                    try {
                        DB::statement("ALTER TABLE `locations` DROP FOREIGN KEY `{$fk}`");
                    } catch (\Exception $e) {
                        // Ignoramos si no existe
                    }
                }

                // 2. Intentar eliminar Índices
                foreach ($cleanupTargets['INDEX'] as $index) {
                    try {
                        DB::statement("ALTER TABLE `locations` DROP INDEX `{$index}`");
                    } catch (\Exception $e) {
                        // Ignoramos si no existe
                    }
                }
            }

            // FASE B: MODIFICACIÓN ESTRUCTURAL
            Schema::table('locations', function (Blueprint $table) {
                
                // Ahora es seguro borrar la columna
                if (Schema::hasColumn('locations', 'branch_id')) {
                    $table->dropColumn('branch_id');
                }

                // Agregar la nueva relación con warehouse
                if (!Schema::hasColumn('locations', 'warehouse_id')) {
                    $table->foreignId('warehouse_id')
                          ->after('id')
                          ->constrained('warehouses')
                          ->onDelete('cascade');
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('locations')) {
            Schema::table('locations', function (Blueprint $table) {
                // Revertir changes
                if (Schema::hasColumn('locations', 'warehouse_id')) {
                    try {
                        $table->dropForeign(['warehouse_id']);
                    } catch (\Exception $e) {}
                    $table->dropColumn('warehouse_id');
                }
                
                if (!Schema::hasColumn('locations', 'branch_id')) {
                    $table->foreignId('branch_id')->nullable()->constrained('branches');
                }
            });
        }
    }
};