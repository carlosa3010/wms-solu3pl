<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class USStatesSeeder extends Seeder
{
    /**
     * Ejecuta las semillas de la base de datos para cargar los estados de USA.
     */
    public function run(): void
    {
        // 1. Obtener o crear el registro de "Estados Unidos" en la tabla de países
        $country = DB::table('countries')->where('name', 'Estados Unidos')->first();

        if (!$country) {
            $countryId = DB::table('countries')->insertGetId([
                'name' => 'Estados Unidos',
                'iso_code' => 'US',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $countryId = $country->id;
        }

        // 2. Lista completa de los 50 estados de EE.UU.
        $states = [
            ['name' => 'Alabama'], ['name' => 'Alaska'], ['name' => 'Arizona'], ['name' => 'Arkansas'],
            ['name' => 'California'], ['name' => 'Colorado'], ['name' => 'Connecticut'], ['name' => 'Delaware'],
            ['name' => 'Florida'], ['name' => 'Georgia'], ['name' => 'Hawaii'], ['name' => 'Idaho'],
            ['name' => 'Illinois'], ['name' => 'Indiana'], ['name' => 'Iowa'], ['name' => 'Kansas'],
            ['name' => 'Kentucky'], ['name' => 'Louisiana'], ['name' => 'Maine'], ['name' => 'Maryland'],
            ['name' => 'Massachusetts'], ['name' => 'Michigan'], ['name' => 'Minnesota'], ['name' => 'Mississippi'],
            ['name' => 'Missouri'], ['name' => 'Montana'], ['name' => 'Nebraska'], ['name' => 'Nevada'],
            ['name' => 'New Hampshire'], ['name' => 'New Jersey'], ['name' => 'New Mexico'], ['name' => 'New York'],
            ['name' => 'North Carolina'], ['name' => 'North Dakota'], ['name' => 'Ohio'], ['name' => 'Oklahoma'],
            ['name' => 'Oregon'], ['name' => 'Pennsylvania'], ['name' => 'Rhode Island'], ['name' => 'South Carolina'],
            ['name' => 'South Dakota'], ['name' => 'Tennessee'], ['name' => 'Texas'], ['name' => 'Utah'],
            ['name' => 'Vermont'], ['name' => 'Virginia'], ['name' => 'Washington'], ['name' => 'West Virginia'],
            ['name' => 'Wisconsin'], ['name' => 'Wyoming']
        ];

        // 3. Inserción de estados evitando duplicados
        foreach ($states as $state) {
            $exists = DB::table('states')
                ->where('country_id', $countryId)
                ->where('name', $state['name'])
                ->exists();

            if (!$exists) {
                DB::table('states')->insert([
                    'country_id' => $countryId,
                    'name' => $state['name'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}