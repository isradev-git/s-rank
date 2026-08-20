<?php

namespace App\Http\Controllers\Api;

use App\Events\WorkoutStored;
use App\Http\Controllers\Controller;
use App\Models\Workout;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActivityController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $date = $request->string('date')->isNotEmpty()
            ? Carbon::parse($request->string('date'))
            : Carbon::today();

        $log = Workout::where('user_id', $request->user()->id)
            ->where('mode', 'pasos')
            ->whereDate('date', $date->toDateString())
            ->latest('date')
            ->first();

        $steps = 0;
        if ($log && !empty($log->notes)) {
            $decoded = json_decode((string) $log->notes, true);
            $steps = (int) (($decoded['steps'] ?? 0));
        }

        return response()->json([
            'date' => $date->format('Y-m-d'),
            'steps' => $steps,
            'calories_burned' => (int) round((float) ($log->calories_burned ?? 0)),
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'nullable|date',
            'steps' => 'required|integer|min:0|max:150000',
            'calories_burned' => 'required|numeric|min:0|max:10000',
        ]);

        $date = !empty($validated['date'])
            ? Carbon::parse($validated['date'])->toDateString()
            : now()->toDateString();

        $log = Workout::where('user_id', $request->user()->id)
            ->where('mode', 'pasos')
            ->whereDate('date', $date)
            ->latest('date')
            ->first();

        $payload = [
            'user_id' => $request->user()->id,
            'mode' => 'pasos',
            'date' => Carbon::parse($date)->startOfDay(),
            'duration_minutes' => 0,
            'calories_burned' => $validated['calories_burned'],
            'notes' => json_encode([
                'type' => 'daily_activity',
                'steps' => (int) $validated['steps'],
            ]),
        ];

        if ($log) {
            $log->update($payload);
        } else {
            $log = Workout::create($payload);
        }

        // El módulo avisa; el Sistema decide. afterWorkout() ya se salta el XP cuando el
        // modo es «pasos» y cierra el día igual, así que publicar el evento es todo lo
        // que hace falta para que apuntar los pasos mantenga la racha.
        $event = new WorkoutStored($log);
        event($event);

        return response()->json([
            'message' => 'Actividad diaria guardada.',
            'date' => Carbon::parse($log->date)->format('Y-m-d'),
            'steps' => (int) $validated['steps'],
            'calories_burned' => (int) round((float) $log->calories_burned),
            'system' => $event->rewards,
        ]);
    }
}
