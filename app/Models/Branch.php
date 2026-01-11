<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
    'name',
    'code', // <--- AGREGA ESTO
    'address',
    'city',
    'state',
    'country',
    'phone',
    'email',
    'manager_name',
    'covered_countries',
    'covered_states',
    'is_active',
    'can_export'
];

    protected $casts = [
        'covered_countries' => 'array', // Importante: Castear a array
        'covered_states'    => 'array', // Importante: Castear a array
        'is_active'         => 'boolean',
        'can_export'        => 'boolean',
    ];

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    /**
     * Verifica si la sucursal tiene cobertura.
     * Basado en las listas independientes de paises y estados.
     */
    public function hasCoverage($targetCountry, $targetState = null)
    {
        // 1. Validar Paises (Array plano en BD)
        $countries = $this->covered_countries ?? [];
        if (empty($countries)) return false;

        $targetCountry = $this->normalizeString($targetCountry);
        
        // Normalizamos la lista de países de la BD
        $normalizedCountries = array_map([$this, 'normalizeString'], $countries);

        if (!in_array($targetCountry, $normalizedCountries)) {
            return false;
        }

        // 2. Validar Estados (si aplica)
        // Si no hay estados definidos, asumimos cobertura nacional en ese país
        $states = $this->covered_states ?? [];
        if (empty($states)) {
            return true;
        }

        if ($targetState) {
            $targetState = $this->normalizeString($targetState);
            $normalizedStates = array_map([$this, 'normalizeString'], $states);
            
            return in_array($targetState, $normalizedStates);
        }

        return true;
    }

    private function normalizeString($string)
    {
        if (is_null($string)) return '';
        return strtoupper(trim($string));
    }
}