<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('inventory', function (Blueprint $table) {
            // Verificamos individualmente para evitar errores de duplicado
            if (!Schema::hasColumn('inventory', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }
            
            if (!Schema::hasColumn('inventory', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('inventory', function (Blueprint $table) {
            // Solo intentamos borrar si existen
            if (Schema::hasColumn('inventory', 'created_at')) {
                $table->dropColumn('created_at');
            }
            if (Schema::hasColumn('inventory', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
        });
    }
};