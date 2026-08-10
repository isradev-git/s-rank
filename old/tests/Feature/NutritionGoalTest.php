<?php

namespace Tests\Feature;

use App\Models\NutritionGoal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NutritionGoalTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────
    // SHOW
    // ──────────────────────────────────────────────────────────

    public function test_show_retorna_has_goal_false_si_no_tiene_objetivo()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/nutrition/goal');

        $response->assertStatus(200)
            ->assertJson(['has_goal' => false])
            ->assertJsonStructure(['goal', 'has_goal']);
    }

    public function test_show_retorna_valores_sugeridos_calculados_si_no_hay_objetivo()
    {
        $user = User::factory()->create([
            'weight' => 75,
            'height' => 175,
            'age'    => 30,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/nutrition/goal');

        $response->assertStatus(200)->assertJson(['has_goal' => false]);
        $goal = $response->json('goal');
        $this->assertArrayHasKey('daily_calories', $goal);
        $this->assertArrayHasKey('target_protein', $goal);
        $this->assertArrayHasKey('target_carbs', $goal);
        $this->assertArrayHasKey('target_fat', $goal);
        $this->assertGreaterThan(0, $goal['daily_calories']);
    }

    public function test_show_retorna_has_goal_true_si_ya_tiene_objetivo()
    {
        $user = User::factory()->create();

        NutritionGoal::create([
            'user_id'        => $user->id,
            'daily_calories' => 2000,
            'target_protein' => 150,
            'target_carbs'   => 225,
            'target_fat'     => 55,
            'goal_type'      => 'maintain',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/nutrition/goal');

        $response->assertStatus(200)
            ->assertJson(['has_goal' => true])
            ->assertJsonPath('goal.daily_calories', 2000);
    }

    public function test_show_no_guarda_objetivo_sugerido_en_bd()
    {
        $user = User::factory()->create(['weight' => 75, 'height' => 175, 'age' => 30]);

        $this->actingAs($user, 'sanctum')->getJson('/api/nutrition/goal');

        $this->assertDatabaseMissing('nutrition_goals', ['user_id' => $user->id]);
    }

    public function test_show_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/nutrition/goal')->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // UPDATE
    // ──────────────────────────────────────────────────────────

    public function test_update_crea_objetivo_nutricional()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/nutrition/goal', [
            'daily_calories' => 2200,
            'target_protein' => 165,
            'target_carbs'   => 247,
            'target_fat'     => 61,
            'goal_type'      => 'gain_muscle',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('nutrition_goals', [
            'user_id'        => $user->id,
            'daily_calories' => 2200,
            'goal_type'      => 'gain_muscle',
        ]);
    }

    public function test_update_actualiza_objetivo_existente()
    {
        $user = User::factory()->create();

        NutritionGoal::create([
            'user_id'        => $user->id,
            'daily_calories' => 2000,
            'target_protein' => 150,
            'target_carbs'   => 225,
            'target_fat'     => 55,
            'goal_type'      => 'maintain',
        ]);

        $this->actingAs($user, 'sanctum')->putJson('/api/nutrition/goal', [
            'daily_calories' => 1800,
            'target_protein' => 135,
            'target_carbs'   => 202,
            'target_fat'     => 50,
            'goal_type'      => 'lose_weight',
        ]);

        // Solo debe haber 1 registro (updateOrCreate)
        $this->assertEquals(1, NutritionGoal::where('user_id', $user->id)->count());
        $this->assertDatabaseHas('nutrition_goals', ['user_id' => $user->id, 'daily_calories' => 1800]);
    }

    public function test_update_calorias_demasiado_altas_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->putJson('/api/nutrition/goal', [
            'daily_calories' => 15000,
            'target_protein' => 150,
            'target_carbs'   => 225,
            'target_fat'     => 55,
            'goal_type'      => 'maintain',
        ])->assertStatus(422)->assertJsonValidationErrors(['daily_calories']);
    }

    public function test_update_calorias_demasiado_bajas_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->putJson('/api/nutrition/goal', [
            'daily_calories' => 100,
            'target_protein' => 50,
            'target_carbs'   => 100,
            'target_fat'     => 30,
            'goal_type'      => 'lose_weight',
        ])->assertStatus(422)->assertJsonValidationErrors(['daily_calories']);
    }

    public function test_update_goal_type_invalido_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->putJson('/api/nutrition/goal', [
            'daily_calories' => 2000,
            'target_protein' => 150,
            'target_carbs'   => 225,
            'target_fat'     => 55,
            'goal_type'      => 'bulk',
        ])->assertStatus(422)->assertJsonValidationErrors(['goal_type']);
    }

    public function test_update_meals_per_day_mayor_8_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->putJson('/api/nutrition/goal', [
            'daily_calories'  => 2000,
            'target_protein'  => 150,
            'target_carbs'    => 225,
            'target_fat'      => 55,
            'goal_type'       => 'maintain',
            'meals_per_day'   => 9,
        ])->assertStatus(422)->assertJsonValidationErrors(['meals_per_day']);
    }
}
