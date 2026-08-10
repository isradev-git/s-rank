<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseSet;
use App\Models\Workout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExerciseController extends Controller
{
    public function index()
    {
        $exercises = [
            ["name" => "Sentadilla", "category" => "Pierna", "muscle_group" => "Cuádriceps", "video_url" => "https://media.giphy.com/media/1Oax1s47bE0yX2aO1q/giphy.gif"],
            ["name" => "Press Banca", "category" => "Empuje", "muscle_group" => "Pecho", "video_url" => "https://media.giphy.com/media/l41YhWbJboLC1RvsI/giphy.gif"],
            ["name" => "Peso Muerto", "category" => "Pierna", "muscle_group" => "Isquios/Espalda", "video_url" => "https://media.giphy.com/media/3o7qDEq2bMbcbPRQ2c/giphy.gif"],
            ["name" => "Dominadas", "category" => "Tracción", "muscle_group" => "Espalda", "video_url" => "https://media.giphy.com/media/l0HlPtbGpcnqa0fja/giphy.gif"],
            ["name" => "Press Militar", "category" => "Empuje", "muscle_group" => "Hombros", "video_url" => ""],
            ["name" => "Fondos", "category" => "Empuje", "muscle_group" => "Pecho/Tríceps", "video_url" => ""],
            ["name" => "Remo con Barra", "category" => "Tracción", "muscle_group" => "Espalda", "video_url" => ""],
            ["name" => "Zancadas", "category" => "Pierna", "muscle_group" => "Cuádriceps/Glúteo", "video_url" => ""],
            ["name" => "Crol", "category" => "Natación", "muscle_group" => "Full Body", "video_url" => ""],
            ["name" => "Braza", "category" => "Natación", "muscle_group" => "Full Body", "video_url" => ""],
            ["name" => "Burpees", "category" => "Cardio", "muscle_group" => "Full Body", "video_url" => ""],
            ["name" => "Plancha", "category" => "Core", "muscle_group" => "Abdomen", "video_url" => ""]
        ];
        return response()->json($exercises);
    }

    public function history(Request $request)
    {
        $user = $request->user();
        $name = $request->query('name');

        if (!$name) {
            return response()->json(null);
        }

        // Find last set of this exercise for this user
        // We need to join workouts to check user_id
        
        $lastSet = ExerciseSet::whereHas('workout', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->where('name', $name)
            ->join('workouts', 'exercise_sets.workout_id', '=', 'workouts.id')
            ->orderBy('workouts.date', 'desc')
            ->select('exercise_sets.*', 'workouts.date as workout_date')
            ->first();

        if ($lastSet) {
            return response()->json([
                "last_date" => $lastSet->workout_date,
                "weight_kg" => $lastSet->weight_kg,
                "reps" => $lastSet->reps,
                "sets" => $lastSet->sets
            ]);
        }

        return response()->json(null);
    }

    /**
     * Series de la ÚLTIMA sesión que contiene este ejercicio (para la columna "Anterior").
     * GET /api/exercises/last-session?name=Press%20Banca
     */
    public function lastSession(Request $request)
    {
        $user = $request->user();
        $name = $request->query('name');
        if (!$name) {
            return response()->json([]);
        }

        // Fecha del entreno más reciente que incluye este ejercicio
        $lastDate = ExerciseSet::query()
            ->join('workouts', 'exercise_sets.workout_id', '=', 'workouts.id')
            ->where('workouts.user_id', $user->id)
            ->where('exercise_sets.name', $name)
            ->max('workouts.date');

        if (!$lastDate) {
            return response()->json([]);
        }

        $sets = ExerciseSet::query()
            ->join('workouts', 'exercise_sets.workout_id', '=', 'workouts.id')
            ->where('workouts.user_id', $user->id)
            ->where('exercise_sets.name', $name)
            ->whereDate('workouts.date', \Carbon\Carbon::parse($lastDate)->toDateString())
            ->orderBy('exercise_sets.id')
            ->get(['exercise_sets.weight_kg', 'exercise_sets.reps', 'exercise_sets.rpe', 'exercise_sets.time_seconds', 'exercise_sets.distance_m']);

        return response()->json($sets->map(fn ($s) => [
            'weight_kg'    => $s->weight_kg !== null ? (float) $s->weight_kg : null,
            'reps'         => $s->reps,
            'rpe'          => $s->rpe,
            'time_seconds' => $s->time_seconds,
            'distance_m'   => $s->distance_m !== null ? (float) $s->distance_m : null,
        ]));
    }

    /**
     * Devuelve sugerencias de nombres de ejercicios desde el historial del usuario.
     */
    public function suggestions(Request $request)
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));

        $names = ExerciseSet::whereHas('workout', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%");
            })
            ->select('name', DB::raw('COUNT(*) as usage_count'))
            ->groupBy('name')
            ->orderByDesc('usage_count')
            ->limit(8)
            ->pluck('name');

        return response()->json($names->values());
    }

    public function progress(Request $request)
    {
        $user = $request->user();
        $name = $request->query('name');
        if (!$name) return response()->json([]);

        $rows = ExerciseSet::whereHas('workout', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            })
            ->where('name', $name)
            ->join('workouts', 'exercise_sets.workout_id', '=', 'workouts.id')
            ->orderBy('workouts.date', 'asc')
            ->select('exercise_sets.*', 'workouts.date as workout_date')
            ->get();

        // Una entrada por fecha: la mejor serie del día (mayor peso; empate => más reps).
        $byDate = $rows
            ->groupBy(fn ($s) => \Carbon\Carbon::parse($s->workout_date)->toDateString())
            ->map(function ($daySets, $date) {
                $best = $daySets->sortByDesc(fn ($s) => [(float) ($s->weight_kg ?? 0), (int) ($s->reps ?? 0)])->first();

                return [
                    'date'      => $date,
                    'weight_kg' => $best->weight_kg,
                    'reps'      => $best->reps,
                    'sets'      => $daySets->count(),
                ];
            })
            ->values();

        return response()->json($byDate);
    }

    /**
     * Devuelve el récord personal (máximo weight_kg) de cada ejercicio del usuario.
     * Solo incluye ejercicios con datos de peso (weight_kg > 0).
     */
    public function records(Request $request)
    {
        $user   = $request->user();
        $userId = $user->id;

        // Encontrar el set con el máximo weight_kg por ejercicio.
        // La subconsulta garantiza que devolvemos el registro real donde se logró el PR
        // (no solo el max_weight suelto sin contexto).
        $records = DB::table('exercise_sets as es')
            ->join('workouts as w', 'es.workout_id', '=', 'w.id')
            ->where('w.user_id', $userId)
            ->whereNotNull('es.weight_kg')
            ->where('es.weight_kg', '>', 0)
            ->whereRaw('es.weight_kg = (
                SELECT MAX(es2.weight_kg)
                FROM exercise_sets es2
                JOIN workouts w2 ON es2.workout_id = w2.id
                WHERE w2.user_id = ? AND es2.name = es.name
            )', [$userId])
            ->groupBy('es.name')
            ->select('es.name', 'es.weight_kg as max_weight', 'es.reps', 'es.sets', 'w.date')
            ->orderByDesc('es.weight_kg')
            ->get();

        return response()->json($records);
    }
}