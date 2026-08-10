<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────
    // INDEX
    // ──────────────────────────────────────────────────────────

    public function test_index_retorna_templates_del_sistema_y_del_usuario()
    {
        $user = User::factory()->create();

        // Template del sistema (user_id = null)
        Template::create([
            'name'  => 'Sistema Full Body',
            'mode'  => 'gym',
            'level' => 'Básico',
        ]);

        // Template del usuario
        Template::create([
            'name'    => 'Mi Rutina',
            'mode'    => 'home',
            'level'   => 'Intermedio',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/templates');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    public function test_index_no_retorna_templates_de_otros_usuarios()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        Template::create([
            'name'    => 'Rutina de otro',
            'mode'    => 'gym',
            'level'   => 'Básico',
            'user_id' => $other->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/templates');

        // Solo se ven templates del sistema (0 en este caso) + los propios
        $response->assertStatus(200);
        $this->assertCount(0, $response->json());
    }

    public function test_index_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/templates')->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // STORE
    // ──────────────────────────────────────────────────────────

    public function test_store_crea_template_valido()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->postJson('/api/templates', [
            'name'       => 'Mi Rutina',
            'mode'       => 'gym',
            'level'      => 'Básico',
            'exercises'  => [
                ['name' => 'Sentadilla'],
                ['name' => 'Press Banca'],
            ],
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('templates', ['name' => 'Mi Rutina', 'user_id' => $user->id]);
    }

    public function test_store_sin_exercises_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/templates', [
            'name'      => 'Mi Rutina',
            'mode'      => 'gym',
            'level'     => 'Básico',
            'exercises' => [],
        ])->assertStatus(422)->assertJsonValidationErrors(['exercises']);
    }

    public function test_store_con_modo_invalido_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/templates', [
            'name'      => 'Mi Rutina',
            'mode'      => 'crossfit',
            'level'     => 'Básico',
            'exercises' => [['name' => 'Sentadilla']],
        ])->assertStatus(422)->assertJsonValidationErrors(['mode']);
    }

    public function test_store_con_nivel_invalido_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/templates', [
            'name'      => 'Mi Rutina',
            'mode'      => 'gym',
            'level'     => 'Experto',
            'exercises' => [['name' => 'Sentadilla']],
        ])->assertStatus(422)->assertJsonValidationErrors(['level']);
    }

    public function test_store_nombre_demasiado_largo_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/templates', [
            'name'      => str_repeat('a', 101),
            'mode'      => 'gym',
            'level'     => 'Básico',
            'exercises' => [['name' => 'Sentadilla']],
        ])->assertStatus(422)->assertJsonValidationErrors(['name']);
    }

    // ──────────────────────────────────────────────────────────
    // UPDATE
    // ──────────────────────────────────────────────────────────

    public function test_update_actualiza_template_propio_y_reemplaza_ejercicios()
    {
        $user = User::factory()->create();

        $template = Template::create([
            'name'             => 'Mi Rutina Inicial',
            'mode'             => 'gym',
            'level'            => 'Básico',
            'description'      => 'Desc inicial',
            'duration_minutes' => 45,
            'user_id'          => $user->id,
            'is_custom'        => true,
        ]);

        $template->exercises()->createMany([
            ['name' => 'Sentadilla', 'sets' => 3, 'reps' => 8],
            ['name' => 'Press Banca', 'sets' => 3, 'reps' => 10],
        ]);

        $response = $this->actingAs($user, 'sanctum')->putJson("/api/templates/{$template->id}", [
            'name'             => 'Mi Rutina Actualizada',
            'description'      => 'Desc actualizada',
            'level'            => 'Intermedio',
            'mode'             => 'home',
            'duration_minutes' => 60,
            'exercises'        => [
                ['name' => 'Dominadas', 'sets' => 4, 'reps' => 6],
                ['name' => 'Fondos', 'sets' => 4, 'reps' => 10],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('name', 'Mi Rutina Actualizada')
            ->assertJsonCount(2, 'exercises');

        $this->assertDatabaseHas('templates', [
            'id'               => $template->id,
            'name'             => 'Mi Rutina Actualizada',
            'mode'             => 'home',
            'level'            => 'Intermedio',
            'duration_minutes' => 60,
        ]);

        // Se reemplazan ejercicios previos por los nuevos
        $this->assertDatabaseMissing('template_exercises', [
            'template_id' => $template->id,
            'name'        => 'Sentadilla',
        ]);
        $this->assertDatabaseHas('template_exercises', [
            'template_id' => $template->id,
            'name'        => 'Dominadas',
            'sets'        => 4,
            'reps'        => 6,
        ]);
    }

    public function test_update_template_del_sistema_retorna_403()
    {
        $user = User::factory()->create();

        $template = Template::create([
            'name'    => 'Sistema Full Body',
            'mode'    => 'gym',
            'level'   => 'Básico',
            'user_id' => null,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/templates/{$template->id}", [
                'name'      => 'No debería editar',
                'exercises' => [['name' => 'Sentadilla', 'sets' => 3, 'reps' => 8]],
            ])
            ->assertStatus(403);
    }

    public function test_update_template_de_otro_usuario_retorna_403()
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $template = Template::create([
            'name'    => 'Rutina de otro',
            'mode'    => 'gym',
            'level'   => 'Básico',
            'user_id' => $other->id,
            'is_custom' => true,
        ]);

        $template->exercises()->create([
            'name' => 'Sentadilla',
            'sets' => 3,
            'reps' => 8,
        ]);

        $this->actingAs($user, 'sanctum')
            ->putJson("/api/templates/{$template->id}", [
                'name'      => 'Intento no autorizado',
                'exercises' => [['name' => 'Dominadas', 'sets' => 4, 'reps' => 6]],
            ])
            ->assertStatus(403);
    }

    // ──────────────────────────────────────────────────────────
    // DESTROY
    // ──────────────────────────────────────────────────────────

    public function test_destroy_elimina_template_propio()
    {
        $user     = User::factory()->create();
        $template = Template::create([
            'name'    => 'Mi Rutina',
            'mode'    => 'gym',
            'level'   => 'Básico',
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/templates/{$template->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('templates', ['id' => $template->id]);
    }

    public function test_destroy_template_del_sistema_retorna_403()
    {
        $user     = User::factory()->create();
        $template = Template::create([
            'name'    => 'Sistema Full Body',
            'mode'    => 'gym',
            'level'   => 'Básico',
            'user_id' => null,  // Template del sistema
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/templates/{$template->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('templates', ['id' => $template->id]);
    }

    public function test_destroy_template_de_otro_usuario_retorna_403()
    {
        $user     = User::factory()->create();
        $other    = User::factory()->create();
        $template = Template::create([
            'name'    => 'Rutina de otro',
            'mode'    => 'gym',
            'level'   => 'Básico',
            'user_id' => $other->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/templates/{$template->id}")
            ->assertStatus(403);
    }
}
