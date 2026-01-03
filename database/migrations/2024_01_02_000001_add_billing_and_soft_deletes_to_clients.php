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
        Schema::table('clients', function (Blueprint $table) {
            // Para el Módulo de Facturación
            $table->string('billing_type')->default('transactional')->after('phone')
                  ->comment('transactional, flat, hybrid');
            
            // Para Soft Deletes (Papelera)
            $table->softDeletes(); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn('billing_type');
            $table->dropSoftDeletes();
        });
    }
};