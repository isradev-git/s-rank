<?php

namespace App\Http\Controllers;

use App\Models\MealLog;
use App\Models\NutritionGoal;
use App\Models\WaterLog;
use App\Models\WeightLog;
use App\Models\Workout;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Informe de salud unificado (peso + nutrición + entrenos) para llevar
 * al médico / nutricionista.
 *
 * ponytail: sin ruta que llegue hasta aquí. Se conserva porque los agregados son
 * la parte cara y la fase 1.5 los necesita enteros; lo que se borró fue cómo se
 * entraba: una cookie propia, en claro y sin Secure, que además estaba rota. Al
 * republicarlo hay que resolver dos cosas que antes no lo estaban: de dónde sale
 * el usuario (URL::temporarySignedRoute firmando el id, no $request->user(), que
 * en una ruta web sin sesión viene vacío) y que el enlace caduque solo.
 */
class ReportController extends Controller
{
    private const MODE_LABELS = [
        'gym' => 'Gimnasio', 'home' => 'Casa', 'calisthenics' => 'Calistenia', 'swimming' => 'Natación',
    ];

    public function show(Request $request)
    {
        return view('reports.health', ['r' => $this->build($request)]);
    }

    public function pdf(Request $request)
    {
        $r = $this->build($request);
        $name = 'informe-fitloop-' . $r['range']['from'] . '_' . $r['range']['to'] . '.pdf';

        return Pdf::loadView('reports.health-pdf', ['r' => $r])
            ->setPaper('a4')
            ->download($name);
    }

    /** Construye todos los datos del informe para un rango de fechas. */
    private function build(Request $request): array
    {
        $user = $request->user();
        $uid  = $user->id;

        // Rango: por defecto últimos 30 días. date_from/date_to lo sobreescriben.
        $to = $request->filled('date_to') ? $this->parse($request->query('date_to')) : Carbon::today();
        $from = $request->filled('date_from') ? $this->parse($request->query('date_from')) : $to->copy()->subDays(29);
        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }
        $periodDays = $from->diffInDays($to) + 1;
        $f = $from->toDateString();
        $t = $to->toDateString();

        // ── Nutrición (sumas por día → medias sobre días registrados) ──
        $meals = MealLog::where('user_id', $uid)
            ->whereBetween('date', [$f, $t])
            ->selectRaw('date, SUM(calories) as kcal, SUM(protein) as protein, SUM(carbs) as carbs, SUM(fat) as fat, SUM(fiber) as fiber, SUM(sugar) as sugar')
            ->groupBy('date')->orderBy('date')->get();

        $loggedDays = $meals->count();
        $avg = fn(string $k) => $loggedDays ? round($meals->avg($k)) : 0;

        $waterAvg = (int) round(
            WaterLog::where('user_id', $uid)->whereBetween('date', [$f, $t])->sum('amount_ml')
            / max(1, $loggedDays ?: $periodDays)
        );

        $goal = NutritionGoal::where('user_id', $uid)->first();
        $goalKcal = $goal?->daily_calories;
        $adherence = $goalKcal ? round(($avg('kcal') / $goalKcal) * 100) : null;

        // ── Entrenos ──
        $workouts = Workout::where('user_id', $uid)->where('mode', '!=', 'pasos')
            ->whereBetween('date', [$f, $t . ' 23:59:59'])
            ->get(['date', 'mode', 'duration_minutes']);

        $totalMin = (int) $workouts->sum('duration_minutes');
        $byMode = $workouts->groupBy('mode')->map(fn($items, $mode) => [
            'label'   => self::MODE_LABELS[$mode] ?? ucfirst($mode),
            'count'   => $items->count(),
            'minutes' => (int) $items->sum('duration_minutes'),
        ])->sortByDesc('count')->values()->all();

        // ── Peso + IMC ──
        $weightLogs = WeightLog::where('user_id', $uid)
            ->whereBetween('date', [$f, $t])->orderBy('date')->get(['date', 'weight']);
        $weightStart = $weightLogs->first()?->weight;
        $weightEnd   = $weightLogs->last()?->weight ?? $user->weight;
        $currentWeight = $weightEnd ?? $user->weight;

        $imc = null;
        $imcCat = null;
        if ($currentWeight && $user->height) {
            $m = $user->height / 100;
            $imc = round($currentWeight / ($m * $m), 1);
            $imcCat = match (true) {
                $imc < 18.5 => 'Bajo peso',
                $imc < 25   => 'Normopeso',
                $imc < 30   => 'Sobrepeso',
                default     => 'Obesidad',
            };
        }

        return [
            'patient' => [
                'name'   => $user->name,
                'age'    => $user->age,
                'gender' => match ($user->gender) {
                    'male' => 'Hombre', 'female' => 'Mujer', default => '—',
                },
                'height' => $user->height,
                'weight' => $currentWeight,
                'imc'    => $imc,
                'imc_cat' => $imcCat,
            ],
            'range' => [
                'from'  => $f,
                'to'    => $t,
                'days'  => $periodDays,
                'label' => $from->locale('es')->isoFormat('D MMM YYYY') . ' – ' . $to->locale('es')->isoFormat('D MMM YYYY'),
                'generated' => Carbon::now()->locale('es')->isoFormat('D MMMM YYYY, HH:mm'),
            ],
            'weight' => [
                'start'  => $weightStart,
                'end'    => $weightEnd,
                'change' => ($weightStart !== null && $weightEnd !== null) ? round($weightEnd - $weightStart, 1) : null,
                'logs'   => $weightLogs->map(fn($w) => ['date' => $w->date, 'weight' => (float) $w->weight])->all(),
            ],
            'nutrition' => [
                'logged_days' => $loggedDays,
                'period_days' => $periodDays,
                'avg_kcal'    => $avg('kcal'),
                'avg_protein' => $avg('protein'),
                'avg_carbs'   => $avg('carbs'),
                'avg_fat'     => $avg('fat'),
                'avg_fiber'   => $avg('fiber'),
                'avg_sugar'   => $avg('sugar'),
                'avg_water'   => $waterAvg,
                'goal_kcal'   => $goalKcal,
                'adherence'   => $adherence,
                'days'        => $meals->map(fn($d) => [
                    'date'    => $d->date,
                    'kcal'    => round($d->kcal),
                    'protein' => round($d->protein),
                    'carbs'   => round($d->carbs),
                    'fat'     => round($d->fat),
                    'fiber'   => round($d->fiber, 1),
                    'sugar'   => round($d->sugar, 1),
                ])->all(),
            ],
            'workouts' => [
                'total'       => $workouts->count(),
                'minutes'     => $totalMin,
                'active_days' => $workouts->pluck('date')->map(fn($d) => Carbon::parse($d)->toDateString())->unique()->count(),
                'avg_min'     => $workouts->count() ? (int) round($totalMin / $workouts->count()) : 0,
                'by_mode'     => $byMode,
            ],
        ];
    }

    private function parse(string $value): Carbon
    {
        try {
            return Carbon::parse($value)->startOfDay();
        } catch (\Throwable $e) {
            abort(422, 'Fecha inválida.');
        }
    }
}
