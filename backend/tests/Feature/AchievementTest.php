<?php

namespace Tests\Feature;

use App\Models\ExerciseSet;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────
    // ESTRUCTURA
    // ──────────────────────────────────────────────────────────

    public function test_index_retorna_estructura_correcta()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/achievements');

        $response->assertStatus(200);
        $achievements = $response->json('achievements');
        $this->assertIsArray($achievements);
        $this->assertNotEmpty($achievements);
        $this->assertArrayHasKey('id', $achievements[0]);
        $this->assertArrayHasKey('name', $achievements[0]);
        $this->assertArrayHasKey('unlocked', $achievements[0]);
        $this->assertArrayHasKey('unlocked_count', $response->json());
        $this->assertArrayHasKey('total_count', $response->json());
    }

    public function test_index_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/achievements')->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // LOGROS — primer_paso
    // ──────────────────────────────────────────────────────────

    public function test_primer_paso_bloqueado_sin_entrenamientos()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/achievements');

        $achievements   = collect($response->json('achievements'));
        $primeropaso    = $achievements->firstWhere('id', 'first_step');

        $this->assertNotNull($primeropaso);
        $this->assertFalse($primeropaso['unlocked']);
    }

    public function test_primer_paso_desbloqueado_con_un_entrenamiento()
    {
        $user = User::factory()->create();
        Workout::factory()->create(['user_id' => $user->id]);

        $response    = $this->actingAs($user, 'sanctum')->getJson('/api/achievements');
        $achievements = collect($response->json('achievements'));
        $primerPaso  = $achievements->firstWhere('id', 'first_step');

        $this->assertTrue($primerPaso['unlocked']);
    }

    // ──────────────────────────────────────────────────────────
    // LOGROS — workouts_10/25/50/100
    // ──────────────────────────────────────────────────────────

    public function test_workouts_10_bloqueado_con_menos_de_10()
    {
        $user = User::factory()->create();
        Workout::factory()->count(5)->create(['user_id' => $user->id]);

        $achievements = collect(
            $this->actingAs($user, 'sanctum')->getJson('/api/achievements')->json('achievements')
        );
        $this->assertFalse($achievements->firstWhere('id', 'workouts_10')['unlocked']);
    }

    public function test_workouts_10_desbloqueado_con_10_o_mas()
    {
        $user = User::factory()->create();
        Workout::factory()->count(10)->create(['user_id' => $user->id]);

        $achievements = collect(
            $this->actingAs($user, 'sanctum')->getJson('/api/achievements')->json('achievements')
        );
        $this->assertTrue($achievements->firstWhere('id', 'workouts_10')['unlocked']);
    }

    // ──────────────────────────────────────────────────────────
    // LOGROS — first_pr
    // ──────────────────────────────────────────────────────────

    public function test_first_pr_bloqueado_sin_ejercicios_con_peso()
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        // Sin peso en los sets
        ExerciseSet::factory()->create([
            'workout_id' => $workout->id,
            'weight_kg'  => null,
        ]);

        $achievements = collect(
            $this->actingAs($user, 'sanctum')->getJson('/api/achievements')->json('achievements')
        );
        $this->assertFalse($achievements->firstWhere('id', 'first_pr')['unlocked']);
    }

    public function test_first_pr_desbloqueado_con_ejercicio_con_peso()
    {
        $user    = User::factory()->create();
        $workout = Workout::factory()->create(['user_id' => $user->id]);

        ExerciseSet::factory()->create([
            'workout_id' => $workout->id,
            'weight_kg'  => 80,
        ]);

        $achievements = collect(
            $this->actingAs($user, 'sanctum')->getJson('/api/achievements')->json('achievements')
        );
        $this->assertTrue($achievements->firstWhere('id', 'first_pr')['unlocked']);
    }

    // ──────────────────────────────────────────────────────────
    // LOGROS — por modo
    // ──────────────────────────────────────────────────────────

    public function test_logro_gym_desbloqueado_con_primer_entrenamiento_gym()
    {
        $user = User::factory()->create();
        Workout::factory()->create(['user_id' => $user->id, 'mode' => 'gym']);

        $achievements = collect(
            $this->actingAs($user, 'sanctum')->getJson('/api/achievements')->json('achievements')
        );
        $gymAchievement = $achievements->firstWhere('id', 'gym_rat');
        $this->assertNotNull($gymAchievement);
        $this->assertTrue($gymAchievement['unlocked']);
    }

    public function test_all_modes_requiere_4_modos_distintos()
    {
        $user = User::factory()->create();

        // Solo 3 modos → all_modes no debe desbloquearse
        Workout::factory()->create(['user_id' => $user->id, 'mode' => 'gym']);
        Workout::factory()->create(['user_id' => $user->id, 'mode' => 'home']);
        Workout::factory()->create(['user_id' => $user->id, 'mode' => 'calisthenics']);

        $achievements = collect(
            $this->actingAs($user, 'sanctum')->getJson('/api/achievements')->json('achievements')
        );
        $allModes = $achievements->firstWhere('id', 'all_modes');
        $this->assertNotNull($allModes);
        $this->assertFalse($allModes['unlocked']);

        // Agregamos el 4to modo
        Workout::factory()->create(['user_id' => $user->id, 'mode' => 'swimming']);

        $achievements2 = collect(
            $this->actingAs($user, 'sanctum')->getJson('/api/achievements')->json('achievements')
        );
        $allModes2 = $achievements2->firstWhere('id', 'all_modes');
        $this->assertTrue($allModes2['unlocked']);
    }

    // ──────────────────────────────────────────────────────────
    // AISLAMIENTO — no mezcla usuarios
    // ──────────────────────────────────────────────────────────

    public function test_logros_no_contabiliza_entrenamientos_de_otros_usuarios()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        // El otro usuario tiene 10 entrenamientos, el nuestro 0
        Workout::factory()->count(10)->create(['user_id' => $other->id]);

        $achievements = collect(
            $this->actingAs($user, 'sanctum')->getJson('/api/achievements')->json('achievements')
        );
        $this->assertFalse($achievements->firstWhere('id', 'first_step')['unlocked']);
        $this->assertFalse($achievements->firstWhere('id', 'workouts_10')['unlocked']);
    }
}
