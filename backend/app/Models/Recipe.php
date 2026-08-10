<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Modelo Recipe — Receta de cocina
 *
 * Las recetas del sistema (is_system=true) se cargan via RecipesTableSeeder.
 * Los usuarios también pueden crear sus propias recetas personalizadas.
 *
 * Los macros son POR PORCIÓN (no por 100g), porque la receta se sirve
 * como una unidad completa. Si la receta rinde 4 porciones, cada
 * porción tiene calories_per_serving / 4 calorías... bueno, en realidad
 * calories_per_serving ya está calculado por porción individual.
 *
 * ingredients se guarda como JSON con este formato:
 * [
 *   {"name": "Pechuga de pollo", "quantity": "200g"},
 *   {"name": "Arroz integral", "quantity": "80g"},
 *   {"name": "Aceite de oliva", "quantity": "1 cucharada"},
 * ]
 */
class Recipe extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'category',
        'image_url',
        'image_path',
        'calories_per_serving',
        'protein_per_serving',
        'carbs_per_serving',
        'fat_per_serving',
        'fiber_per_serving',
        'servings',
        'prep_time_min',
        'cook_time_min',
        'ingredients',
        'instructions',
        'difficulty',
        'is_system',
        'user_id',
    ];

    protected $casts = [
        'calories_per_serving' => 'integer',
        'protein_per_serving'  => 'float',
        'carbs_per_serving'    => 'float',
        'fat_per_serving'      => 'float',
        'fiber_per_serving'    => 'float',
        'servings'             => 'integer',
        'prep_time_min'        => 'integer',
        'cook_time_min'        => 'integer',
        'ingredients'          => 'array',  // Laravel convierte el JSON a array automáticamente
        'is_system'            => 'boolean',
    ];

    /**
     * Relación con el usuario que creó la receta (null si es del sistema).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Tiempo total de preparación + cocción.
     */
    public function getTotalTimeAttribute(): int
    {
        return $this->prep_time_min + $this->cook_time_min;
    }

    /**
     * Scope (filtro) para recetas dentro de un rango de calorías.
     * Se usa en la vista de recetas recomendadas.
     *
     * Ejemplo: Recipe::withinCalories(400, 600)->get()
     * → Recetas de entre 400 y 600 calorías por porción
     */
    public function scopeWithinCalories($query, int $min, int $max)
    {
        return $query->whereBetween('calories_per_serving', [$min, $max]);
    }

    /**
     * Scope para filtrar por categoría.
     * Ejemplo: Recipe::ofCategory('desayuno')->get()
     */
    public function scopeOfCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
