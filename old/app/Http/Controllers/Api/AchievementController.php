<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseSet;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AchievementController extends Controller
{
    public function index(Request $request)
    {
        $user   = $request->user();
        $userId = $user->id;

        // ── Datos base ────────────────────────────────────────────────────
        $totalWorkouts = Workout::where('user_id', $userId)->count();
        $modes         = Workout::where('user_id', $userId)->distinct()->pluck('mode')->toArray();

        $hasPR = ExerciseSet::whereHas('workout', fn($q) => $q->where('user_id', $userId))
            ->whereNotNull('weight_kg')
            ->where('weight_kg', '>', 0)
            ->exists();

        // Fechas distintas de entrenamiento (orden desc) para calcular racha máxima
        $dates = Workout::where('user_id', $userId)
            ->selectRaw('DATE(date) as d')
            ->distinct()
            ->orderByRaw('d DESC')
            ->pluck('d')
            ->map(fn($d) => Carbon::parse($d)->toDateString())
            ->all();

        // Racha máxima histórica
        $maxStreak = 0;
        if (!empty($dates)) {
            $run = 1;
            for ($i = 0; $i < count($dates) - 1; $i++) {
                $diffDays = Carbon::parse($dates[$i])->diffInDays(Carbon::parse($dates[$i + 1]));
                if ($diffDays === 1) {
                    $run++;
                } else {
                    $maxStreak = max($maxStreak, $run);
                    $run = 1;
                }
            }
            $maxStreak = max($maxStreak, $run);
        }

        // Semana perfecta: ¿hay alguna semana ISO con 7 días distintos de entrenamiento?
        $allDates  = Workout::where('user_id', $userId)->get(['date']);
        $perfectWeek = $allDates
            ->groupBy(fn($w) => Carbon::parse($w->date)->format('o-W'))
            ->contains(fn($group) => $group->map(fn($w) => Carbon::parse($w->date)->toDateString())->unique()->count() >= 7);

        // Primer entrenamiento
        $firstWorkout = Workout::where('user_id', $userId)->orderBy('date')->first();

        // Entrenamiento por modo
        $firstSwim = Workout::where('user_id', $userId)->where('mode', 'swimming')->orderBy('date')->first();
        $firstGym  = Workout::where('user_id', $userId)->where('mode', 'gym')->orderBy('date')->first();
        $firstCali = Workout::where('user_id', $userId)->where('mode', 'calisthenics')->orderBy('date')->first();
        $firstHome = Workout::where('user_id', $userId)->where('mode', 'home')->orderBy('date')->first();

        $allModesUnlocked = count(array_intersect(['gym', 'home', 'calisthenics', 'swimming'], $modes)) >= 4;

        // ── Definición de logros ──────────────────────────────────────────
        $achievements = [
            [
                'id'          => 'first_step',
                'name'        => 'Primer Paso',
                'description' => 'Completa tu primer entrenamiento',
                'icon'        => 'footprints',
                'color'       => '#f59e0b',
                'unlocked'    => $totalWorkouts >= 1,
                'unlocked_at' => $firstWorkout?->date,
            ],
            [
                'id'          => 'workouts_10',
                'name'        => 'En Racha',
                'description' => '10 entrenamientos completados',
                'icon'        => 'zap',
                'color'       => '#60a5fa',
                'unlocked'    => $totalWorkouts >= 10,
                'unlocked_at' => null,
            ],
            [
                'id'          => 'workouts_25',
                'name'        => 'Constancia',
                'description' => '25 entrenamientos completados',
                'icon'        => 'award',
                'color'       => '#a78bfa',
                'unlocked'    => $totalWorkouts >= 25,
                'unlocked_at' => null,
            ],
            [
                'id'          => 'workouts_50',
                'name'        => 'Medio Centenar',
                'description' => '50 entrenamientos completados',
                'icon'        => 'trophy',
                'color'       => '#f59e0b',
                'unlocked'    => $totalWorkouts >= 50,
                'unlocked_at' => null,
            ],
            [
                'id'          => 'workouts_100',
                'name'        => 'Centurión',
                'description' => '100 entrenamientos completados',
                'icon'        => 'crown',
                'color'       => '#eab308',
                'unlocked'    => $totalWorkouts >= 100,
                'unlocked_at' => null,
            ],
            [
                'id'          => 'streak_7',
                'name'        => 'Semana de Fuego',
                'description' => '7 días consecutivos entrenando',
                'icon'        => 'flame',
                'color'       => '#f97316',
                'unlocked'    => $maxStreak >= 7,
                'unlocked_at' => null,
            ],
            [
                'id'          => 'streak_30',
                'name'        => 'Mes Épico',
                'description' => '30 días consecutivos entrenando',
                'icon'        => 'flame',
                'color'       => '#ef4444',
                'unlocked'    => $maxStreak >= 30,
                'unlocked_at' => null,
            ],
            [
                'id'          => 'perfect_week',
                'name'        => 'Semana Perfecta',
                'description' => 'Entrena los 7 días de una semana',
                'icon'        => 'star',
                'color'       => '#fbbf24',
                'unlocked'    => $perfectWeek,
                'unlocked_at' => null,
            ],
            [
                'id'          => 'first_pr',
                'name'        => 'Superación',
                'description' => 'Registra tu primer levantamiento con peso',
                'icon'        => 'trending-up',
                'color'       => '#34d399',
                'unlocked'    => $hasPR,
                'unlocked_at' => null,
            ],
            [
                'id'          => 'all_modes',
                'name'        => 'Explorador',
                'description' => 'Entrena en las 4 modalidades',
                'icon'        => 'compass',
                'color'       => '#22d3ee',
                'unlocked'    => $allModesUnlocked,
                'unlocked_at' => null,
            ],
            [
                'id'          => 'swimmer',
                'name'        => 'Nadador',
                'description' => 'Completa tu primer entrenamiento de natación',
                'icon'        => 'waves',
                'color'       => '#38bdf8',
                'unlocked'    => $firstSwim !== null,
                'unlocked_at' => $firstSwim?->date,
            ],
            [
                'id'          => 'gym_rat',
                'name'        => 'Gym Rat',
                'description' => 'Completa tu primer entrenamiento en el gimnasio',
                'icon'        => 'dumbbell',
                'color'       => '#f59e0b',
                'unlocked'    => $firstGym !== null,
                'unlocked_at' => $firstGym?->date,
            ],
        ];

        $unlockedCount = collect($achievements)->where('unlocked', true)->count();

        return response()->json([
            'achievements'   => $achievements,
            'unlocked_count' => $unlockedCount,
            'total_count'    => count($achievements),
        ]);
    }
}
