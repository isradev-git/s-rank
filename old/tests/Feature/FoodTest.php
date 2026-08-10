<?php

namespace Tests\Feature;

use App\Models\FoodItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FoodTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────
    // INDEX — búsqueda de alimentos
    // ──────────────────────────────────────────────────────────

    public function test_index_retorna_alimentos_del_sistema_y_del_usuario()
    {
        $user = User::factory()->create();

        FoodItem::factory()->system()->create(['name' => 'Pollo cocido']);
        FoodItem::factory()->forUser($user->id)->create(['name' => 'Mi proteína']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/foods');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json('foods'));
    }

    public function test_index_no_retorna_alimentos_de_otros_usuarios()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        FoodItem::factory()->forUser($other->id)->create(['name' => 'Alimento de otro']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/foods');

        $response->assertStatus(200);
        $this->assertCount(0, $response->json('foods'));
    }

    public function test_index_filtra_por_nombre()
    {
        $user = User::factory()->create();

        FoodItem::factory()->system()->create(['name' => 'Pechuga de pollo']);
        FoodItem::factory()->system()->create(['name' => 'Arroz integral']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/foods?search=pollo');

        $response->assertStatus(200);
        $data = $response->json('foods');
        $this->assertCount(1, $data);
        $this->assertStringContainsStringIgnoringCase('pollo', $data[0]['name']);
    }

    public function test_index_filtra_por_categoria()
    {
        $user = User::factory()->create();

        FoodItem::factory()->system()->create(['name' => 'Leche', 'category' => 'lácteos']);
        FoodItem::factory()->system()->create(['name' => 'Pollo', 'category' => 'carnes']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/foods?category=lácteos');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('foods'));
        $this->assertEquals('lácteos', $response->json('foods.0.category'));
    }

    public function test_index_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/foods')->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // ALL — paginado
    // ──────────────────────────────────────────────────────────

    public function test_all_retorna_paginacion()
    {
        $user = User::factory()->create();
        FoodItem::factory()->system()->count(5)->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/foods/all');

        $response->assertStatus(200)
            ->assertJsonStructure(['foods', 'total', 'current_page', 'last_page', 'per_page']);
        $this->assertEquals(5, $response->json('total'));
    }

    // ──────────────────────────────────────────────────────────
    // CATEGORIES
    // ──────────────────────────────────────────────────────────

    public function test_categories_retorna_lista_unica_de_categorias()
    {
        $user = User::factory()->create();

        FoodItem::factory()->system()->create(['category' => 'carnes']);
        FoodItem::factory()->system()->create(['category' => 'carnes']);
        FoodItem::factory()->system()->create(['category' => 'lácteos']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/foods/categories');

        $response->assertStatus(200);
        $categories = $response->json('categories');
        $this->assertCount(2, $categories);
        $this->assertContains('carnes', $categories);
        $this->assertContains('lácteos', $categories);
    }

    // ──────────────────────────────────────────────────────────
    // STORE
    // ──────────────────────────────────────────────────────────

    public function test_store_crea_alimento_personal()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/foods', [
            'name'              => 'Mi Proteína',
            'calories_per_100g' => 350,
            'protein_per_100g'  => 70,
            'carbs_per_100g'    => 20,
            'fat_per_100g'      => 5,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('food_items', [
            'name'    => 'Mi Proteína',
            'user_id' => $user->id,
        ]);
    }

    public function test_store_con_from_ingredients_true_crea_alimento_del_sistema()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/foods', [
            'name'              => 'Nuevo Alimento Sistema',
            'calories_per_100g' => 200,
            'from_ingredients'  => true,
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('food_items', [
            'name'        => 'Nuevo Alimento Sistema',
            'user_id'     => null,
            'is_verified' => true,
        ]);
    }

    public function test_store_sin_nombre_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/foods', [
            'calories_per_100g' => 200,
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    public function test_store_sin_calorias_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/foods', [
            'name' => 'Alimento sin calorias',
        ])->assertStatus(422)->assertJsonValidationErrors(['calories_per_100g']);
    }

    public function test_store_con_unidad_invalida_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/foods', [
            'name'              => 'Alimento',
            'calories_per_100g' => 200,
            'unit'              => 'oz',
        ])->assertStatus(422)->assertJsonValidationErrors(['unit']);
    }

    // ──────────────────────────────────────────────────────────
    // UPDATE
    // ──────────────────────────────────────────────────────────

    public function test_update_modifica_alimento_propio()
    {
        $user = User::factory()->create();
        $food = FoodItem::factory()->forUser($user->id)->create(['calories_per_100g' => 100]);

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/foods/{$food->id}", ['calories_per_100g' => 150]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('food_items', ['id' => $food->id, 'calories_per_100g' => 150]);
    }

    public function test_update_alimento_del_sistema_por_cualquier_usuario()
    {
        $user = User::factory()->create();
        $food = FoodItem::factory()->system()->create(['calories_per_100g' => 100]);

        // Cualquier usuario puede editar alimentos del sistema (para completar macros)
        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/foods/{$food->id}", ['protein_per_100g' => 25]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('food_items', ['id' => $food->id, 'protein_per_100g' => 25]);
    }

    public function test_update_alimento_de_otro_usuario_retorna_403()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $food  = FoodItem::factory()->forUser($other->id)->create();

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/foods/{$food->id}", ['calories_per_100g' => 999])
            ->assertStatus(403);
    }

    // ──────────────────────────────────────────────────────────
    // DESTROY
    // ──────────────────────────────────────────────────────────

    public function test_destroy_elimina_alimento_propio()
    {
        $user = User::factory()->create();
        $food = FoodItem::factory()->forUser($user->id)->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson("/api/foods/{$food->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('food_items', ['id' => $food->id]);
    }

    public function test_destroy_alimento_del_sistema_retorna_403()
    {
        $user = User::factory()->create();
        $food = FoodItem::factory()->system()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/foods/{$food->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('food_items', ['id' => $food->id]);
    }

    public function test_destroy_alimento_de_otro_usuario_retorna_403()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();
        $food  = FoodItem::factory()->forUser($other->id)->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/foods/{$food->id}")
            ->assertStatus(403);
    }
}
