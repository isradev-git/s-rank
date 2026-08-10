<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseSet;
use App\Models\MealLog;
use App\Models\NutritionGoal;
use App\Models\SupplementLog;
use App\Models\WaterLog;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    // MET estimado por modo de entrenamiento
    private const MET_BY_MODE = [
        'gym'          => 5.5,
        'calisthenics' => 5.0,
        'home'         => 4.5,
        'swimming'     => 7.0,
    ];

    public function stats(Request $request)
    {
        $user = $request->user();

        $totals = Workout::where('user_id', $user->id)
            ->where('mode', '!=', 'pasos')
            ->selectRaw('count(*) as count, sum(duration_minutes) as minutes')
            ->first();

        // Racha real: días consecutivos con al menos un entrenamiento,
        // contando hacia atrás desde hoy (o ayer si hoy no se entrenó).
        $dates = Workout::where('user_id', $user->id)
            ->where('mode', '!=', 'pasos')
            ->selectRaw('DATE(date) as d')
            ->distinct()
            ->orderByRaw('d DESC')
            ->pluck('d')
            ->map(fn($d) => \Carbon\Carbon::parse($d)->toDateString())
            ->all();

        $streak = 0;
        $today     = now()->toDateString();
        $yesterday = now()->subDay()->toDateString();

        if (in_array($today, $dates) || in_array($yesterday, $dates)) {
            $check = in_array($today, $dates) ? now() : now()->subDay();
            while (in_array($check->toDateString(), $dates)) {
                $streak++;
                $check->subDay();
            }
        }

        $monthlyCount = Workout::where('user_id', $user->id)
            ->where('mode', '!=', 'pasos')
            ->where('date', '>=', now()->subDays(30))
            ->count();

        // TDEE estimado (Mifflin-St Jeor promedio hombre/mujer)
        $tdee = null;
        if ($user->weight && $user->height && $user->age) {
            $male   = (10 * $user->weight) + (6.25 * $user->height) - (5 * $user->age) + 5;
            $female = (10 * $user->weight) + (6.25 * $user->height) - (5 * $user->age) - 161;
            $bmr    = ($male + $female) / 2;
            $tdee   = (int) round($bmr * 1.55); // Moderadamente activo
        }

        // Calorías quemadas hoy estimadas por modo y duración
        $weight = (float) ($user->weight ?? 70);
        $caloriesBurnedToday = Workout::where('user_id', $user->id)
            ->where('mode', '!=', 'pasos')
            ->whereDate('date', now()->toDateString())
            ->get()
            ->sum(fn($w) => (self::MET_BY_MODE[$w->mode] ?? 5.0) * $weight * ($w->duration_minutes / 60));

        return response()->json([
            'total_workouts'       => (int) ($totals->count ?? 0),
            'total_minutes'        => (int) ($totals->minutes ?? 0),
            'weekly_streak'        => (int) $streak,
            'monthly_count'        => (int) $monthlyCount,
            'weekly_goal'          => (int) $user->weekly_goal,
            'tdee'                 => $tdee,
            'calories_burned_today'=> (int) round($caloriesBurnedToday),
        ]);
    }

    /**
     * Devuelve la actividad por día del año para el heatmap de contribución.
     * GET /api/stats/heatmap?year=2026
     */
    public function heatmap(Request $request)
    {
        $user = $request->user();
        $year = max(2020, min(2030, $request->integer('year', now()->year)));

        $rows = $this->baseWorkoutQuery($request)
            ->where('user_id', $user->id)
            ->whereYear('date', $year)
            ->selectRaw('DATE(date) as day, COUNT(*) as count, SUM(duration_minutes) as minutes')
            ->groupBy('day')
            ->get();

        $data = $rows->keyBy('day')->map(fn($r) => [
            'count'   => (int) $r->count,
            'minutes' => (int) $r->minutes,
        ]);

        return response()->json(['year' => $year, 'data' => $data]);
    }

    public function calendar(Request $request)
    {
        $period = $request->query('period', 'month');
        if (!in_array($period, ['week', 'month', 'year'], true)) {
            return response()->json(['message' => 'Periodo inválido.'], 422);
        }

        $anchor = $this->parseDate($request->query('date'));

        if ($period === 'week') {
            $periodStart = $anchor->copy()->startOfWeek(Carbon::MONDAY);
            $periodEnd = $anchor->copy()->endOfWeek(Carbon::SUNDAY);
            $gridStart = $periodStart->copy();
            $gridEnd = $periodEnd->copy();
            $label = $periodStart->locale('es')->isoFormat('D MMM') . ' - ' . $periodEnd->locale('es')->isoFormat('D MMM YYYY');
        } elseif ($period === 'month') {
            $periodStart = $anchor->copy()->startOfMonth();
            $periodEnd = $anchor->copy()->endOfMonth();
            $gridStart = $periodStart->copy()->startOfWeek(Carbon::MONDAY);
            $gridEnd = $periodEnd->copy()->endOfWeek(Carbon::SUNDAY);
            $label = $periodStart->locale('es')->isoFormat('MMMM YYYY');
        } else {
            $periodStart = $anchor->copy()->startOfYear();
            $periodEnd = $anchor->copy()->endOfYear();
            $gridStart = $periodStart->copy()->startOfWeek(Carbon::MONDAY);
            $gridEnd = $periodEnd->copy()->endOfWeek(Carbon::SUNDAY);
            $label = $periodStart->locale('es')->isoFormat('YYYY');
        }

        $rows = $this->baseWorkoutQuery($request)
            ->whereBetween('date', [$gridStart->toDateString(), $gridEnd->toDateString()])
            ->selectRaw('DATE(date) as day, COUNT(*) as count, SUM(duration_minutes) as minutes')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $days = [];
        $cursor = $gridStart->copy();
        while ($cursor->lte($gridEnd)) {
            $dateKey = $cursor->toDateString();
            $entry = $rows->get($dateKey);

            $days[] = [
                'date' => $dateKey,
                'day_number' => $cursor->day,
                'day_name' => ucfirst($cursor->locale('es')->isoFormat('dd')),
                'month_name' => ucfirst($cursor->locale('es')->isoFormat('MMM')),
                'in_period' => $cursor->betweenIncluded($periodStart, $periodEnd),
                'is_today' => $cursor->isToday(),
                'workouts_count' => (int) ($entry->count ?? 0),
                'minutes' => (int) ($entry->minutes ?? 0),
            ];

            $cursor->addDay();
        }

        $summary = collect($days)
            ->filter(fn($day) => $day['in_period'])
            ->reduce(function (array $carry, array $day) {
                $carry['total_workouts'] += $day['workouts_count'];
                $carry['total_minutes'] += $day['minutes'];
                $carry['active_days'] += $day['workouts_count'] > 0 ? 1 : 0;

                return $carry;
            }, [
                'total_workouts' => 0,
                'total_minutes' => 0,
                'active_days' => 0,
            ]);

        $summary['average_duration'] = $summary['total_workouts'] > 0
            ? (int) round($summary['total_minutes'] / $summary['total_workouts'])
            : 0;

        return response()->json([
            'period' => $period,
            'label' => $label,
            'anchor_date' => $anchor->toDateString(),
            'start_date' => $periodStart->toDateString(),
            'end_date' => $periodEnd->toDateString(),
            'days' => $days,
            'summary' => $summary,
        ]);
    }

    public function reports(Request $request)
    {
        [$startDate, $endDate, $label] = $this->resolveReportRange($request);

        $nutritionGoal = NutritionGoal::query()
            ->where('user_id', $request->user()->id)
            ->first();

        $mealLogs = MealLog::query()
            ->where('user_id', $request->user()->id)
            ->when($startDate, fn($query) => $query->whereDate('date', '>=', $startDate->toDateString()))
            ->when($endDate, fn($query) => $query->whereDate('date', '<=', $endDate->toDateString()))
            ->get(['date', 'meal_type', 'calories', 'protein', 'carbs', 'fat', 'fiber', 'sugar']);

        $waterByDay = WaterLog::query()
            ->where('user_id', $request->user()->id)
            ->when($startDate, fn($query) => $query->whereDate('date', '>=', $startDate->toDateString()))
            ->when($endDate, fn($query) => $query->whereDate('date', '<=', $endDate->toDateString()))
            ->selectRaw('DATE(date) as day, SUM(amount_ml) as total_ml')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $supplementsByDay = SupplementLog::query()
            ->where('user_id', $request->user()->id)
            ->where('taken', true)
            ->when($startDate, fn($query) => $query->whereDate('date', '>=', $startDate->toDateString()))
            ->when($endDate, fn($query) => $query->whereDate('date', '<=', $endDate->toDateString()))
            ->selectRaw('DATE(date) as day, COUNT(*) as taken_count')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $nutrition = $this->buildNutritionSummary(
            $request,
            $mealLogs,
            $waterByDay,
            $supplementsByDay,
            $nutritionGoal,
            $startDate,
            $endDate,
        );

        $workouts = $this->baseWorkoutQuery($request)
            ->when($startDate, fn($query) => $query->whereDate('date', '>=', $startDate->toDateString()))
            ->when($endDate, fn($query) => $query->whereDate('date', '<=', $endDate->toDateString()))
            ->orderBy('date')
            ->get(['id', 'date', 'mode', 'duration_minutes', 'notes']);

        $summary = $this->buildSummaryFromWorkouts($workouts);
        $comparison = $this->buildComparison($request, $startDate, $endDate, $summary, $nutritionGoal, $nutrition);

        if ($workouts->isEmpty()) {
            return response()->json([
                'range_label' => $label,
                'date_from' => $startDate?->toDateString(),
                'date_to' => $endDate?->toDateString(),
                'summary' => $summary,
                'nutrition' => $nutrition,
                'comparison' => $comparison,
                'by_mode' => [],
                'by_weekday' => [
                    ['label' => 'Lun', 'workouts' => 0],
                    ['label' => 'Mar', 'workouts' => 0],
                    ['label' => 'Mié', 'workouts' => 0],
                    ['label' => 'Jue', 'workouts' => 0],
                    ['label' => 'Vie', 'workouts' => 0],
                    ['label' => 'Sáb', 'workouts' => 0],
                    ['label' => 'Dom', 'workouts' => 0],
                ],
                'by_month' => [],
                'top_exercises' => [],
                'recent_workouts' => [],
            ]);
        }

        $workoutIds = $workouts->pluck('id');
        $sets = ExerciseSet::whereIn('workout_id', $workoutIds)
            ->get(['workout_id', 'name', 'sets', 'reps', 'weight_kg']);

        $summary['total_sets'] = (int) $sets->sum(fn($set) => (int) ($set->sets ?? 0));
        $summary['total_volume'] = (float) round($sets->sum(function ($set) {
            return (float) ($set->weight_kg ?? 0) * (int) ($set->reps ?? 0) * (int) ($set->sets ?? 0);
        }), 2);

        $modeTotals = $workouts->groupBy('mode')->map(function ($items, $mode) use ($summary) {
            $count = $items->count();

            return [
                'mode' => $mode,
                'label' => $this->modeLabel($mode),
                'workouts' => $count,
                'minutes' => (int) $items->sum('duration_minutes'),
                'percentage' => $summary['total_workouts'] > 0
                    ? (float) round(($count / $summary['total_workouts']) * 100, 1)
                    : 0,
            ];
        })->values()->sortByDesc('workouts')->values();

        $weekdayLabels = [1 => 'Lun', 2 => 'Mar', 3 => 'Mié', 4 => 'Jue', 5 => 'Vie', 6 => 'Sáb', 7 => 'Dom'];
        $weekdayTotals = collect($weekdayLabels)->map(function ($label, $weekday) use ($workouts) {
            $items = $workouts->filter(fn($workout) => Carbon::parse($workout->date)->dayOfWeekIso === $weekday);

            return [
                'label' => $label,
                'workouts' => $items->count(),
                'minutes' => (int) $items->sum('duration_minutes'),
            ];
        })->values();

        $monthTotals = $workouts->groupBy(fn($workout) => Carbon::parse($workout->date)->format('Y-m'))
            ->map(function ($items, $month) {
                $date = Carbon::createFromFormat('Y-m', $month);

                return [
                    'month' => $month,
                    'label' => ucfirst($date->locale('es')->isoFormat('MMM YYYY')),
                    'workouts' => $items->count(),
                    'minutes' => (int) $items->sum('duration_minutes'),
                ];
            })
            ->sortBy('month')
            ->values();

        $topExercises = $sets->groupBy('name')->map(function ($items, $name) {
            return [
                'name' => $name,
                'sessions' => $items->pluck('workout_id')->unique()->count(),
                'total_sets' => (int) $items->sum(fn($item) => (int) ($item->sets ?? 0)),
                'total_reps' => (int) $items->sum(fn($item) => (int) ($item->reps ?? 0) * (int) ($item->sets ?? 0)),
                'max_weight' => (float) $items->max(fn($item) => (float) ($item->weight_kg ?? 0)),
                'total_volume' => (float) round($items->sum(fn($item) => (float) ($item->weight_kg ?? 0) * (int) ($item->reps ?? 0) * (int) ($item->sets ?? 0)), 2),
            ];
        })->sortByDesc('sessions')->take(10)->values();

        $recentWorkouts = $workouts->sortByDesc('date')->take(5)->values()->map(fn($workout) => [
            'id' => $workout->id,
            'date' => Carbon::parse($workout->date)->toDateString(),
            'mode' => $workout->mode,
            'mode_label' => $this->modeLabel($workout->mode),
            'duration_minutes' => (int) $workout->duration_minutes,
            'notes' => $workout->notes,
        ]);

        return response()->json([
            'range_label' => $label,
            'date_from' => $startDate?->toDateString(),
            'date_to' => $endDate?->toDateString(),
            'summary' => $summary,
            'nutrition' => $nutrition,
            'comparison' => $comparison,
            'by_mode' => $modeTotals,
            'by_weekday' => $weekdayTotals,
            'by_month' => $monthTotals,
            'top_exercises' => $topExercises,
            'recent_workouts' => $recentWorkouts,
        ]);
    }

    private function baseWorkoutQuery(Request $request)
    {
        return Workout::query()
            ->where('user_id', $request->user()->id)
            ->where('mode', '!=', 'pasos')
            ->when($request->filled('mode'), fn($query) => $query->where('mode', $request->query('mode')));
    }

    private function parseDate(?string $value): Carbon
    {
        try {
            return $value ? Carbon::parse($value)->startOfDay() : now()->startOfDay();
        } catch (\Throwable $e) {
            abort(response()->json(['message' => 'Fecha inválida.'], 422));
        }
    }

    private function resolveReportRange(Request $request): array
    {
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $start = $request->filled('date_from') ? $this->parseDate($request->query('date_from')) : null;
            $end = $request->filled('date_to') ? $this->parseDate($request->query('date_to')) : now()->startOfDay();

            if ($start && $end && $start->gt($end)) {
                [$start, $end] = [$end, $start];
            }

            $label = ($start?->locale('es')->isoFormat('D MMM YYYY') ?? 'Inicio')
                . ' - '
                . ($end?->locale('es')->isoFormat('D MMM YYYY') ?? 'Hoy');

            return [$start, $end, $label];
        }

        $range = $request->query('range', '30d');
        $end = now()->startOfDay();

        return match ($range) {
            '7d' => [$end->copy()->subDays(6), $end, 'Últimos 7 días'],
            '90d' => [$end->copy()->subDays(89), $end, 'Últimos 90 días'],
            '365d' => [$end->copy()->subDays(364), $end, 'Últimos 12 meses'],
            'all' => [null, null, 'Todo el historial'],
            default => [$end->copy()->subDays(29), $end, 'Últimos 30 días'],
        };
    }

    private function modeLabel(string $mode): string
    {
        return match ($mode) {
            'gym' => 'Gimnasio',
            'home' => 'Casa',
            'calisthenics' => 'Calistenia',
            'swimming' => 'Natación',
            default => ucfirst($mode),
        };
    }

    private function buildSummaryFromWorkouts($workouts): array
    {
        $totalWorkouts = $workouts->count();
        $totalMinutes = (int) $workouts->sum('duration_minutes');

        return [
            'total_workouts' => $totalWorkouts,
            'total_minutes' => $totalMinutes,
            'active_days' => $workouts->pluck('date')->map(fn($date) => Carbon::parse($date)->toDateString())->unique()->count(),
            'average_duration' => $totalWorkouts > 0 ? (int) round($totalMinutes / $totalWorkouts) : 0,
            'total_sets' => 0,
            'total_volume' => 0,
        ];
    }

    private function buildComparison(
        Request $request,
        ?Carbon $startDate,
        ?Carbon $endDate,
        array $summary,
        ?NutritionGoal $nutritionGoal,
        array $nutrition,
    ): ?array
    {
        if (!$startDate || !$endDate) {
            return null;
        }

        $days = $startDate->diffInDays($endDate) + 1;
        $previousEnd = $startDate->copy()->subDay();
        $previousStart = $previousEnd->copy()->subDays($days - 1);

        $previousWorkouts = $this->baseWorkoutQuery($request)
            ->whereDate('date', '>=', $previousStart->toDateString())
            ->whereDate('date', '<=', $previousEnd->toDateString())
            ->get(['date', 'duration_minutes']);

        $previousSummary = $this->buildSummaryFromWorkouts($previousWorkouts);

        $previousMealLogs = MealLog::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('date', '>=', $previousStart->toDateString())
            ->whereDate('date', '<=', $previousEnd->toDateString())
            ->get(['date', 'meal_type', 'calories', 'protein', 'carbs', 'fat', 'fiber', 'sugar']);

        $previousWaterByDay = WaterLog::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('date', '>=', $previousStart->toDateString())
            ->whereDate('date', '<=', $previousEnd->toDateString())
            ->selectRaw('DATE(date) as day, SUM(amount_ml) as total_ml')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $previousSupplementsByDay = SupplementLog::query()
            ->where('user_id', $request->user()->id)
            ->where('taken', true)
            ->whereDate('date', '>=', $previousStart->toDateString())
            ->whereDate('date', '<=', $previousEnd->toDateString())
            ->selectRaw('DATE(date) as day, COUNT(*) as taken_count')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $previousNutrition = $this->buildNutritionSummary(
            $request,
            $previousMealLogs,
            $previousWaterByDay,
            $previousSupplementsByDay,
            $nutritionGoal,
            $previousStart,
            $previousEnd,
        );

        $currentNutritionSummary = [
            'total_calories' => (float) ($nutrition['summary']['total_calories'] ?? 0),
            'average_calories' => (float) ($nutrition['summary']['average_calories'] ?? 0),
            'total_protein' => (float) ($nutrition['summary']['total_protein'] ?? 0),
            'water_total_ml' => (int) ($nutrition['hydration']['total_ml'] ?? 0),
            'supplement_taken_count' => (int) ($nutrition['supplements']['taken_count'] ?? 0),
        ];

        return [
            'label' => 'vs periodo anterior',
            'previous_range' => [
                'date_from' => $previousStart->toDateString(),
                'date_to' => $previousEnd->toDateString(),
            ],
            'previous_summary' => $previousSummary,
            'previous_nutrition' => [
                'total_calories' => $previousNutrition['summary']['total_calories'],
                'average_calories' => $previousNutrition['summary']['average_calories'],
                'total_protein' => $previousNutrition['summary']['total_protein'],
                'water_total_ml' => $previousNutrition['hydration']['total_ml'],
                'supplement_taken_count' => $previousNutrition['supplements']['taken_count'],
            ],
            'delta' => [
                'total_workouts' => $summary['total_workouts'] - $previousSummary['total_workouts'],
                'total_minutes' => $summary['total_minutes'] - $previousSummary['total_minutes'],
                'active_days' => $summary['active_days'] - $previousSummary['active_days'],
                'average_duration' => $summary['average_duration'] - $previousSummary['average_duration'],
                'nutrition_total_calories' => $currentNutritionSummary['total_calories'] - $previousNutrition['summary']['total_calories'],
                'nutrition_average_calories' => $currentNutritionSummary['average_calories'] - $previousNutrition['summary']['average_calories'],
                'nutrition_total_protein' => $currentNutritionSummary['total_protein'] - $previousNutrition['summary']['total_protein'],
                'nutrition_water_total_ml' => $currentNutritionSummary['water_total_ml'] - $previousNutrition['hydration']['total_ml'],
                'nutrition_supplement_taken_count' => $currentNutritionSummary['supplement_taken_count'] - $previousNutrition['supplements']['taken_count'],
            ],
            'change_pct' => [
                'total_workouts' => $this->safePercentChange($previousSummary['total_workouts'], $summary['total_workouts']),
                'total_minutes' => $this->safePercentChange($previousSummary['total_minutes'], $summary['total_minutes']),
                'active_days' => $this->safePercentChange($previousSummary['active_days'], $summary['active_days']),
                'average_duration' => $this->safePercentChange($previousSummary['average_duration'], $summary['average_duration']),
                'nutrition_total_calories' => $this->safePercentChange($previousNutrition['summary']['total_calories'], $currentNutritionSummary['total_calories']),
                'nutrition_average_calories' => $this->safePercentChange($previousNutrition['summary']['average_calories'], $currentNutritionSummary['average_calories']),
                'nutrition_total_protein' => $this->safePercentChange($previousNutrition['summary']['total_protein'], $currentNutritionSummary['total_protein']),
                'nutrition_water_total_ml' => $this->safePercentChange($previousNutrition['hydration']['total_ml'], $currentNutritionSummary['water_total_ml']),
                'nutrition_supplement_taken_count' => $this->safePercentChange($previousNutrition['supplements']['taken_count'], $currentNutritionSummary['supplement_taken_count']),
            ],
        ];
    }

    private function buildNutritionSummary(
        Request $request,
        $mealLogs,
        $waterByDay,
        $supplementsByDay,
        ?NutritionGoal $nutritionGoal,
        ?Carbon $startDate,
        ?Carbon $endDate,
    ): array {
        $periodDays = ($startDate && $endDate)
            ? ($startDate->diffInDays($endDate) + 1)
            : max(1, $mealLogs->pluck('date')->unique()->count());

        $mealDays = $mealLogs->pluck('date')->map(fn($date) => Carbon::parse($date)->toDateString())->unique();
        $hydrationDays = $waterByDay->keys();
        $supplementDays = $supplementsByDay->keys();
        $loggedDays = $mealDays->merge($hydrationDays)->merge($supplementDays)->unique()->count();

        $summary = [
            'logged_days' => (int) $loggedDays,
            'period_days' => (int) $periodDays,
            'total_entries' => (int) $mealLogs->count(),
            'total_calories' => (float) round($mealLogs->sum('calories'), 1),
            'average_calories' => (float) round($mealLogs->sum('calories') / max(1, $loggedDays ?: $periodDays), 1),
            'total_protein' => (float) round($mealLogs->sum('protein'), 1),
            'total_carbs' => (float) round($mealLogs->sum('carbs'), 1),
            'total_fat' => (float) round($mealLogs->sum('fat'), 1),
            'total_fiber' => (float) round($mealLogs->sum('fiber'), 1),
            'total_sugar' => (float) round($mealLogs->sum('sugar'), 1),
            'goal_daily_calories' => $nutritionGoal?->daily_calories,
            'goal_total_calories' => $nutritionGoal?->daily_calories ? (int) ($nutritionGoal->daily_calories * $periodDays) : null,
            'goal_progress_pct' => $nutritionGoal?->daily_calories
                ? (float) round(($mealLogs->sum('calories') / max(1, $nutritionGoal->daily_calories * $periodDays)) * 100, 1)
                : null,
        ];

        $hydrationGoal = (int) ($request->user()->water_goal_ml ?? 2000);
        $hydrationTotal = (int) $waterByDay->sum(fn($row) => (int) ($row->total_ml ?? 0));
        $hydration = [
            'total_ml' => $hydrationTotal,
            'average_ml' => (int) round($hydrationTotal / max(1, $loggedDays ?: $periodDays)),
            'goal_daily_ml' => $hydrationGoal,
            'goal_total_ml' => $hydrationGoal * $periodDays,
            'goal_progress_pct' => (float) round(($hydrationTotal / max(1, $hydrationGoal * $periodDays)) * 100, 1),
        ];

        $supplementTaken = (int) $supplementsByDay->sum(fn($row) => (int) ($row->taken_count ?? 0));
        $supplementExpected = $periodDays * 4;
        $supplements = [
            'taken_count' => $supplementTaken,
            'expected_count' => $supplementExpected,
            'progress_pct' => $supplementExpected > 0 ? (float) round(($supplementTaken / $supplementExpected) * 100, 1) : 0.0,
        ];

        $byMealType = $mealLogs->groupBy('meal_type')->map(function ($items, $mealType) {
            return [
                'meal_type' => $mealType,
                'entries' => (int) $items->count(),
                'calories' => (float) round($items->sum('calories'), 1),
            ];
        })->values();

        $byDay = [];
        if ($startDate && $endDate) {
            $cursor = $startDate->copy();
            while ($cursor->lte($endDate)) {
                $day = $cursor->toDateString();
                $meals = $mealLogs->filter(fn($log) => Carbon::parse($log->date)->toDateString() === $day);
                $water = (int) ($waterByDay->get($day)->total_ml ?? 0);
                $suppTaken = (int) ($supplementsByDay->get($day)->taken_count ?? 0);
                $byDay[] = [
                    'date' => $day,
                    'calories' => (float) round($meals->sum('calories'), 1),
                    'protein' => (float) round($meals->sum('protein'), 1),
                    'water_ml' => $water,
                    'supplements_taken' => $suppTaken,
                ];
                $cursor->addDay();
            }
        }

        return [
            'summary' => $summary,
            'hydration' => $hydration,
            'supplements' => $supplements,
            'by_meal_type' => $byMealType,
            'by_day' => $byDay,
        ];
    }

    private function safePercentChange(float|int $previous, float|int $current): ?float
    {
        if ((float) $previous === 0.0) {
            if ((float) $current === 0.0) {
                return 0.0;
            }

            return null;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
