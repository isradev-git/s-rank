<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupplementLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplementController extends Controller
{
    private const SUPPLEMENTS = [
        'multivitaminas' => ['name' => 'Multivitaminas', 'dose' => '1 pastilla'],
        'omega3'         => ['name' => 'Omega 3', 'dose' => '1 capsula'],
        'vitamina_d'     => ['name' => 'Vitamina D3', 'dose' => '1 pastilla'],
        'magnesio'       => ['name' => 'Magnesio', 'dose' => '1 pastilla'],
    ];

    public function index(Request $request): JsonResponse
    {
        $date = $request->string('date')->isNotEmpty()
            ? Carbon::parse($request->string('date'))
            : Carbon::today();

        $rows = SupplementLog::where('user_id', $request->user()->id)
            ->whereDate('date', $date)
            ->get(['supplement_key', 'taken'])
            ->keyBy('supplement_key');

        $items = collect(self::SUPPLEMENTS)->map(function (array $meta, string $key) use ($rows) {
            return [
                'key' => $key,
                'name' => $meta['name'],
                'dose' => $meta['dose'],
                'taken' => (bool) optional($rows->get($key))->taken,
            ];
        })->values();

        return response()->json([
            'date' => $date->format('Y-m-d'),
            'items' => $items,
            'taken_count' => $items->where('taken', true)->count(),
            'total_count' => count(self::SUPPLEMENTS),
        ]);
    }

    public function upsert(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'supplement_key' => 'required|string|in:multivitaminas,omega3,vitamina_d,magnesio',
            'taken' => 'required|boolean',
        ]);

        $normalizedDate = Carbon::parse($validated['date'])->startOfDay()->toDateTimeString();

        SupplementLog::query()->upsert(
            [[
                'user_id' => $request->user()->id,
                'date' => $normalizedDate,
                'supplement_key' => $validated['supplement_key'],
                'taken' => (bool) $validated['taken'],
                'created_at' => now(),
                'updated_at' => now(),
            ]],
            ['user_id', 'date', 'supplement_key'],
            ['taken', 'updated_at']
        );

        return response()->json(['message' => 'Suplemento actualizado.']);
    }

    public function reset(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date' => 'required|date',
        ]);

        SupplementLog::where('user_id', $request->user()->id)
            ->whereDate('date', $validated['date'])
            ->delete();

        return response()->json(['message' => 'Checklist reiniciada.']);
    }
}
