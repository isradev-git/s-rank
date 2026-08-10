<?php

namespace Tests\Feature;

use App\Models\FoodItem;
use App\Models\MealLog;
use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MealLogTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────
    // INDEX
    // ──────────────────────────────────────────────────────────

    public function test_index_retorna_comidas_del_dia()
    {
        $user = User::factory()->create();

        MealLog::create([
            'user_id'   => $user->id,
            'date'      => '2026-03-31',
            'meal_type' => 'breakfast',
            'custom_food_name' => 'Avena',
            'quantity_grams'   => 100,
            'calories'  => 350,
            'protein'   => 12,
            'carbs'     => 60,
            'fat'       => 7,
            'fiber'     => 8,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/meals?date=2026-03-31');

        $response->assertStatus(200)
            ->assertJsonStructure(['date', 'meals', 'totals', 'count', 'calories_burned']);
        $this->assertEquals('2026-03-31', $response->json('date'));
        $this->assertEquals(1, $response->json('count'));
    }

    public function test_index_totaliza_macros_del_dia()
    {
        $user = User::factory()->create();

        MealLog::create([
            'user_id'   => $user->id,
            'date'      => '2026-03-31',
            'meal_type' => 'breakfast',
            'custom_food_name' => 'Avena',
            'quantity_grams'   => 100,
            'calories' => 300,
            'protein'  => 10,
            'carbs'    => 50,
            'fat'      => 5,
            'fiber'    => 4,
        ]);

        MealLog::create([
            'user_id'   => $user->id,
            'date'      => '2026-03-31',
            'meal_type' => 'lunch',
            'custom_food_name' => 'Arroz',
            'quantity_grams'   => 150,
            'calories' => 200,
            'protein'  => 5,
            'carbs'    => 40,
            'fat'      => 2,
            'fiber'    => 2,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/meals?date=2026-03-31');

        $response->assertStatus(200);
        $totals = $response->json('totals');
        $this->assertEquals(500, $totals['calories']);
        $this->assertEquals(15, $totals['protein']);
    }

    public function test_index_sin_fecha_usa_hoy()
    {
        $user = User::factory()->create();

        MealLog::create([
            'user_id'   => $user->id,
            'date'      => now()->toDateString(),
            'meal_type' => 'breakfast',
            'custom_food_name' => 'Avena',
            'quantity_grams'   => 100,
            'calories' => 300,
            'protein'  => 10,
            'carbs'    => 50,
            'fat'      => 5,
            'fiber'    => 4,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/meals');
        $response->assertStatus(200)->assertJson(['count' => 1]);
    }

    public function test_index_no_retorna_comidas_de_otros_usuarios()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        MealLog::create([
            'user_id'   => $other->id,
            'date'      => '2026-03-31',
            'meal_type' => 'breakfast',
            'custom_food_name' => 'Avena de otro',
            'quantity_grams'   => 100,
            'calories' => 300, 'protein' => 10, 'carbs' => 50, 'fat' => 5, 'fiber' => 4,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/meals?date=2026-03-31');

        $response->assertStatus(200)->assertJson(['count' => 0]);
    }

    public function test_index_incluye_calorias_quemadas_por_entrenamiento()
    {
        $user = User::factory()->create(['weight' => 80]);

        Workout::factory()->create([
            'user_id'          => $user->id,
            'date'             => '2026-03-31',
            'mode'             => 'gym',
            'duration_minutes' => 60,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/meals?date=2026-03-31');

        $response->assertStatus(200);
        // MET gym=5.5, peso=80kg, 1h → 440 kcal
        $this->assertEquals(440, $response->json('calories_burned'));
    }

    // ──────────────────────────────────────────────────────────
    // STORE
    // ──────────────────────────────────────────────────────────

    public function test_store_con_food_item_id_calcula_macros_automaticamente()
    {
        $user = User::factory()->create();
        $food = FoodItem::factory()->system()->create([
            'calories_per_100g' => 200,
            'protein_per_100g'  => 20,
            'carbs_per_100g'    => 30,
            'fat_per_100g'      => 5,
            'fiber_per_100g'    => 2,
        ]);

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/meals', [
            'meal_type'      => 'lunch',
            'food_item_id'   => $food->id,
            'quantity_grams' => 150,
        ]);

        $response->assertStatus(201);
        $mealLog = $response->json('meal_log');
        // 150g → factor 1.5 → 300 kcal, 30g prot, 45g carbs, 7.5g fat
        $this->assertEquals(300.0, $mealLog['calories']);
        $this->assertEquals(30.0, $mealLog['protein']);
    }

    public function test_store_con_custom_food_name_usa_macros_manuales()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/meals', [
            'meal_type'        => 'dinner',
            'custom_food_name' => 'Cena casera',
            'calories'         => 500,
            'protein'          => 35,
            'carbs'            => 60,
            'fat'              => 12,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('meal_logs', ['custom_food_name' => 'Cena casera', 'calories' => 500]);
    }

    public function test_store_sin_food_item_ni_custom_name_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/meals', [
            'meal_type' => 'lunch',
            'calories'  => 200,
        ])->assertStatus(422);
    }

    public function test_store_con_meal_type_invalido_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/meals', [
            'meal_type'        => 'merienda',
            'custom_food_name' => 'Fruta',
        ])->assertStatus(422)->assertJsonValidationErrors(['meal_type']);
    }

    public function test_store_con_food_item_id_inexistente_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/meals', [
            'meal_type'    => 'lunch',
            'food_item_id' => 99999,
        ])->assertStatus(422)->assertJsonValidationErrors(['food_item_id']);
    }

    public function test_store_cantidad_fuera_de_rango_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/meals', [
            'meal_type'        => 'lunch',
            'custom_food_name' => 'Algo',
            'quantity_grams'   => 9999,
        ])->assertStatus(422)->assertJsonValidationErrors(['quantity_grams']);
    }

    public function test_store_asigna_uuid_automaticamente()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/meals', [
            'meal_type'        => 'snack',
            'custom_food_name' => 'Manzana',
            'calories'         => 80,
        ]);

        $response->assertStatus(201);
        $uuid = $response->json('meal_log.uuid');
        $this->assertNotNull($uuid);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $uuid
        );
    }

    // ──────────────────────────────────────────────────────────
    // DESTROY
    // ──────────────────────────────────────────────────────────

    public function test_destroy_elimina_comida_propia_por_uuid()
    {
        $user = User::factory()->create();

        $log = MealLog::create([
            'user_id'          => $user->id,
            'date'             => '2026-03-31',
            'meal_type'        => 'breakfast',
            'custom_food_name' => 'Avena',
            'quantity_grams'   => 100,
            'calories' => 300, 'protein' => 10, 'carbs' => 50, 'fat' => 5, 'fiber' => 4,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/meals/{$log->uuid}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('meal_logs', ['uuid' => $log->uuid]);
    }

    public function test_destroy_no_puede_borrar_la_comida_de_otro_usuario()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        $log = MealLog::create([
            'user_id'          => $other->id,
            'date'             => '2026-03-31',
            'meal_type'        => 'breakfast',
            'custom_food_name' => 'Avena de otro',
            'quantity_grams'   => 100,
            'calories' => 300, 'protein' => 10, 'carbs' => 50, 'fat' => 5, 'fiber' => 4,
        ]);

        // El borrado es idempotente a propósito (doble pulsación, estado desfasado en
        // el cliente), así que responde 200. Lo que importa es que la fila del otro
        // usuario siga intacta.
        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/meals/{$log->uuid}")
            ->assertOk();

        $this->assertDatabaseHas('meal_logs', ['uuid' => $log->uuid, 'user_id' => $other->id]);
    }

    // ──────────────────────────────────────────────────────────
    // HISTORY
    // ──────────────────────────────────────────────────────────

    public function test_history_retorna_sumatorio_de_macros_por_dia()
    {
        $user = User::factory()->create();

        MealLog::create([
            'user_id' => $user->id, 'date' => now()->subDay()->toDateString(),
            'meal_type' => 'breakfast', 'custom_food_name' => 'Avena',
            'quantity_grams' => 100,
            'calories' => 300, 'protein' => 10, 'carbs' => 50, 'fat' => 5, 'fiber' => 4,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/meals/history?days=7');

        $response->assertStatus(200)
            ->assertJsonStructure(['history', 'days', 'start_date']);
        $this->assertCount(1, $response->json('history'));
    }

    public function test_history_limita_a_90_dias_como_maximo()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/meals/history?days=999');

        $response->assertStatus(200);
        $this->assertEquals(90, $response->json('days'));
    }
}
