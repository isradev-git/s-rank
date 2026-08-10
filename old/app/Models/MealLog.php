<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Modelo MealLog — Registro de una comida del usuario
 *
 * Cada fila representa un alimento consumido en un momento específico.
 * Por ejemplo: "El usuario comió 150g de arroz en el almuerzo del 30/03/2026"
 *
 * Los macros se guardan como snapshot: si el alimento del catálogo cambia
 * después, el historial NO se altera (los datos quedan "congelados").
 *
 * meal_type puede ser:
 * - breakfast: Desayuno
 * - lunch: Almuerzo
 * - dinner: Cena
 * - snack: Snack / merienda
 */
class MealLog extends Model
{
    protected $fillable = [
        'uuid',
        'user_id',
        'date',
        'meal_type',
        'food_item_id',
        'custom_food_name',
        'quantity_grams',
        'calories',
        'protein',
        'carbs',
        'fat',
        'fiber',
        'sugar',
        'notes',
    ];

    protected $casts = [
        'date'           => 'date',
        'quantity_grams' => 'float',
        'calories'       => 'float',
        'protein'        => 'float',
        'carbs'          => 'float',
        'fat'            => 'float',
        'fiber'          => 'float',
        'sugar'          => 'float',
    ];

    /**
     * Al crear un MealLog automáticamente se le asigna un UUID.
     * Esto es útil para la API: el cliente usa el UUID, no el ID numérico.
     */
    protected static function booted(): void
    {
        static::creating(function (MealLog $log) {
            if (empty($log->uuid)) {
                $log->uuid = (string) Str::uuid();
            }
        });
    }

    /**
     * Relación con el usuario dueño del registro.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relación con el alimento del catálogo (puede ser null si es manual).
     */
    public function foodItem(): BelongsTo
    {
        return $this->belongsTo(FoodItem::class);
    }

    /**
     * Retorna el nombre del alimento, ya sea del catálogo o el ingresado manualmente.
     * Se usa en las vistas para mostrar siempre un nombre.
     */
    public function getFoodNameAttribute(): string
    {
        return $this->foodItem?->name ?? $this->custom_food_name ?? 'Alimento desconocido';
    }

    /**
     * Etiquetas legibles para el tipo de comida.
     * Útil para mostrar en la interfaz.
     */
    public static function mealTypeLabel(string $type): string
    {
        return match($type) {
            'breakfast' => 'Desayuno',
            'lunch'     => 'Almuerzo',
            'dinner'    => 'Cena',
            'snack'     => 'Snack',
            default     => $type,
        };
    }
}
