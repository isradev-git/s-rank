<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WaterLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controlador de hidratación.
 *
 * Endpoints:
 *   GET    /api/water?date=YYYY-MM-DD   → resumen del día (total + entradas + objetivo)
 *   POST   /api/water                   → añadir agua (body: amount_ml, date)
 *   DELETE /api/water/{id}              → eliminar una entrada concreta
 *   PUT    /api/water/goal              → actualizar objetivo diario (body: goal_ml)
 */
class WaterController extends Controller
{
    /**
     * Devuelve el resumen de agua del día:
     * - total_ml: suma de todo lo bebido ese día
     * - goal_ml: objetivo diario del usuario (default 2000ml)
     * - entries: listado de cada entrada con id y ml (para poder borrar)
     * - pct: porcentaje de objetivo completado (0–100, puede superar 100)
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $date = $request->string('date')->isNotEmpty()
            ? Carbon::parse($request->string('date'))
            : Carbon::today();

        // Recuperamos todas las entradas del día
        $entries = WaterLog::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->orderBy('created_at')
            ->get(['id', 'amount_ml', 'created_at']);

        $totalMl  = $entries->sum('amount_ml');
        $goalMl   = (int) ($user->water_goal_ml ?? 2000);
        $pct      = $goalMl > 0 ? min(round(($totalMl / $goalMl) * 100), 100) : 0;

        return response()->json([
            'date'     => $date->format('Y-m-d'),
            'total_ml' => $totalMl,
            'goal_ml'  => $goalMl,
            'pct'      => $pct,
            'entries'  => $entries,
        ]);
    }

    /**
     * Añade una entrada de agua para el usuario.
     *
     * Body esperado:
     *   - amount_ml: integer (mínimo 1, máximo 2000). Default 250.
     *   - date: string YYYY-MM-DD (opcional, default hoy)
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount_ml' => 'required|integer|min:1|max:2000',
            'date'      => 'nullable|date',
        ]);

        $log = WaterLog::create([
            'user_id'   => $request->user()->id,
            'date'      => $validated['date'] ?? now()->toDateString(),
            'amount_ml' => $validated['amount_ml'],
        ]);

        // Recalculamos el total para devolver estado actualizado
        $user    = $request->user();
        $goalMl  = (int) ($user->water_goal_ml ?? 2000);
        $total   = WaterLog::where('user_id', $user->id)
            ->whereDate('date', $log->date)
            ->sum('amount_ml');

        return response()->json([
            'message'  => 'Agua registrada.',
            'entry'    => $log,
            'total_ml' => $total,
            'goal_ml'  => $goalMl,
            'pct'      => $goalMl > 0 ? min(round(($total / $goalMl) * 100), 100) : 0,
        ], 201);
    }

    /**
     * Elimina una entrada específica de agua del usuario.
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        $log = WaterLog::where('id', $id)
            ->where('user_id', $request->user()->id)
            ->first();

        if (!$log) {
            return response()->json(['message' => 'Entrada de agua no encontrada.'], 404);
        }

        $date = $log->date;
        $log->delete();

        // Recalculamos el total actualizado
        $user   = $request->user();
        $goalMl = (int) ($user->water_goal_ml ?? 2000);
        $total  = WaterLog::where('user_id', $user->id)
            ->whereDate('date', $date)
            ->sum('amount_ml');

        return response()->json([
            'message'  => 'Entrada eliminada.',
            'total_ml' => $total,
            'goal_ml'  => $goalMl,
            'pct'      => $goalMl > 0 ? min(round(($total / $goalMl) * 100), 100) : 0,
        ]);
    }

    /**
     * Actualiza el objetivo diario de agua del usuario.
     *
     * Body esperado:
     *   - goal_ml: integer (mínimo 500ml, máximo 6000ml)
     */
    public function updateGoal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'goal_ml' => 'required|integer|min:500|max:6000',
        ]);

        $user = $request->user();
        $user->update(['water_goal_ml' => $validated['goal_ml']]);

        return response()->json([
            'message' => 'Objetivo actualizado.',
            'goal_ml' => $user->water_goal_ml,
        ]);
    }
}
