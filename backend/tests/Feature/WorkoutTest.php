<?php

namespace Tests\Feature;

use App\Models\ExerciseSet;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        return User::factory()->createOne($attributes);
    }

    // ──────────────────────────────────────────────────────────
    // INDEX
    // ──────────────────────────────────────────────────────────

    public function test_index_retorna_lista_de_workouts_del_usuario()
    {
        $user  = $this->createUser();
        $other = $this->createUser();

        Workout::factory()->count(3)->create(['user_id' => $user->id]);
        Workout::factory()->count(2)->create(['user_id' => $other->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/workouts');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(3, $data);
    }

    public function test_index_no_retorna_workouts_de_otros_usuarios()
    {
        $user  = $this->createUser();
        $other = $this->createUser();

        Workout::factory()->count(2)->create(['user_id' => $other->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/workouts');
        $response->assertStatus(200);
        $this->assertCount(0, $response->json('data'));
    }

    public function test_index_filtra_por_modo()
    {
        $user = $this->createUser();

        Workout::factory()->create(['user_id' => $user->id, 'mode' => 'gym']);
        Workout::factory()->create(['user_id' => $user->id, 'mode' => 'home']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/workouts?mode=gym');
        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('gym', $response->json('data.0.mode'));
    }

    public function test_index_filtra_por_rango_de_fechas()
    {
        $user = $this->createUser();

        Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-03-01']);
        Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-03-15']);
        Workout::factory()->create(['user_id' => $user->id, 'date' => '2026-04-01']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/workouts?date_from=2026-03-10&date_to=2026-03-31');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        // La API devuelve instantes en UTC. Con la aplicación en Europe/Madrid, la
        // medianoche del 15 de marzo (CET, +01:00) es el 14 a las 23:00 UTC. Quien
        // consuma esto tiene que convertir a Madrid antes de quedarse con el día.
        $this->assertEquals('2026-03-14T23:00:00.000000Z', $response->json('data.0.date'));
    }

    public function test_index_all_devuelve_todos_los_resultados_sin_paginacion()
    {
        $user = $this->createUser();

        Workout::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/workouts?all=1');

        $response->assertStatus(200);
        $this->assertCount(3, $response->json());
    }

    public function test_index_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/workouts')->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // SHOW
    // ──────────────────────────────────────────────────────────

    public function test_show_retorna_workout_propio()
    {
        $user    = $this->createUser();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/workouts/{$workout->id}");
        $response->assertStatus(200)->assertJson(['id' => $workout->id]);
    }

    public function test_show_retorna_403_si_el_workout_es_de_otro_usuario()
    {
        $user    = $this->createUser();
        $other   = $this->createUser();
        $workout = Workout::factory()->create(['user_id' => $other->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/workouts/{$workout->id}");
        $response->assertStatus(403);
    }

    // ──────────────────────────────────────────────────────────
    // STORE
    // ──────────────────────────────────────────────────────────

    public function test_store_crea_workout_valido()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/workouts', [
            'mode'             => 'gym',
            'date'             => '2026-03-31',
            'duration_minutes' => 60,
            'exercises'        => [
                ['name' => 'Sentadilla', 'sets' => [
                    ['reps' => 10, 'weight_kg' => 80],
                    ['reps' => 10, 'weight_kg' => 80],
                    ['reps' => 10, 'weight_kg' => 80],
                ]],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('workouts', ['user_id' => $user->id, 'mode' => 'gym']);
        $this->assertDatabaseHas('exercise_sets', ['name' => 'Sentadilla', 'weight_kg' => 80]);
    }

    public function test_store_detecta_nuevo_record_personal()
    {
        $user = $this->createUser();

        // Primer workout con 70kg
        $this->actingAs($user, 'sanctum')->postJson('/api/workouts', [
            'mode'             => 'gym',
            'date'             => '2026-03-30',
            'duration_minutes' => 60,
            'exercises'        => [
                ['name' => 'Press Banca', 'sets' => [['reps' => 8, 'weight_kg' => 70]]],
            ],
        ]);

        // Segundo workout con 75kg → debe detectar nuevo PR
        $response = $this->actingAs($user, 'sanctum')->postJson('/api/workouts', [
            'mode'             => 'gym',
            'date'             => '2026-03-31',
            'duration_minutes' => 60,
            'exercises'        => [
                ['name' => 'Press Banca', 'sets' => [['reps' => 8, 'weight_kg' => 75]]],
            ],
        ]);

        $response->assertStatus(201);
        $newRecords = $response->json('new_records');
        $this->assertCount(1, $newRecords);
        $this->assertEquals('Press Banca', $newRecords[0]['name']);
        $this->assertEquals(75, $newRecords[0]['weight_kg']);
        $this->assertEquals(70, $newRecords[0]['previous_pr']);
    }

    public function test_store_primer_ejercicio_con_peso_se_detecta_como_is_first()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/workouts', [
            'mode'             => 'gym',
            'date'             => '2026-03-31',
            'duration_minutes' => 45,
            'exercises'        => [
                ['name' => 'Sentadilla', 'sets' => [['reps' => 10, 'weight_kg' => 60]]],
            ],
        ]);

        $response->assertStatus(201);
        $newRecords = $response->json('new_records');
        $this->assertCount(1, $newRecords);
        $this->assertTrue($newRecords[0]['is_first']);
    }

    public function test_store_sin_modo_retorna_422()
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')->postJson('/api/workouts', [
            'date'             => '2026-03-31',
            'duration_minutes' => 60,
        ])->assertStatus(422)->assertJsonValidationErrors(['mode']);
    }

    public function test_store_con_modo_invalido_retorna_422()
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')->postJson('/api/workouts', [
            'mode'             => 'yoga',
            'date'             => '2026-03-31',
            'duration_minutes' => 60,
        ])->assertStatus(422)->assertJsonValidationErrors(['mode']);
    }

    public function test_store_sin_fecha_retorna_422()
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')->postJson('/api/workouts', [
            'mode'             => 'gym',
            'duration_minutes' => 60,
        ])->assertStatus(422)->assertJsonValidationErrors(['date']);
    }

    public function test_store_duracion_mayor_600_retorna_422()
    {
        $user = $this->createUser();

        $this->actingAs($user, 'sanctum')->postJson('/api/workouts', [
            'mode'             => 'gym',
            'date'             => '2026-03-31',
            'duration_minutes' => 999,
        ])->assertStatus(422)->assertJsonValidationErrors(['duration_minutes']);
    }

    public function test_store_mas_de_50_ejercicios_retorna_422()
    {
        $user      = $this->createUser();
        $exercises = array_fill(0, 51, ['name' => 'Sentadilla', 'sets' => 3, 'reps' => 10]);

        $this->actingAs($user, 'sanctum')->postJson('/api/workouts', [
            'mode'             => 'gym',
            'date'             => '2026-03-31',
            'duration_minutes' => 60,
            'exercises'        => $exercises,
        ])->assertStatus(422)->assertJsonValidationErrors(['exercises']);
    }

    // ──────────────────────────────────────────────────────────
    // UPDATE
    // ──────────────────────────────────────────────────────────

    public function test_update_modifica_notas_del_workout_propio()
    {
        $user    = $this->createUser();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/workouts/{$workout->id}", [
            'notes' => 'Buena sesión',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('workouts', ['id' => $workout->id, 'notes' => 'Buena sesión']);
    }

    public function test_update_retorna_403_si_es_de_otro_usuario()
    {
        $user    = $this->createUser();
        $other   = $this->createUser();
        $workout = Workout::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')->putJson("/api/workouts/{$workout->id}", [
            'notes' => 'Intento de edición',
        ])->assertStatus(403);
    }

    public function test_update_notas_demasiado_largas_retorna_422()
    {
        $user    = $this->createUser();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user, 'sanctum')->putJson("/api/workouts/{$workout->id}", [
            'notes' => str_repeat('a', 1001),
        ])->assertStatus(422)->assertJsonValidationErrors(['notes']);
    }

    // ──────────────────────────────────────────────────────────
    // DESTROY
    // ──────────────────────────────────────────────────────────

    public function test_destroy_elimina_workout_propio()
    {
        $user    = $this->createUser();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/workouts/{$workout->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('workouts', ['id' => $workout->id]);
    }

    public function test_destroy_retorna_403_si_es_de_otro_usuario()
    {
        $user    = $this->createUser();
        $other   = $this->createUser();
        $workout = Workout::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/workouts/{$workout->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('workouts', ['id' => $workout->id]);
    }

    public function test_destroy_elimina_en_cascada_los_exercise_sets()
    {
        $user    = $this->createUser();
        $workout = Workout::factory()->create(['user_id' => $user->id]);
        ExerciseSet::factory()->create(['workout_id' => $workout->id]);

        $this->actingAs($user, 'sanctum')->deleteJson("/api/workouts/{$workout->id}");

        $this->assertDatabaseMissing('exercise_sets', ['workout_id' => $workout->id]);
    }
}
