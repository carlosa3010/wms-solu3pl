<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'address',
        'city',
        'state',
        'country',
        'phone',
        'email',
        'manager_name',
        'coverage_area', // JSON: {"United States": ["Florida", "Texas"], "Mexico": []}
        'is_active'
    ];

    protected $casts = [
        'coverage_area' => 'array',
        'is_active' => 'boolean',
    ];

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    public function users()
    {
        return $this->hasMany(User::class); // Si tienes relación usuarios-sucursal
    }

    /**
     * Verifica si la sucursal tiene cobertura para un país y estado dados.
     * Normaliza los textos para evitar errores por mayúsculas o espacios.
     */
    public function hasCoverage($targetCountry, $targetState = null)
    {
        // 1. Si no hay configuración, asumimos que NO hay cobertura (o cambia a true si quieres cobertura global por defecto)
        if (empty($this->coverage_area) || !is_array($this->coverage_area)) {
            return false;
        }

        $targetCountry = $this->normalizeString($targetCountry);
        $targetState = $this->normalizeString($targetState);

        foreach ($this->coverage_area as $country => $states) {
            // 2. Compara el país
            if ($this->normalizeString($country) === $targetCountry) {
                
                // Si el array de estados está vacío o contiene '*', cubre todo el país
                if (empty($states) || (is_array($states) && in_array('*', $states))) {
                    return true;
                }

                // 3. Si hay estados definidos, busca el estado específico
                if ($targetState && is_array($states)) {
                    // Normalizamos todos los estados configurados para comparar
                    $normalizedStates = array_map([$this, 'normalizeString'], $states);
                    if (in_array($targetState, $normalizedStates)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Helper para limpiar cadenas (trim + uppercase)
     */
    private function normalizeString($string)
    {
        if (is_null($string)) return '';
        // Elimina acentos básicos si es necesario, pero startoupper suele bastar para inglés/español básico
        return strtoupper(trim($string));
    }
}