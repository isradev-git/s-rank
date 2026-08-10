<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Template;
use App\Models\Workout;
use App\System\AchievementService;
use App\System\QuestService;
use App\System\SystemService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SystemController extends Controller
{
    public function __construct(
        private SystemService $system,
        private QuestService $quests,
        private AchievementService $achievements,
    ) {}

    /**
     * Progreso + misiones del día + entreno sugerido.
     *
     * Genera las misiones si aún no existen. Es idempotente, así que no hace falta
     * cron: la primera petición de cada día las crea y las siguientes las leen.
     */
    public function today(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $this->todayDate();

        $this->quests->generate($user, $date);
        $this->quests->sync($user, $date);

        return response()->json([
            'date'              => $date->toDateString(),
            'progress'          => $this->system->snapshot($user),
            'quests'            => $this->quests->forDate($user, $date),
            'suggested_workout' => $this->suggestedWorkout($user, $date),
        ]);
    }

    public function profile(Request $request): JsonResponse
    {
        return response()->json([
            'progress' => $this->system->snapshot($request->user()),
            'modules'  => config('srank.modules'),
        ]);
    }

    public function achievements(Request $request): JsonResponse
    {
        $list = $this->achievements->listFor($request->user());

        return response()->json([
            'achievements'   => $list,
            'unlocked_count' => count(array_filter($list, fn ($a) => $a['unlocked'])),
            'total_count'    => count($list),
        ]);
    }

    /**
     * Marca a mano una misión opcional. Las opcionales no se pueden medir solas.
     */
    public function completeQuest(Request $request, string $key): JsonResponse
    {
        $user = $request->user();
        $date = $this->todayDate();

        if (! $this->quests->completeOptional($user, $key, $date)) {
            return response()->json([
                'message' => 'Esa misión no se puede marcar a mano o ya está hecha.',
            ], 422);
        }

        return response()->json([
            'system' => $this->system->afterHabit($user, $date),
            'quests' => $this->quests->forDate($user, $date),
        ]);
    }

    private function todayDate(): CarbonImmutable
    {
        return CarbonImmutable::now(config('srank.timezone'))->startOfDay();
    }

    /**
     * Qué entrenar hoy: una plantilla del modo en el que más ha entrenado
     * últimamente, con el motivo escrito en español llano.
     */
    private function suggestedWorkout($user, CarbonImmutable $date): array
    {
        $goal = max(1, (int) $user->weekly_goal);

        $done = Workout::where('user_id', $user->id)
            ->where('mode', '!=', 'pasos')
            ->whereBetween('date', [
                $date->startOfWeek()->startOfDay()->toDateTimeString(),
                $date->endOfWeek()->endOfDay()->toDateTimeString(),
            ])
            ->count();

        $favouriteMode = Workout::where('user_id', $user->id)
            ->where('mode', '!=', 'pasos')
            ->where('date', '>=', $date->subDays(30)->toDateTimeString())
            ->selectRaw('mode, COUNT(*) as total')
            ->groupBy('mode')
            ->orderByDesc('total')
            ->value('mode');

        $template = Template::where('user_id', $user->id)
            ->when($favouriteMode, fn ($q) => $q->where('mode', $favouriteMode))
            ->latest('id')
            ->first();

        $pending = max(0, $goal - $done);

        return [
            'reason' => $pending === 0
                ? 'Ya has cumplido tu meta de esta semana.'
                : ($pending === 1
                    ? 'Te falta 1 entreno para tu meta de esta semana.'
                    : "Te faltan {$pending} entrenos para tu meta de esta semana."),
            'weekly_done' => $done,
            'weekly_goal' => $goal,
            'template'    => $template,
        ];
    }
}
