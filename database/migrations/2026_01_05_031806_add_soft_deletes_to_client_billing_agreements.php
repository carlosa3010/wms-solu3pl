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
        if (Schema::hasTable('client_billing_agreements')) {
            Schema::table('client_billing_agreements', function (Blueprint $table) {
                // Solo intentamos crear la columna si NO existe
                if (!Schema::hasColumn('client_billing_agreements', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('client_billing_agreements')) {
            Schema::table('client_billing_agreements', function (Blueprint $table) {
                if (Schema::hasColumn('client_billing_agreements', 'deleted_at')) {
                    $table->dropSoftDeletes();
                }
            });
        }
    }
};