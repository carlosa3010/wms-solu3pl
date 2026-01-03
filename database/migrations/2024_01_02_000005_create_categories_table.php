<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Ejecuta las migraciones.
     * Crea la tabla categories y migra los datos existentes de products.
     */
    public function up(): void
    {
        // 1. Crear la tabla de categorías primero
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Agregar la nueva columna category_id a products (pero permitir NULL por ahora)
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'category_id')) {
                $table->foreignId('category_id')
                      ->nullable()
                      ->after('image_url')
                      ->constrained()
                      ->nullOnDelete();
            }
        });

        // 3. MIGRACIÓN DE DATOS: Convertir texto viejo en registros de la nueva tabla
        if (Schema::hasColumn('products', 'category')) {
            // Obtener nombres de categorías únicas que ya existan en los productos
            $existingCategories = DB::table('products')
                ->whereNotNull('category')
                ->distinct()
                ->pluck('category');

            foreach ($existingCategories as $catName) {
                // Crear la categoría si no existe (por nombre)
                $catId = DB::table('categories')->insertGetId([
                    'name' => ucfirst($catName),
                    'slug' => Str::slug($catName),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Actualizar los productos que tenían ese nombre de texto con el nuevo ID
                DB::table('products')
                    ->where('category', $catName)
                    ->update(['category_id' => $catId]);
            }

            // 4. Ahora que los datos están a salvo, eliminamos la columna de texto antigua
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('category');
            });
        }
    }

    /**
     * Revierte las migraciones.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'category')) {
                $table->string('category')->nullable()->after('image_url');
            }
            
            // Intentar restaurar el texto antes de borrar la relación (opcional)
            // Esto es más complejo de revertir fielmente sin tipos de datos
            
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });
        
        Schema::dropIfExists('categories');
    }
};