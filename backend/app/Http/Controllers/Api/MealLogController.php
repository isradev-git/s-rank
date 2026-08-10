<?php

namespace App\Http\Controllers\Api;

use App\Events\MealLogged;
use App\Http\Controllers\Controller;
use App\Models\FoodItem;
use App\Models\MealLog;
use App\Models\Workout;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador para registrar y consultar las comidas diarias.
 *
 * Endpoints:
 * - GET    /api/meals?date=2026-03-30          → comidas del día + resumen de macros
 * - POST   /api/meals                          → registrar una comida
 * - DELETE /api/meals/{uuid}                   → eliminar una entrada
 * - GET    /api/meals/history?days=7           → historial de calorías por día
 */
class MealLogController extends Controller
{
    /**
     * Devuelve todas las comidas del usuario para una fecha específica,
     * junto con el total de macros consumidos ese día.
     *
     * Si no se proporciona fecha, usa el día de hoy.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        // Parsemos la fecha (o usamos hoy si no se proporciona)
        $date = $request->string('date')->isNotEmpty()
            ? Carbon::parse($request->string('date'))
            : Carbon::today();

        $user = $request->user();

        // Obtenemos todas las comidas del día, agrupadas por tipo de comida
        $meals = MealLog::with('foodItem')
            ->where('user_id', $user->id)
            ->whereDate('date', $date)
            ->orderBy('created_at')
            ->get();

        // Calculamos los totales del día
        $totals = [
            'calories' => round($meals->sum('calories'), 1),
            'protein'  => round($meals->sum('protein'), 1),
            'carbs'    => round($meals->sum('carbs'), 1),
            'fat'      => round($meals->sum('fat'), 1),
            'fiber'    => round($meals->sum('fiber'), 1),
            'sugar'    => round($meals->sum('sugar'), 1),
        ];

        // Agrupamos por tipo de comida para la UI
        $grouped = $meals->groupBy('meal_type')->map(function ($items) {
            return [
                'items'    => $items->values(),
                'calories' => round($items->sum('calories'), 1),
            ];
        });

        // Calorías quemadas: si el workout tiene calories_burned se usa directamente
        // (ej. datos del reloj inteligente); si no, se calcula con MET × peso × horas.
        $metByMode = ['gym' => 5.5, 'calisthenics' => 5.0, 'home' => 4.5, 'swimming' => 7.0, 'pasos' => 3.5];
        $weight    = (float) ($user->weight ?? 70);
        $workoutCaloriesBurned = Workout::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->get()
            ->sum(function ($w) use ($metByMode, $weight) {
                if (!is_null($w->calories_burned)) {
                    return $w->calories_burned;
                }
                return ($metByMode[$w->mode] ?? 5.0) * $weight * ($w->duration_minutes / 60);
            });

        $activityWorkout = Workout::where('user_id', $user->id)
            ->where('mode', 'pasos')
            ->whereDate('date', $date)
            ->latest('date')
            ->first();

        $activitySteps = 0;
        if ($activityWorkout && !empty($activityWorkout->notes)) {
            $decoded = json_decode((string) $activityWorkout->notes, true);
            $activitySteps = (int) (($decoded['steps'] ?? 0));
        }

        // Ojo: workoutCaloriesBurned ya incluye el registro de modo "pasos" si existe.
        $caloriesBurned = $workoutCaloriesBurned;

        return response()->json([
            'date'            => $date->format('Y-m-d'),
            'meals'           => $grouped,
            'totals'          => $totals,
            'count'           => $meals->count(),
            'calories_burned' => (int) round($caloriesBurned),
            'activity'        => [
                'steps' => $activitySteps,
                'manual_calories_burned' => (int) round((float) ($activityWorkout->calories_burned ?? 0)),
                'workout_calories_burned' => (int) round($workoutCaloriesBurned),
            ],
        ]);
    }

    /**
     * Registra una comida consumida.
     *
     * Puede recibir:
     * A) food_item_id + quantity_grams → calcula los macros automáticamente desde el catálogo
     * B) custom_food_name + calories (y opcionalmente macros) → entrada manual sin catálogo
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date'             => 'nullable|date',
            'meal_type'        => 'required|in:breakfast,lunch,dinner,snack',
            'food_item_id'     => 'nullable|exists:food_items,id',
            'custom_food_name' => 'nullable|string|max:200',
            'quantity_grams'   => 'nullable|numeric|min:1|max:5000',
            'calories'         => 'nullable|numeric|min:0',  // Solo para entrada manual
            'protein'          => 'nullable|numeric|min:0',
            'carbs'            => 'nullable|numeric|min:0',
            'fat'              => 'nullable|numeric|min:0',
            'fiber'            => 'nullable|numeric|min:0',
            'sugar'            => 'nullable|numeric|min:0',
            'notes'            => 'nullable|string|max:500',
        ]);

        // Validamos que tenga al menos una forma de identificar el alimento
        if (empty($validated['food_item_id']) && empty($validated['custom_food_name'])) {
            return response()->json([
                'message' => 'Debes proporcionar un alimento del catálogo (food_item_id) o un nombre personalizado (custom_food_name).',
            ], 422);
        }

        // Calculamos los macros si se seleccionó un alimento del catálogo
        $macros = [];
        if (!empty($validated['food_item_id'])) {
            $food   = FoodItem::findOrFail($validated['food_item_id']);
            $grams  = $validated['quantity_grams'] ?? 100;
            $macros = $food->macrosForQuantity($grams);
        } else {
            // Entrada manual: usamos los macros que envió el usuario (o 0 si no los envió)
            $macros = [
                'calories' => $validated['calories'] ?? 0,
                'protein'  => $validated['protein']  ?? 0,
                'carbs'    => $validated['carbs']     ?? 0,
                'fat'      => $validated['fat']       ?? 0,
                'fiber'    => $validated['fiber']     ?? 0,
                'sugar'    => $validated['sugar']     ?? 0,
            ];
        }

        $log = MealLog::create([
            'user_id'          => $request->user()->id,
            'date'             => $validated['date'] ?? now()->toDateString(),
            'meal_type'        => $validated['meal_type'],
            'food_item_id'     => $validated['food_item_id'] ?? null,
            'custom_food_name' => $validated['custom_food_name'] ?? null,
            'quantity_grams'   => $validated['quantity_grams'] ?? 100,
            'notes'            => $validated['notes'] ?? null,
            ...$macros,  // Spread del array de macros calculados
        ]);

        $event = new MealLogged($request->user(), CarbonImmutable::parse($log->date));
        event($event);

        return response()->json([
            'message'  => 'Comida registrada correctamente.',
            'meal_log' => $log->load('foodItem'),
            'system'   => $event->rewards,
        ], 201);
    }

    /**
     * Elimina una entrada de comida del usuario.
     * Se identifica por UUID para no exponer el ID numérico.
     *
     * @param Request $request
     * @param string  $uuid
     * @return JsonResponse
     */
    public function destroy(Request $request, string $uuid): JsonResponse
    {
        $log = MealLog::where('uuid', $uuid)
            ->where('user_id', $request->user()->id)
            ->first();

        // Borrado idempotente: si ya no existe, respondemos OK para evitar errores
        // por doble click o estados desfasados en cliente.
        if (! $log) {
            return response()->json(['message' => 'Entrada ya eliminada.']);
        }

        $log->delete();

        return response()->json(['message' => 'Entrada eliminada correctamente.']);
    }

