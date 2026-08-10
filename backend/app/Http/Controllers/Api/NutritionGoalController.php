<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\NutritionGoal;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para gestionar el objetivo nutricional del usuario.
 *
 * Endpoints:
 * - GET  /api/nutrition/goal  → ver objetivo actual (o calcular uno recomendado)
 * - PUT  /api/nutrition/goal  → actualizar objetivo
 */
class NutritionGoalController extends Controller
{
    /**
     * Retorna el objetivo nutricional del usuario autenticado.
     *
     * Si el usuario NO tiene objetivo configurado todavía,
     * calculamos uno recomendado con Harris-Benedict usando los
     * datos de su perfil y se lo devolvemos SIN guardarlo.
     * El frontend puede mostrar "valores sugeridos" y que el usuario los acepte.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        // Buscamos si ya tiene un objetivo guardado
        $goal = NutritionGoal::where('user_id', $user->id)->first();

        if ($goal) {
            return response()->json([
                'goal'      => $goal,
                'has_goal'  => true,  // Le decimos al frontend que ya tiene objetivo configurado
            ]);
        }

        // Si no tiene objetivo, calculamos valores sugeridos
        $recommendedCalories = NutritionGoal::calculateRecommended($user);

        // Distribuimos los macros con el ratio estándar: 30% proteína, 45% carbos, 25% grasa
        $suggested = [
            'daily_calories' => $recommendedCalories,
            'target_protein' => (int) round(($recommendedCalories * 0.30) / 4),  // 4 kcal por gramo de proteína
            'target_carbs'   => (int) round(($recommendedCalories * 0.45) / 4),  // 4 kcal por gramo de carbohidrato
            'target_fat'     => (int) round(($recommendedCalories * 0.25) / 9),  // 9 kcal por gramo de grasa
            'target_fiber'   => 25,
            'goal_type'      => 'maintain',
            'meals_per_day'  => 3,
        ];

        return response()->json([
            'goal'      => $suggested,
            'has_goal'  => false,  // Indica que son valores sugeridos, no guardados aún
        ]);
    }

    /**
     * Guarda o actualiza el objetivo nutricional del usuario.
     *
     * Usa updateOrCreate para que si ya existe uno lo actualice,
     * y si no existe lo cree. Así el usuario siempre tiene máximo 1 objetivo.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function update(Request $request): JsonResponse
    {
        // Validamos que los datos recibidos tengan sentido
        $validated = $request->validate([
            'daily_calories' => 'required|integer|min:500|max:10000',
            'target_protein' => 'required|integer|min:0|max:500',
            'target_carbs'   => 'required|integer|min:0|max:1000',
            'target_fat'     => 'required|integer|min:0|max:500',
            'target_fiber'   => 'nullable|integer|min:0|max:100',
            'goal_type'      => 'required|in:lose_weight,maintain,gain_muscle',
            'meals_per_day'  => 'nullable|integer|min:1|max:8',
        ]);

        // updateOrCreate: si existe para este user_id lo actualiza, si no lo crea
        $goal = NutritionGoal::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json([
            'message' => 'Objetivo nutricional guardado correctamente.',
            'goal'    => $goal,
        ]);
    }
}
