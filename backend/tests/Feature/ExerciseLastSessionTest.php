<?php

namespace Tests\Feature;

use App\Models\ExerciseSet;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExerciseLastSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_sets_of_most_recent_session(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $old = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-06-01']);
        ExerciseSet::factory()->create(['workout_id' => $old->id, 'name' => 'Press Banca', 'weight_kg' => 70, 'reps' => 10, 'sets' => 1]);

        $recent = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-06-10']);
        ExerciseSet::factory()->create(['workout_id' => $recent->id, 'name' => 'Press Banca', 'weight_kg' => 80, 'reps' => 8, 'sets' => 1]);
        ExerciseSet::factory()->create(['workout_id' => $recent->id, 'name' => 'Press Banca', 'weight_kg' => 80, 'reps' => 7, 'sets' => 1]);

        $res = $this->getJson('/api/exercises/last-session?name=Press Banca');

        $res->assertOk();
        $res->assertJsonCount(2);
        $res->assertJsonPath('0.weight_kg', 80);
        $res->assertJsonPath('0.reps', 8);
    }

    public function test_returns_empty_when_no_history(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $res = $this->getJson('/api/exercises/last-session?name=Inexistente');

        $res->assertOk();
        $res->assertExactJson([]);
    }

    public function test_progress_returns_one_point_per_date_best_set(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $w = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-06-10']);
        ExerciseSet::factory()->create(['workout_id' => $w->id, 'name' => 'Sentadilla', 'weight_kg' => 100, 'reps' => 5, 'sets' => 1]);
        ExerciseSet::factory()->create(['workout_id' => $w->id, 'name' => 'Sentadilla', 'weight_kg' => 110, 'reps' => 3, 'sets' => 1]);

        $res = $this->getJson('/api/exercises/progress?name=Sentadilla');

        $res->assertOk();
        $res->assertJsonCount(1);
        $res->assertJsonPath('0.weight_kg', 110);
    }
}
