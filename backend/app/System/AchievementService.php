<?php

namespace App\System;

use App\Models\User;
use App\Models\UserAchievement;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Cuarenta logros en cuatro rarezas. Los bloqueados se enseñan con su pista visible,
 * no ocultos: saber qué falta es lo que tira.
 *
 * Las métricas se calculan de una vez y se evalúan todas las condiciones contra ellas.
 * ponytail: es una decena larga de consultas por evaluación, aceptable con un puñado de
 * usuarios; si algún día pesa, se calculan solo las métricas de los logros pendientes.
 */
class AchievementService
{
    public const CATALOG = [
        // ── Comunes (10) ──────────────────────────────────────────────────
        'first_step'   => ['name' => 'Primer Paso',    'description' => 'Completa tu primer entrenamiento',             'rarity' => 'common'],
        'first_meal'   => ['name' => 'Primera Comida', 'description' => 'Registra tu primera comida',                   'rarity' => 'common'],
        'hydrated'     => ['name' => 'Hidratado',      'description' => 'Alcanza tu objetivo de agua un día',           'rarity' => 'common'],
        'on_scale'     => ['name' => 'En la Báscula',  'description' => 'Apunta tu peso por primera vez',               'rarity' => 'common'],
        'first_pr'     => ['name' => 'Superación',     'description' => 'Registra tu primer levantamiento con peso',    'rarity' => 'common'],
        'own_routine'  => ['name' => 'Rutina Propia',  'description' => 'Crea tu primera plantilla de entrenamiento',   'rarity' => 'common'],
        'three_a_day'  => ['name' => 'Tres al Día',    'description' => 'Registra las tres comidas de un día',          'rarity' => 'common'],
        'streak_3'     => ['name' => 'Constante',      'description' => '3 días seguidos con actividad',                'rarity' => 'common'],
        'workouts_10'  => ['name' => 'En Racha',       'description' => '10 entrenamientos completados',                'rarity' => 'common'],
        'supplemented' => ['name' => 'Suplementado',   'description' => 'Marca tu primer suplemento',                   'rarity' => 'common'],

        // ── Raras (12) ────────────────────────────────────────────────────
        'workouts_25'  => ['name' => 'Constancia',      'description' => '25 entrenamientos completados',                'rarity' => 'rare'],
        'streak_7'     => ['name' => 'Semana de Fuego', 'description' => '7 días seguidos con actividad',                'rarity' => 'rare'],
        'swimmer'      => ['name' => 'Nadador',         'description' => 'Completa tu primer entrenamiento de natación', 'rarity' => 'rare'],
        'gym_rat'      => ['name' => 'Gym Rat',         'description' => 'Completa tu primer entrenamiento de gimnasio', 'rarity' => 'rare'],
        'home_trainer' => ['name' => 'Casero',          'description' => 'Completa tu primer entrenamiento en casa',     'rarity' => 'rare'],
        'bodyweight'   => ['name' => 'Peso Corporal',   'description' => 'Completa tu primer entrenamiento de calistenia', 'rarity' => 'rare'],
        'all_modes'    => ['name' => 'Explorador',      'description' => 'Entrena en las 4 modalidades',                 'rarity' => 'rare'],
        'perfect_week' => ['name' => 'Semana Perfecta', 'description' => 'Ten actividad los 7 días de una semana',       'rarity' => 'rare'],
        'calories_7'   => ['name' => 'Diana',           'description' => '7 días cumpliendo tu objetivo de calorías',    'rarity' => 'rare'],
        'protein_14'   => ['name' => 'Proteico',        'description' => '14 días alcanzando tu objetivo de proteína',   'rarity' => 'rare'],
        'water_30'     => ['name' => 'Bien Regado',     'description' => '30 días alcanzando tu objetivo de agua',       'rarity' => 'rare'],
        'records_5'    => ['name' => 'Cinco Récords',   'description' => 'Bate 5 récords personales',                    'rarity' => 'rare'],

        // ── Épicas (10) ───────────────────────────────────────────────────
        'workouts_50'    => ['name' => 'Medio Centenar',     'description' => '50 entrenamientos completados',           'rarity' => 'epic'],
        'streak_30'      => ['name' => 'Mes Épico',          'description' => '30 días seguidos con actividad',          'rarity' => 'epic'],
        'volume_100k'    => ['name' => 'Tonelada',           'description' => 'Mueve 100.000 kg en total',               'rarity' => 'epic'],
        'pool_10km'      => ['name' => 'Maratón de Piscina', 'description' => 'Nada 10 km acumulados',                   'rarity' => 'epic'],
        'early_bird_20'  => ['name' => 'Madrugador',         'description' => '20 entrenamientos antes de las 8:00',     'rarity' => 'epic'],
        'night_owl_20'   => ['name' => 'Noctámbulo',         'description' => '20 entrenamientos después de las 22:00',  'rarity' => 'epic'],
        'variety_30'     => ['name' => 'Variado',            'description' => 'Registra 30 ejercicios distintos',        'rarity' => 'epic'],
        'chef_10'        => ['name' => 'Chef',               'description' => 'Crea 10 recetas propias',                 'rarity' => 'epic'],
        'flawless_month' => ['name' => 'Mes Impecable',      'description' => '20 días de un mes con todas las misiones', 'rarity' => 'epic'],
        'rank_c'         => ['name' => 'Rango C',            'description' => 'Alcanza el rango C',                      'rarity' => 'epic'],

        // ── Legendarias (8) ───────────────────────────────────────────────
        'workouts_100'  => ['name' => 'Centurión',     'description' => '100 entrenamientos completados', 'rarity' => 'legendary'],
        'workouts_365'  => ['name' => 'Veterano',      'description' => '365 entrenamientos completados', 'rarity' => 'legendary'],
        'streak_100'    => ['name' => 'Imparable',     'description' => '100 días seguidos con actividad', 'rarity' => 'legendary'],
        'year_complete' => ['name' => 'Año Completo',  'description' => '12 meses seguidos con actividad', 'rarity' => 'legendary'],
        'volume_1m'     => ['name' => 'Titán',         'description' => 'Mueve 1.000.000 kg en total',    'rarity' => 'legendary'],
        'rank_a'        => ['name' => 'Rango A',       'description' => 'Alcanza el rango A',             'rarity' => 'legendary'],
        'rank_s'        => ['name' => 'Rango S',       'description' => 'Alcanza el rango S',             'rarity' => 'legendary'],
        'collector'     => ['name' => 'Coleccionista', 'description' => 'Desbloquea los otros 39 logros', 'rarity' => 'legendary'],
    ];

