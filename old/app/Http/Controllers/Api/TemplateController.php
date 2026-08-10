<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class TemplateController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Templates del sistema (user_id NULL) se cachean 1 hora
        $systemTemplates = Cache::remember('system_templates', 3600, function () {
            return Template::whereNull('user_id')->with('exercises')->get();
        });

        // Templates personalizados siempre frescos
        $userTemplates = Template::where('user_id', $user->id)
            ->with('exercises')
            ->get();

        return response()->json($systemTemplates->concat($userTemplates)->values());
    }
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'exercises' => 'required|array|min:1|max:50',
            'exercises.*.name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'level' => 'nullable|string|in:Básico,Intermedio,Avanzado',
            'mode' => 'nullable|string|in:gym,home,calisthenics,swimming',
            'duration_minutes' => 'nullable|integer|min:1|max:600',
        ]);

        $template = Template::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? 'Rutina personalizada',
            'level' => $validated['level'] ?? 'Intermedio',
            'mode' => $validated['mode'] ?? 'gym',
            'is_custom' => true,
            'duration_minutes' => $validated['duration_minutes'] ?? 60,
        ]);

        foreach ($validated['exercises'] as $exData) {
            $template->exercises()->create([
                'name' => $exData['name'],
                'sets' => $exData['sets'] ?? 3,
                'reps' => $exData['reps'] ?? 10,
                'weight_kg' => $exData['weight'] ?? 0,
                'time_seconds' => $exData['time'] ?? 0,
                'distance_m' => $exData['distance'] ?? 0,
                // Add defaults for other fields if needed
            ]);
        }

        return response()->json(['message' => 'Template created', 'template' => $template->load('exercises')], 201);
    }

    public function destroy(Request $request, Template $template)
    {
        if ($template->user_id === null) {
            return response()->json(['message' => 'No se pueden eliminar plantillas del sistema'], 403);
        }
        if ($template->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $template->delete();
        return response()->json(['message' => 'Plantilla eliminada']);
    }

    public function update(Request $request, Template $template)
    {
        if ($template->user_id === null) {
            return response()->json(['message' => 'No se pueden editar plantillas del sistema'], 403);
        }
        if ($template->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'level' => 'nullable|string|in:Básico,Intermedio,Avanzado',
            'mode' => 'nullable|string|in:gym,home,calisthenics,swimming',
            'duration_minutes' => 'nullable|integer|min:1|max:600',
            'exercises' => 'required|array|min:1|max:50',
            'exercises.*.name' => 'required|string|max:100',
            'exercises.*.sets' => 'nullable|integer|min:1|max:100',
            'exercises.*.reps' => 'nullable|integer|min:0|max:1000',
        ]);

        $template->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $template->description,
            'level' => $validated['level'] ?? $template->level,
            'mode' => $validated['mode'] ?? $template->mode,
            'duration_minutes' => $validated['duration_minutes'] ?? $template->duration_minutes,
        ]);

        $template->exercises()->delete();
        foreach ($validated['exercises'] as $exData) {
            $template->exercises()->create([
                'name' => $exData['name'],
                'sets' => $exData['sets'] ?? 3,
                'reps' => $exData['reps'] ?? 10,
            ]);
        }

        return response()->json($template->load('exercises'));
    }
}
