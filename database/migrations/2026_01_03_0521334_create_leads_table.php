<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('branches', function (Blueprint $table) {
            // Añadimos country después de city y zip después de state
            if (!Schema::hasColumn('branches', 'country')) {
                $table->string('country')->nullable()->after('city');
            }
            if (!Schema::hasColumn('branches', 'zip')) {
                $table->string('zip', 20)->nullable()->after('state');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['country', 'zip']);
        });
    }
};