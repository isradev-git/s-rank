<?php

namespace Tests\Feature;

use App\Models\ExerciseSet;
use App\Models\MealLog;
use App\Models\NutritionGoal;
use App\Models\SupplementLog;
use App\Models\User;
use App\Models\WaterLog;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function createUser(array $attributes = []): User
    {
        return User::factory()->createOne($attributes);
    }

    // ──────────────────────────────────────────────────────────
    // STATS
    // ──────────────────────────────────────────────────────────

    public function test_stats_retorna_estructura_correcta()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'total_workouts',
                'total_minutes',
                'weekly_streak',
                'monthly_count',
                'weekly_goal',
                'tdee',
                'calories_burned_today',
            ]);
    }

    public function test_stats_sin_workouts_retorna_ceros()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/stats');

        $response->assertStatus(200)
            ->assertJson([
                'total_workouts'        => 0,
                'total_minutes'         => 0,
                'weekly_streak'         => 0,
                'monthly_count'         => 0,
                'calories_burned_today' => 0,
            ]);
    }

    public function test_stats_cuenta_total_workouts_correctamente()
    {
        $user = $this->createUser();
        Workout::factory()->count(5)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/stats');

        $response->assertStatus(200)->assertJson(['total_workouts' => 5]);
    }

    public function test_stats_no_cuenta_workouts_de_otros_usuarios()
    {
        $user  = $this->createUser();
        $other = $this->createUser();

        Workout::factory()->count(3)->create(['user_id' => $other->id]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/stats');

        $response->assertStatus(200)->assertJson(['total_workouts' => 0]);
    }

    public function test_stats_calcula_racha_de_dias_consecutivos()
    {
        $user = $this->createUser();

        // 3 días consecutivos terminando hoy
        Workout::factory()->create(['user_id' => $user->id, 'date' => now()]);
        Workout::factory()->create(['user_id' => $user->id, 'date' => now()->subDay()]);
        Workout::factory()->create(['user_id' => $user->id, 'date' => now()->subDays(2)]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/stats');

        $response->assertStatus(200)->assertJson(['weekly_streak' => 3]);
    }

    public function test_stats_calcula_tdee_si_el_usuario_tiene_datos()
    {
        $user = $this->createUser([
            'weight' => 75,
            'height' => 175,
            'age'    => 30,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/stats');

        $response->assertStatus(200);
        $this->assertNotNull($response->json('tdee'));
        $this->assertGreaterThan(0, $response->json('tdee'));
    }

    public function test_stats_tdee_es_null_sin_datos_de_perfil()
    {
        $user = $this->createUser([
            'weight' => null,
            'height' => null,
            'age'    => null,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/stats');

        $response->assertStatus(200)->assertJson(['tdee' => null]);
    }

    public function test_stats_calcula_calorias_quemadas_hoy()
    {
        $user = $this->createUser(['weight' => 80]);

        // Entreno de hoy: 60 minutos en gym
        Workout::factory()->create([
            'user_id'          => $user->id,
            'date'             => now(),
            'mode'             => 'gym',
            'duration_minutes' => 60,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/stats');

        $response->assertStatus(200);
        // MET gym=5.5, peso=80, 1 hora → 5.5 * 80 * 1 = 440 kcal
        $this->assertEquals(440, $response->json('calories_burned_today'));
    }

    public function test_stats_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/stats')->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // HEATMAP
    // ──────────────────────────────────────────────────────────

    public function test_heatmap_retorna_estructura_correcta()
    {
        $user = $this->createUser();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/stats/heatmap?year=2026');

        $response->assertStatus(200)
            ->assertJsonStructure(['year', 'data'])
            ->assertJson(['year' => 2026]);
    }

    public function test_heatmap_incluye_workouts_del_año_solicitado()
    {
        $user = $this->createUser();

        Workout::factory()->create([
            'user_id'          => $user->id,
            'date'             => '2026-03-15',
            'duration_minutes' => 45,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/stats/heatmap?year=2026');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertArrayHasKey('2026-03-15', $data);
        $this->assertEquals(1, $data['2026-03-15']['count']);
    }

    public function test_heatmap_no_incluye_workouts_de_otros_usuarios()
    {
        $user  = $this->createUser();
        $other = $this->createUser();

        Workout::factory()->create(['user_id' => $other->id, 'date' => '2026-03-15']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/stats/heatmap?year=2026');

        $response->assertStatus(200);
        $this->assertEmpty($response->json('data'));
    }

    public function test_heatmap_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/stats/heatmap')->assertStatus(401);
    }

    public function test_calendar_retorna_resumen_y_dias_para_semana()
    {
        $user = $this->createUser();

        Workout::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-04-15',
            'duration_minutes' => 50,
            'mode' => 'gym',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/calendar?period=week&date=2026-04-15');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'period',
                'label',
                'anchor_date',
                'start_date',
                'end_date',
                'summary' => ['total_workouts', 'total_minutes', 'active_days', 'average_duration'],
                'days',
            ])
            ->assertJson([
                'period' => 'week',
                'summary' => [
                    'total_workouts' => 1,
                    'total_minutes' => 50,
                    'active_days' => 1,
                    'average_duration' => 50,
                ],
            ]);

        $this->assertCount(7, $response->json('days'));
    }

    public function test_calendar_acepta_periodo_year()
    {
        $user = $this->createUser();

        Workout::factory()->create([
            'user_id' => $user->id,
            'date' => '2026-02-10',
            'duration_minutes' => 30,
            'mode' => 'gym',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/stats/calendar?period=year&date=2026-06-01');

        $response->assertStatus(200)
            ->assertJson([
                'period' => 'year',
                'summary' => [
                    'total_workouts' => 1,
                    'total_minutes' => 30,
                ],
            ]);
    }

    public function test_reports_retorna_agregados_y_top_ejercicios()
    {
        $user = $this->createUser();

        NutritionGoal::create([
            'user_id' => $user->id,
            'daily_calories' => 2200,
            'target_protein' => 160,
            'target_carbs' => 250,
            'target_fat' => 70,
            'target_fiber' => 25,
            'goal_type' => 'maintain',
            'meals_per_day' => 4,
        ]);

        $w1 = Workout::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDays(2),
            'duration_minutes' => 60,
            'mode' => 'gym',
        ]);

        $w2 = Workout::factory()->create([
            'user_id' => $user->id,
            'date' => now()->subDay(),
            'duration_minutes' => 40,
            'mode' => 'home',
        ]);

        ExerciseSet::factory()->create([
            'workout_id' => $w1->id,
            'name' => 'Sentadilla',
            'sets' => 3,
            'reps' => 5,
            'weight_kg' => 100,
        ]);

        ExerciseSet::factory()->create([
            'workout_id' => $w2->id,
            'name' => 'Sentadilla',
            'sets' => 4,
            'reps' => 8,
            'weight_kg' => 60,
        ]);

        MealLog::create([
            'user_id' => $user->id,
            'date' => now()->subDays(2)->toDateString(),
            'meal_type' => 'lunch',
            'custom_food_name' => 'Arroz con pollo',
            'quantity_grams' => 350,
            'calories' => 820,
            'protein' => 52,
            'carbs' => 75,
            'fat' => 24,
            'fiber' => 8,
            'sugar' => 4,
        ]);

        WaterLog::create([
            'user_id' => $user->id,
            'date' => now()->subDays(2)->toDateString(),
            'amount_ml' => 600,
        ]);

        SupplementLog::create([
            'user_id' => $user->id,
            'date' => now()->subDays(2)->toDateString(),
            'supplement_key' => 'omega3',
            'taken' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/stats/reports?range=7d');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'range_label',
                'summary' => ['total_workouts', 'total_minutes', 'active_days', 'average_duration', 'total_sets', 'total_volume'],
                'nutrition' => [
                    'summary' => ['total_entries', 'total_calories', 'total_protein', 'goal_daily_calories'],
                    'hydration' => ['total_ml', 'goal_daily_ml'],
                    'supplements' => ['taken_count', 'expected_count'],
                ],
                'by_mode',
                'by_weekday',
                'by_month',
                'top_exercises',
                'recent_workouts',
            ]);

        $response->assertJson([
            'summary' => [
                'total_workouts' => 2,
                'total_minutes' => 100,
                'active_days' => 2,
                'average_duration' => 50,
                'total_sets' => 7,
                'total_volume' => 3420.0,
            ],
            'nutrition' => [
                'summary' => [
                    'total_entries' => 1,
                    'total_calories' => 820.0,
                    'total_protein' => 52.0,
                    'goal_daily_calories' => 2200,
                ],
                'hydration' => [
                    'total_ml' => 600,
                ],
                'supplements' => [
                    'taken_count' => 1,
                ],
            ],
        ]);

        $response->assertJsonStructure([
            'comparison' => [
                'label',
                'previous_range' => ['date_from', 'date_to'],
                'previous_summary' => ['total_workouts', 'total_minutes', 'active_days', 'average_duration'],
                'previous_nutrition' => ['total_calories', 'average_calories', 'total_protein', 'water_total_ml', 'supplement_taken_count'],
                'delta' => ['total_workouts', 'total_minutes', 'active_days', 'average_duration'],
                'change_pct' => ['total_workouts', 'total_minutes', 'active_days', 'average_duration'],
            ],
        ]);

        $topExercise = collect($response->json('top_exercises'))->firstWhere('name', 'Sentadilla');
        $this->assertNotNull($topExercise);
        $this->assertEquals(2, $topExercise['sessions']);
        $this->assertEquals(100, $topExercise['max_weight']);
    }
}
