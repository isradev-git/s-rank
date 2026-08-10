<?php

namespace Tests\Feature;

use App\Models\ExerciseSet;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WorkoutLoggingTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'mode' => 'gym',
            'date' => '2026-06-16',
            'duration_minutes' => 45,
            'exercises' => [[
                'name' => 'Press Banca',
                'sets' => [
                    ['weight_kg' => 80, 'reps' => 8, 'rpe' => 7, 'rest_seconds' => 90],
                    ['weight_kg' => 80, 'reps' => 7, 'rpe' => 8, 'rest_seconds' => 90],
                    ['weight_kg' => 85, 'reps' => 6, 'rpe' => 9, 'rest_seconds' => 120],
                ],
            ]],
        ], $overrides);
    }

    public function test_store_creates_one_row_per_set(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/workouts', $this->payload());

        $res->assertCreated();
        $this->assertSame(1, Workout::where('user_id', $user->id)->count());
        $rows = ExerciseSet::where('name', 'Press Banca')->get();
        $this->assertCount(3, $rows);
        $this->assertTrue($rows->every(fn ($r) => (int) $r->sets === 1));
        $this->assertEqualsCanonicalizing([8, 7, 6], $rows->pluck('reps')->all());
    }

    public function test_store_detects_pr_from_best_set(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $old = Workout::factory()->create(['user_id' => $user->id, 'mode' => 'gym']);
        ExerciseSet::factory()->create(['workout_id' => $old->id, 'name' => 'Press Banca', 'weight_kg' => 82.5, 'reps' => 5, 'sets' => 1]);

        $res = $this->postJson('/api/workouts', $this->payload());

        $res->assertCreated();
        $records = $res->json('new_records');
        $this->assertCount(1, $records);
        $this->assertSame('Press Banca', $records[0]['name']);
        $this->assertEquals(85.0, $records[0]['weight_kg']);
        $this->assertEquals(82.5, $records[0]['previous_pr']);
    }

    public function test_store_accepts_bodyweight_set_without_weight(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $res = $this->postJson('/api/workouts', $this->payload([
            'mode' => 'calisthenics',
            'exercises' => [[
                'name' => 'Dominadas',
                'sets' => [['reps' => 10], ['reps' => 8]],
            ]],
        ]));

        $res->assertCreated();
        $rows = ExerciseSet::where('name', 'Dominadas')->get();
        $this->assertCount(2, $rows);
        $this->assertNull($rows->first()->weight_kg);
        $this->assertEmpty($res->json('new_records'));
    }
}
