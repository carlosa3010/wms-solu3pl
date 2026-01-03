<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            // Roles: admin, operator, manager, client
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default('client')->after('email');
            }
            // Permisos JSON para el rol Manager/Supervisor
            if (!Schema::hasColumn('users', 'permissions')) {
                $table->json('permissions')->nullable()->after('role');
            }
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'permissions']);
        });
    }
};