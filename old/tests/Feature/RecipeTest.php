<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────
    // INDEX
    // ──────────────────────────────────────────────────────────

    public function test_index_retorna_recetas_del_sistema_y_del_usuario()
    {
        $user = User::factory()->create();

        Recipe::factory()->system()->create(['name' => 'Receta sistema']);
        Recipe::factory()->byUser($user->id)->create(['name' => 'Mi receta']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/recipes');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('recipes'));
    }

    public function test_index_no_retorna_recetas_privadas_de_otros_usuarios()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        // Receta con is_system=false y user_id del otro ← esta NO debe aparecer
        Recipe::create([
            'name'                 => 'Receta privada de otro',
            'category'             => 'almuerzo',
            'calories_per_serving' => 400,
            'is_system'            => false,
            'user_id'              => $other->id,
            'ingredients'          => [],
            'instructions'         => '',
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/recipes');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('recipes'));
    }

    public function test_index_filtra_por_categoria()
    {
        $user = User::factory()->create();

        Recipe::factory()->system()->create(['name' => 'Tortilla', 'category' => 'desayuno']);
        Recipe::factory()->system()->create(['name' => 'Ensalada', 'category' => 'almuerzo']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/recipes?category=desayuno');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('recipes'));
        $this->assertEquals('desayuno', $response->json('recipes.0.category'));
    }

    public function test_index_filtra_por_max_calorias()
    {
        $user = User::factory()->create();

        Recipe::factory()->system()->create(['calories_per_serving' => 300]);
        Recipe::factory()->system()->create(['calories_per_serving' => 600]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/recipes?max_calories=400');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('recipes'));
    }

    public function test_index_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/recipes')->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // SHOW
    // ──────────────────────────────────────────────────────────

    public function test_show_retorna_receta_del_sistema_para_cualquier_usuario()
    {
        $user   = User::factory()->create();
        $recipe = Recipe::factory()->system()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/recipes/{$recipe->id}");

        $response->assertStatus(200)->assertJson(['recipe' => ['id' => $recipe->id]]);
    }

    public function test_show_retorna_receta_propia_del_usuario()
    {
        $user   = User::factory()->create();
        $recipe = Recipe::factory()->byUser($user->id)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson("/api/recipes/{$recipe->id}");

        $response->assertStatus(200);
    }

    public function test_show_receta_privada_de_otro_usuario_retorna_404()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        // Receta no-sistema de otro usuario
        $recipe = Recipe::create([
            'name'                 => 'Receta privada',
            'category'             => 'cena',
            'calories_per_serving' => 400,
            'is_system'            => false,
            'user_id'              => $other->id,
            'ingredients'          => [],
            'instructions'         => '',
        ]);

        $this->actingAs($user, 'sanctum')
            ->getJson("/api/recipes/{$recipe->id}")
            ->assertStatus(404);
    }

    // ──────────────────────────────────────────────────────────
    // STORE
    // ──────────────────────────────────────────────────────────

    public function test_store_crea_receta_del_usuario()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/recipes', [
            'name'                 => 'Pollo con arroz',
            'category'             => 'almuerzo',
            'calories_per_serving' => 450,
            'protein_per_serving'  => 40,
            'carbs_per_serving'    => 50,
            'fat_per_serving'      => 8,
            'ingredients'          => [
                ['name' => 'Pechuga', 'quantity' => '200g'],
                ['name' => 'Arroz',   'quantity' => '80g'],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('recipes', [
            'name'      => 'Pollo con arroz',
            'user_id'   => $user->id,
            'is_system' => true,  // Las recetas de usuario también son is_system=true
        ]);
    }

    public function test_store_sin_nombre_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/recipes', [
            'category'             => 'almuerzo',
            'calories_per_serving' => 400,
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_store_con_categoria_invalida_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/recipes', [
            'name'                 => 'Mi receta',
            'category'             => 'postre',
            'calories_per_serving' => 400,
        ])->assertStatus(422)->assertJsonValidationErrors(['category']);
    }

    public function test_store_sin_calorias_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/recipes', [
            'name'     => 'Mi receta',
            'category' => 'cena',
        ])->assertStatus(422)->assertJsonValidationErrors(['calories_per_serving']);
    }

    // ──────────────────────────────────────────────────────────
    // DESTROY
    // ──────────────────────────────────────────────────────────

    public function test_destroy_elimina_receta_propia()
    {
        $user   = User::factory()->create();
        $recipe = Recipe::factory()->byUser($user->id)->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/recipes/{$recipe->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('recipes', ['id' => $recipe->id]);
    }

    public function test_destroy_receta_del_sistema_sin_user_id_retorna_403()
    {
        $user = User::factory()->create();

        // Receta original del sistema (user_id = null)
        $recipe = Recipe::create([
            'name'                 => 'Receta Original Sistema',
            'category'             => 'desayuno',
            'calories_per_serving' => 300,
            'is_system'            => true,
            'user_id'              => null,
            'ingredients'          => [],
            'instructions'         => '',
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/recipes/{$recipe->id}")
            ->assertStatus(403);
    }

    public function test_destroy_receta_de_otro_usuario_retorna_403()
    {
        $user   = User::factory()->create();
        $other  = User::factory()->create();
        $recipe = Recipe::factory()->byUser($other->id)->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/recipes/{$recipe->id}")
            ->assertStatus(403);
    }

    // ──────────────────────────────────────────────────────────
    // RECOMMENDED
    // ──────────────────────────────────────────────────────────

    public function test_recommended_retorna_recetas_que_caben_en_presupuesto_calorico()
    {
        $user = User::factory()->create();

        Recipe::factory()->system()->create(['calories_per_serving' => 300, 'category' => 'cena']);
        Recipe::factory()->system()->create(['calories_per_serving' => 800, 'category' => 'cena']);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/recipes/recommended?remaining_calories=500&meal_type=cena');

        $response->assertStatus(200)->assertJsonStructure(['recipes']);
        // Solo la receta de 300 kcal cabe en el presupuesto de 500
        foreach ($response->json('recipes') as $recipe) {
            $this->assertLessThanOrEqual(500, $recipe['calories_per_serving']);
        }
    }

    public function test_recommended_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/recipes/recommended')->assertStatus(401);
    }
}