    /**
     * Evalúa los logros aún bloqueados y guarda los que se cumplen.
     *
     * @return array<int, array{key:string, name:string, rarity:string}>
     */
    public function evaluate(User $user): array
    {
        $unlocked = UserAchievement::where('user_id', $user->id)
            ->pluck('achievement_key')
            ->all();

        $pending = array_diff(array_keys(self::CATALOG), $unlocked);

        if ($pending === []) {
            return [];
        }

        $conditions = $this->conditions($this->metrics($user), count($unlocked));
        $new = [];

        foreach ($pending as $key) {
            if (($conditions[$key] ?? false) !== true) {
                continue;
            }

            UserAchievement::create([
                'user_id'         => $user->id,
                'achievement_key' => $key,
                'unlocked_at'     => now(),
            ]);

            $new[] = [
                'key'    => $key,
                'name'   => self::CATALOG[$key]['name'],
                'rarity' => self::CATALOG[$key]['rarity'],
            ];
        }

        return $new;
    }

    /**
     * Los 40 con su estado, para la pantalla de logros.
     */
    public function listFor(User $user): array
    {
        $unlocked = UserAchievement::where('user_id', $user->id)
            ->pluck('unlocked_at', 'achievement_key');

        $out = [];

        foreach (self::CATALOG as $key => $meta) {
            $out[] = $meta + [
                'key'         => $key,
                'unlocked'    => $unlocked->has($key),
                'unlocked_at' => $unlocked->get($key),
            ];
        }

        return $out;
    }

