<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('client_billing_agreements', function (Blueprint $table) {
            // Añadir timestamps si no existen (Cura el error updated_at / created_at)
            if (!Schema::hasColumn('client_billing_agreements', 'created_at')) {
                $table->timestamps();
            }
            // Añadir columna status (Cura el error Unknown column 'status')
            if (!Schema::hasColumn('client_billing_agreements', 'status')) {
                $table->string('status')->default('active')->after('start_date');
            }
            // Asegurar SoftDeletes
            if (!Schema::hasColumn('client_billing_agreements', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('client_billing_agreements', function (Blueprint $table) {
            $table->dropTimestamps();
            $table->dropColumn('status');
            $table->dropSoftDeletes();
        });
    }
};