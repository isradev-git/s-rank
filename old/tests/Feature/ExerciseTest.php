<?php

namespace Tests\Feature;

use App\Models\ExerciseSet;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────
    // INDEX — lista hardcodeada de ejercicios
    // ──────────────────────────────────────────────────────────

    public function test_index_retorna_lista_de_ejercicios()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/exercises');

        $response->assertStatus(200);
        $exercises = $response->json();
        $this->assertIsArray($exercises);
        $this->assertNotEmpty($exercises);
        $this->assertArrayHasKey('name', $exercises[0]);
        $this->assertArrayHasKey('category', $exercises[0]);
    }

    public function test_index_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/exercises')->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // HISTORY
    // ──────────────────────────────────────────────────────────

    public function test_history_retorna_ultimo_set_del_ejercicio()
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-03-30']);

        ExerciseSet::factory()->create([
            'workout_id' => $workout->id,
            'name'       => 'Press Banca',
            'weight_kg'  => 80,
            'reps'        => 5,
            'sets'        => 3,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/exercises/history?name=Press+Banca');

        $response->assertStatus(200)
            ->assertJsonStructure(['last_date', 'weight_kg', 'reps', 'sets']);
        $this->assertEquals(80, $response->json('weight_kg'));
    }

    public function test_history_sin_nombre_retorna_vacio()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/exercises/history');

        // response()->json(null) → el helper de tests Laravel lo normaliza a []
        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    public function test_history_ejercicio_inexistente_retorna_vacio()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/exercises/history?name=EjercicioNoExiste');

        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    public function test_history_no_retorna_datos_de_otros_usuarios()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $workout = Workout::factory()->create(['user_id' => $other->id]);
        ExerciseSet::factory()->create([
            'workout_id' => $workout->id,
            'name'       => 'Sentadilla',
            'weight_kg'  => 100,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/exercises/history?name=Sentadilla');

        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    // ──────────────────────────────────────────────────────────
    // SUGGESTIONS
    // ──────────────────────────────────────────────────────────

    public function test_suggestions_retorna_nombres_filtrados_y_ordenados_por_uso()
    {
        $user = User::factory()->create();

        $w1 = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-03-20']);
        $w2 = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-03-21']);
        $w3 = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-03-22']);

        ExerciseSet::factory()->create(['workout_id' => $w1->id, 'name' => 'Press Banca']);
        ExerciseSet::factory()->create(['workout_id' => $w2->id, 'name' => 'Press Banca']);
        ExerciseSet::factory()->create(['workout_id' => $w3->id, 'name' => 'Press Militar']);
        ExerciseSet::factory()->create(['workout_id' => $w3->id, 'name' => 'Sentadilla']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/exercises/suggestions?q=press');

        $response->assertStatus(200);
        $suggestions = $response->json();

        $this->assertEquals(['Press Banca', 'Press Militar'], $suggestions);
    }

    public function test_suggestions_no_incluye_datos_de_otros_usuarios()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $wOther = Workout::factory()->create(['user_id' => $other->id]);
        ExerciseSet::factory()->create(['workout_id' => $wOther->id, 'name' => 'Peso Muerto']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/exercises/suggestions?q=peso');

        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    public function test_suggestions_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/exercises/suggestions?q=press')->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // PROGRESS
    // ──────────────────────────────────────────────────────────

    public function test_progress_retorna_historial_del_ejercicio()
    {
        $user = User::factory()->create();

        $w1 = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-03-20']);
        $w2 = Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-03-25']);

        ExerciseSet::factory()->create(['workout_id' => $w1->id, 'name' => 'Sentadilla', 'weight_kg' => 80]);
        ExerciseSet::factory()->create(['workout_id' => $w2->id, 'name' => 'Sentadilla', 'weight_kg' => 85]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/exercises/progress?name=Sentadilla');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    // ──────────────────────────────────────────────────────────
    // RECORDS
    // ──────────────────────────────────────────────────────────

    public function test_records_retorna_pr_de_cada_ejercicio()
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        ExerciseSet::factory()->create(['workout_id' => $workout->id, 'name' => 'Sentadilla', 'weight_kg' => 80]);
        ExerciseSet::factory()->create(['workout_id' => $workout->id, 'name' => 'Sentadilla', 'weight_kg' => 100]);
        ExerciseSet::factory()->create(['workout_id' => $workout->id, 'name' => 'Press Banca', 'weight_kg' => 70]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/exercises/records');

        $response->assertStatus(200);
        $records = $response->json();
        $this->assertCount(2, $records);

        // El primer registro (mayor peso) debe ser Sentadilla con 100kg
        $sentadilla = collect($records)->firstWhere('name', 'Sentadilla');
        $this->assertEquals(100, $sentadilla['max_weight']);
    }

    public function test_records_no_incluye_ejercicios_de_otros_usuarios()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $workout = Workout::factory()->create(['user_id' => $other->id]);
        ExerciseSet::factory()->create(['workout_id' => $workout->id, 'name' => 'Sentadilla', 'weight_kg' => 120]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/exercises/records');

        $response->assertStatus(200);
        $this->assertEmpty($response->json());
    }

    public function test_records_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/exercises/records')->assertStatus(401);
    }
}