    /**
     * @param  array<string, mixed>  $m
     * @return array<string, bool>
     */
    private function conditions(array $m, int $alreadyUnlocked): array
    {
        return [
            'first_step'   => $m['workouts'] >= 1,
            'first_meal'   => $m['meals'] >= 1,
            'hydrated'     => $m['water_goal_days'] >= 1,
            'on_scale'     => $m['weight_logs'] >= 1,
            'first_pr'     => $m['weighted_sets'] >= 1,
            'own_routine'  => $m['templates'] >= 1,
            'three_a_day'  => $m['three_meal_days'] >= 1,
            'streak_3'     => $m['longest_streak'] >= 3,
            'workouts_10'  => $m['workouts'] >= 10,
            'supplemented' => $m['supplements_taken'] >= 1,

            'workouts_25'  => $m['workouts'] >= 25,
            'streak_7'     => $m['longest_streak'] >= 7,
            'swimmer'      => in_array('swimming', $m['modes'], true),
            'gym_rat'      => in_array('gym', $m['modes'], true),
            'home_trainer' => in_array('home', $m['modes'], true),
            'bodyweight'   => in_array('calisthenics', $m['modes'], true),
            'all_modes'    => count(array_intersect(['gym', 'home', 'calisthenics', 'swimming'], $m['modes'])) >= 4,
            'perfect_week' => $m['perfect_week'],
            'calories_7'   => $m['calorie_goal_days'] >= 7,
            'protein_14'   => $m['protein_goal_days'] >= 14,
            'water_30'     => $m['water_goal_days'] >= 30,
            'records_5'    => $m['records'] >= 5,

            'workouts_50'    => $m['workouts'] >= 50,
            'streak_30'      => $m['longest_streak'] >= 30,
            'volume_100k'    => $m['volume'] >= 100000,
            'pool_10km'      => $m['swim_metres'] >= 10000,
            'early_bird_20'  => $m['early_workouts'] >= 20,
            'night_owl_20'   => $m['late_workouts'] >= 20,
            'variety_30'     => $m['distinct_exercises'] >= 30,
            'chef_10'        => $m['recipes'] >= 10,
            'flawless_month' => $m['best_flawless_month'] >= 20,
            'rank_c'         => $m['level'] >= 25,

            'workouts_100'  => $m['workouts'] >= 100,
            'workouts_365'  => $m['workouts'] >= 365,
            'streak_100'    => $m['longest_streak'] >= 100,
            'year_complete' => $m['consecutive_active_months'] >= 12,
            'volume_1m'     => $m['volume'] >= 1000000,
            'rank_a'        => $m['level'] >= 45,
            'rank_s'        => $m['level'] >= 55,
            'collector'     => $alreadyUnlocked >= 39,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metrics(User $user): array
    {
        $id = $user->id;

        $workouts = DB::table('workouts')
            ->where('user_id', $id)
            ->where('mode', '!=', 'pasos')
            ->get(['date', 'mode']);

        // ponytail: las métricas por hora, semana y mes se calculan en PHP y no en SQL,
        // porque HOUR()/strftime() no son portables entre MySQL y el SQLite de los tests,
        // y el volumen por usuario es de miles de filas, no de millones.
        $hours = $workouts->map(fn ($w) => Carbon::parse($w->date));
        $activeDates = $this->activeDates($user);

        $waterGoal = max(1, (int) ($user->water_goal_ml ?? 2000));
        $nutrition = DB::table('nutrition_goals')->where('user_id', $id)->first();

        $mealsByDay = DB::table('meal_logs')
            ->where('user_id', $id)
            ->selectRaw('date, COUNT(DISTINCT meal_type) as tipos, SUM(protein) as prot, SUM(calories) as kcal')
            ->groupBy('date')
            ->get();

        return [
            'workouts'          => $workouts->count(),
            'modes'             => $workouts->pluck('mode')->unique()->values()->all(),
            'meals'             => DB::table('meal_logs')->where('user_id', $id)->count(),
            'templates'         => DB::table('templates')->where('user_id', $id)->count(),
            'recipes'           => DB::table('recipes')->where('user_id', $id)->count(),
            'weight_logs'       => DB::table('weight_logs')->where('user_id', $id)->count(),
            'supplements_taken' => DB::table('supplement_logs')->where('user_id', $id)->where('taken', true)->count(),
            'records'           => DB::table('xp_events')->where('user_id', $id)->where('source', 'record')->count(),
            'level'             => (int) (DB::table('user_progress')->where('user_id', $id)->value('level') ?? 1),

            'volume' => (float) DB::table('exercise_sets as es')
                ->join('workouts as w', 'es.workout_id', '=', 'w.id')
                ->where('w.user_id', $id)
                ->sum(DB::raw('COALESCE(es.weight_kg, 0) * COALESCE(es.reps, 0) * COALESCE(es.sets, 1)')),

            'swim_metres' => (float) DB::table('exercise_sets as es')
                ->join('workouts as w', 'es.workout_id', '=', 'w.id')
                ->where('w.user_id', $id)->where('w.mode', 'swimming')
                ->sum(DB::raw('COALESCE(es.distance_m, 0)')),

            'distinct_exercises' => DB::table('exercise_sets as es')
                ->join('workouts as w', 'es.workout_id', '=', 'w.id')
                ->where('w.user_id', $id)
                ->distinct()->count('es.name'),

            'weighted_sets' => DB::table('exercise_sets as es')
                ->join('workouts as w', 'es.workout_id', '=', 'w.id')
                ->where('w.user_id', $id)->where('es.weight_kg', '>', 0)
                ->count(),

            'early_workouts' => $hours->filter(fn ($d) => $d->hour < 8)->count(),
            'late_workouts'  => $hours->filter(fn ($d) => $d->hour >= 22)->count(),

            'longest_streak'            => $this->longestStreak($activeDates),
            'perfect_week'              => $this->hasPerfectWeek($activeDates),
            'consecutive_active_months' => $this->consecutiveActiveMonths($activeDates),

            'three_meal_days'   => $mealsByDay->where('tipos', '>=', 3)->count(),
            'protein_goal_days' => $nutrition
                ? $mealsByDay->filter(fn ($d) => $d->prot >= $nutrition->target_protein)->count()
                : 0,
            'calorie_goal_days' => $nutrition
                ? $mealsByDay->filter(fn ($d) => abs($d->kcal - $nutrition->daily_calories) <= $nutrition->daily_calories * 0.10)->count()
                : 0,

            'water_goal_days' => DB::table('water_logs')
                ->where('user_id', $id)
                ->selectRaw('date, SUM(amount_ml) as ml')
                ->groupBy('date')
                ->havingRaw('SUM(amount_ml) >= ?', [$waterGoal])
                ->get()->count(),

            'best_flawless_month' => $this->bestFlawlessMonth($user),
        ];
    }

    /**
     * Días con cualquier tipo de actividad, en orden ascendente. Es la definición de
     * «día activo» que usan la racha del Sistema y todos los logros de racha.
     *
     * Los pasos SÍ cuentan aquí, aunque no sean entrenamiento y no den XP de entreno:
     * apuntar los pasos es actividad. Hay quien usa la app solo para eso, y dejarle la
     * racha a cero para siempre sería absurdo. Lo que no cuentan es como entreno, y de
     * eso se encarga la consulta de `$workouts`, que sí los filtra.
     *
     * @return string[] fechas 'Y-m-d'
     */
    public function activeDates(User $user): array
    {
        $id = $user->id;

        $sets = [
            DB::table('workouts')->where('user_id', $id)->pluck('date'),
            DB::table('meal_logs')->where('user_id', $id)->pluck('date'),
            DB::table('water_logs')->where('user_id', $id)->pluck('date'),
            DB::table('supplement_logs')->where('user_id', $id)->where('taken', true)->pluck('date'),
            DB::table('weight_logs')->where('user_id', $id)->pluck('date'),
        ];

        return collect($sets)->flatten()
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /** @param  string[]  $dates */
    private function longestStreak(array $dates): int
    {
        $best = 0;
        $run = 0;
        $previous = null;

        foreach ($dates as $date) {
            $run = ($previous !== null && Carbon::parse($previous)->addDay()->toDateString() === $date)
                ? $run + 1
                : 1;

            $best = max($best, $run);
            $previous = $date;
        }

        return $best;
    }

    /** @param  string[]  $dates */
    private function hasPerfectWeek(array $dates): bool
    {
        return collect($dates)
            ->groupBy(fn ($d) => Carbon::parse($d)->format('o-W'))
            ->contains(fn ($week) => count($week) >= 7);
    }

    /** @param  string[]  $dates */
    private function consecutiveActiveMonths(array $dates): int
    {
        $months = collect($dates)->map(fn ($d) => Carbon::parse($d)->format('Y-m'))->unique()->sort()->values();

        $best = 0;
        $run = 0;
        $previous = null;

        foreach ($months as $month) {
            $run = ($previous !== null && Carbon::parse($previous.'-01')->addMonth()->format('Y-m') === $month)
                ? $run + 1
                : 1;

            $best = max($best, $run);
            $previous = $month;
        }

        return $best;
    }

    /**
     * Mejor mes natural por número de días con todas las misiones obligatorias hechas.
     */
    private function bestFlawlessMonth(User $user): int
    {
        $days = DB::table('daily_quests')
            ->where('user_id', $user->id)
            ->where('is_optional', false)
            ->selectRaw('date, COUNT(*) as total, SUM(CASE WHEN completed_at IS NULL THEN 0 ELSE 1 END) as hechas')
            ->groupBy('date')
            ->get()
            ->filter(fn ($d) => $d->total > 0 && (int) $d->total === (int) $d->hechas);

        return $days
            ->groupBy(fn ($d) => Carbon::parse($d->date)->format('Y-m'))
            ->map(fn ($month) => count($month))
            ->max() ?? 0;
    }
}
