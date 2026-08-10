<?php

namespace App\Http\Controllers\Api;

use App\Events\WorkoutStored;
use App\Http\Controllers\Controller;
use App\Models\Workout;
use App\Models\ExerciseSet;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkoutController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $query = Workout::where('user_id', $user->id)
            ->where('mode', '!=', 'pasos')   // actividad diaria: no es entrenamiento
            ->with('sets')
            ->orderBy('date', $request->query('sort', 'desc') === 'asc' ? 'asc' : 'desc');

        if ($request->filled('mode')) {
            $query->where('mode', $request->mode);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $this->parseDate($request->query('date_from'))->toDateString());
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $this->parseDate($request->query('date_to'))->toDateString());
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('notes', 'like', "%{$search}%")
                    ->orWhere('mode', 'like', "%{$search}%")
                    ->orWhereHas('sets', fn($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->boolean('all')) {
            return response()->json($query->get());
        }

        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        return response()->json($query->paginate($perPage));
    }

    private function parseDate(?string $value): Carbon
    {
        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            abort(response()->json([
                'message' => 'Fecha inválida.',
                'errors' => ['date' => ['Formato de fecha inválido.']],
            ], 422));
        }
    }

    public function show(Request $request, Workout $workout)
    {
        if ($request->user()->id !== $workout->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        return response()->json($workout->load('sets'));
    }

    public function update(Request $request, Workout $workout)
    {
        if ($request->user()->id !== $workout->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'notes'            => 'nullable|string|max:1000',
            'duration_minutes' => 'nullable|integer|min:1|max:600',
        ]);
        $workout->update($validated);
        return response()->json($workout->load('sets'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'mode' => 'required|string|in:gym,home,calisthenics,swimming,pasos',
            'date' => 'required|date',
            'duration_minutes' => 'required|integer|min:0|max:600',
            'calories_burned' => 'nullable|integer|min:0|max:10000',
            'notes' => 'nullable|string|max:2000',
            'exercises' => 'array|max:50',
            'exercises.*.name' => 'required|string|max:100',
            'exercises.*.sets' => 'array|max:50',
            'exercises.*.sets.*.weight_kg' => 'nullable|numeric|min:0|max:2000',
            'exercises.*.sets.*.reps' => 'nullable|integer|min:0|max:1000',
            'exercises.*.sets.*.rpe' => 'nullable|integer|min:1|max:10',
            'exercises.*.sets.*.rest_seconds' => 'nullable|integer|min:0|max:3600',
            'exercises.*.sets.*.time_seconds' => 'nullable|integer|min:0|max:100000',
            'exercises.*.sets.*.distance_m' => 'nullable|numeric|min:0|max:100000',
            'exercises.*.sets.*.laps' => 'nullable|integer|min:0|max:1000',
            'exercises.*.sets.*.style' => 'nullable|string|max:50',
        ]);

        $user = $request->user();

        // PR previos por ejercicio (antes de insertar)
        $exerciseNames = collect($request->exercises ?? [])->pluck('name')->unique();
        $previousPRs = [];
        if ($exerciseNames->isNotEmpty()) {
            $previousPRs = DB::table('exercise_sets as es')
                ->join('workouts as w', 'es.workout_id', '=', 'w.id')
                ->where('w.user_id', $user->id)
                ->whereNotNull('es.weight_kg')
                ->where('es.weight_kg', '>', 0)
                ->whereIn('es.name', $exerciseNames)
                ->groupBy('es.name')
                ->select('es.name', DB::raw('MAX(es.weight_kg) as max_weight'))
                ->pluck('max_weight', 'name')
                ->toArray();
        }

        return DB::transaction(function () use ($request, $user, $previousPRs) {
            $workout = Workout::create([
                'user_id'          => $user->id,
                'mode'             => $request->mode,
                'date'             => $request->date,
                'duration_minutes' => $request->duration_minutes,
                'calories_burned'  => $request->calories_burned ?? null,
                'notes'            => $request->notes ?? null,
            ]);

            $newRecords = [];

            foreach ($request->exercises ?? [] as $ex) {
                $bestWeight = 0.0;

                foreach ($ex['sets'] ?? [] as $set) {
                    ExerciseSet::create([
                        'workout_id'   => $workout->id,
                        'name'         => $ex['name'],
                        'sets'         => 1, // una fila = una serie; sets=1 mantiene el cálculo de volumen
                        'reps'         => $set['reps'] ?? null,
                        'weight_kg'    => $set['weight_kg'] ?? null,
                        'rpe'          => $set['rpe'] ?? null,
                        'rest_seconds' => $set['rest_seconds'] ?? null,
                        'time_seconds' => $set['time_seconds'] ?? null,
                        'distance_m'   => $set['distance_m'] ?? null,
                        'laps'         => $set['laps'] ?? null,
                        'style'        => $set['style'] ?? null,
                    ]);

                    $w = (float) ($set['weight_kg'] ?? 0);
                    if ($w > $bestWeight) {
                        $bestWeight = $w;
                    }
                }

                if ($bestWeight > 0) {
                    $prev = (float) ($previousPRs[$ex['name']] ?? 0);
                    if ($bestWeight > $prev) {
                        $newRecords[] = [
                            'name'        => $ex['name'],
                            'weight_kg'   => $bestWeight,
                            'previous_pr' => $prev > 0 ? $prev : null,
                            'is_first'    => $prev === 0.0,
                        ];
                    }
                }
            }

            $data                = $workout->load('sets')->toArray();
            $data['new_records'] = $newRecords;

            $event = new WorkoutStored($workout, $newRecords);
            event($event);
            $data['system'] = $event->rewards;

            return response()->json($data, 201);
        });
    }
    public function destroy(Request $request, Workout $workout)
    {
        // Ensure the workout belongs to the authenticated user
        if ($request->user()->id !== $workout->user_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $workout->delete();

        return response()->json(['message' => 'Workout deleted']);
    }
}
