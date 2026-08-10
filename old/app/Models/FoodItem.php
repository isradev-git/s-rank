<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo FoodItem — Alimento del catálogo
 *
 * Representa un alimento con sus macronutrientes por 100g o 100ml.
 * Los alimentos del sistema tienen is_verified=true y user_id=null.
 * Los usuarios pueden crear sus propios alimentos personalizados.
 *
 * Propiedades principales:
 * - name: nombre del alimento
 * - unit: unidad de referencia — 'g' (sólidos) o 'ml' (líquidos)
 * - calories_per_100g: calorías por cada 100 unidades (g o ml, según unit)
 * - protein_per_100g, carbs_per_100g, fat_per_100g: macros por 100 unidades
 * - category: categoría (carnes, lácteos, bebidas, etc.)
 */
class FoodItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'brand',
        'category',
        'unit',             // 'g' para sólidos, 'ml' para líquidos
        'calories_per_100g',
        'protein_per_100g',
        'carbs_per_100g',
        'fat_per_100g',
        'fiber_per_100g',
        'sugar_per_100g',
        'image_path',
        'is_verified',
        'user_id',
    ];

    protected $casts = [
        'calories_per_100g' => 'float',
        'protein_per_100g'  => 'float',
        'carbs_per_100g'    => 'float',
        'fat_per_100g'      => 'float',
        'fiber_per_100g'    => 'float',
        'sugar_per_100g'    => 'float',
        'is_verified'       => 'boolean',
    ];

    /**
     * Relación con el usuario que creó el alimento personalizado.
     * Si user_id es null, pertenece al sistema.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calcula los macros para una cantidad específica.
     *
     * La cantidad se interpreta en la misma unidad que `unit`:
     * - Si unit='g'  → la cantidad es en gramos
     * - Si unit='ml' → la cantidad es en mililitros
     *
     * El cálculo es idéntico en ambos casos porque los valores de la BD
     * siempre están referenciados a 100 unidades (100g o 100ml).
     *
     * Ejemplo: leche (unit='ml'), 250ml
     * $macros = $foodItem->macrosForQuantity(250);
     * // → ['calories' => 162.5, ...] (250ml × macros_por_100ml / 100)
     */
    public function macrosForQuantity(float $quantity): array
    {
        // El factor divide entre 100 porque los macros están expresados por 100 unidades
        $factor = $quantity / 100;

        return [
            'calories' => round($this->calories_per_100g * $factor, 2),
            'protein'  => round($this->protein_per_100g  * $factor, 2),
            'carbs'    => round($this->carbs_per_100g    * $factor, 2),
            'fat'      => round($this->fat_per_100g      * $factor, 2),
            'fiber'    => round($this->fiber_per_100g    * $factor, 2),
            'sugar'    => round($this->sugar_per_100g    * $factor, 2),
        ];
    }
}
