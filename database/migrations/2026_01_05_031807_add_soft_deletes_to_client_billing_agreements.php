<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('client_billing_agreements')) {
            Schema::table('client_billing_agreements', function (Blueprint $table) {
                // Agregar columna status si no existe (Cura el SQLSTATE[42S22])
                if (!Schema::hasColumn('client_billing_agreements', 'status')) {
                    $table->string('status')->default('active')->after('start_date');
                }
                // Asegurar deleted_at para SoftDeletes
                if (!Schema::hasColumn('client_billing_agreements', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::table('client_billing_agreements', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->dropSoftDeletes();
        });
    }
};