    /**
     * Historial de calorías de los últimos N días.
     * Útil para mostrar una gráfica de evolución calórica en el tiempo.
     *
     * Ejemplo de respuesta:
     * [
     *   {"date": "2026-03-28", "calories": 1850, "protein": 145},
     *   {"date": "2026-03-29", "calories": 2100, "protein": 160},
     *   ...
     * ]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function history(Request $request): JsonResponse
    {
        // Rango por fechas explícito (date_from/date_to) o por nº de días.
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $endDate   = $request->filled('date_to') ? Carbon::parse($request->query('date_to')) : Carbon::today();
            $startDate = $request->filled('date_from') ? Carbon::parse($request->query('date_from')) : $endDate->copy()->subDays(29);
            if ($startDate->gt($endDate)) {
                [$startDate, $endDate] = [$endDate, $startDate];
            }
        } else {
            $days      = min($request->integer('days', 7), 90);
            $endDate   = Carbon::today();
            $startDate = $endDate->copy()->subDays($days - 1);
        }

        // Agrupamos por día y sumamos los macros (incluyendo micronutrientes: fibra y azúcar)
        $history = MealLog::where('user_id', $request->user()->id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw('date, SUM(calories) as calories, SUM(protein) as protein, SUM(carbs) as carbs, SUM(fat) as fat, SUM(fiber) as fiber, SUM(sugar) as sugar')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                return [
                    'date'     => $row->date,
                    'calories' => round($row->calories, 1),
                    'protein'  => round($row->protein, 1),
                    'carbs'    => round($row->carbs, 1),
                    'fat'      => round($row->fat, 1),
                    'fiber'    => round($row->fiber ?? 0, 1),
                    'sugar'    => round($row->sugar ?? 0, 1),
                ];
            });

        return response()->json([
            'history'    => $history,
            'days'       => $startDate->diffInDays($endDate) + 1,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date'   => $endDate->format('Y-m-d'),
        ]);
    }
}
