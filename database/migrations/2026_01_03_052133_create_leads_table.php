<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Tabla de Países
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('iso_code', 3)->nullable();
            $table->timestamps();
        });

        // Tabla de Estados (Provincias)
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('country_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->timestamps();
        });

        // --- PRECARGA DE DATOS ---
        
        // 1. Insertar Venezuela
        $countryId = DB::table('countries')->insertGetId([
            'name' => 'Venezuela',
            'iso_code' => 'VE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Insertar Estados de Venezuela
        $states = [
            ['name' => 'Amazonas'], ['name' => 'Anzoátegui'], ['name' => 'Apure'],
            ['name' => 'Aragua'], ['name' => 'Barinas'], ['name' => 'Bolívar'],
            ['name' => 'Carabobo'], ['name' => 'Cojedes'], ['name' => 'Delta Amacuro'],
            ['name' => 'Distrito Capital'], ['name' => 'Falcón'], ['name' => 'Guárico'],
            ['name' => 'Lara'], ['name' => 'Mérida'], ['name' => 'Miranda'],
            ['name' => 'Monagas'], ['name' => 'Nueva Esparta'], ['name' => 'Portuguesa'],
            ['name' => 'Sucre'], ['name' => 'Táchira'], ['name' => 'Trujillo'],
            ['name' => 'Vargas'], ['name' => 'Yaracuy'], ['name' => 'Zulia']
        ];

        foreach ($states as $state) {
            DB::table('states')->insert([
                'country_id' => $countryId,
                'name' => $state['name'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Puedes añadir otros países base aquí si lo deseas
        DB::table('countries')->insert([
            ['name' => 'Colombia', 'iso_code' => 'CO', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'México', 'iso_code' => 'MX', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Estados Unidos', 'iso_code' => 'US', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Panamá', 'iso_code' => 'PA', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::dropIfExists('states');
        Schema::dropIfExists('countries');
    }
};