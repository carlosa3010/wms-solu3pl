<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Agregamos validaciones para evitar errores si las columnas ya existen.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'barcode')) {
                $table->string('barcode')->nullable()->after('sku');
            }
            
            if (!Schema::hasColumn('products', 'image_url')) {
                $table->string('image_url')->nullable()->after('description');
            }
            
            if (!Schema::hasColumn('products', 'category')) {
                $table->string('category')->default('general')->after('image_url');
            }
            
            if (!Schema::hasColumn('products', 'length_cm')) {
                $table->decimal('length_cm', 8, 2)->default(0)->after('weight_kg');
            }
            
            if (!Schema::hasColumn('products', 'width_cm')) {
                $table->decimal('width_cm', 8, 2)->default(0)->after('length_cm');
            }
            
            if (!Schema::hasColumn('products', 'height_cm')) {
                $table->decimal('height_cm', 8, 2)->default(0)->after('width_cm');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = ['barcode', 'image_url', 'category', 'length_cm', 'width_cm', 'height_cm'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};