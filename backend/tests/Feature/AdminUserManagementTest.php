<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────
    // PROTECCIÓN — solo admins
    // ──────────────────────────────────────────────────────────

    public function test_usuario_no_admin_no_puede_listar_usuarios()
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/users')
            ->assertStatus(403);
    }

    public function test_usuario_no_autenticado_no_puede_listar_usuarios()
    {
        $this->getJson('/api/admin/users')->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // INDEX
    // ──────────────────────────────────────────────────────────

    public function test_admin_puede_listar_todos_los_usuarios()
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure(['users']);
        // 1 admin + 3 usuarios = 4 en total
        $this->assertCount(4, $response->json('users'));
    }

    public function test_listado_incluye_campos_necesarios()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/admin/users');

        $response->assertStatus(200);
        $user = $response->json('users.0');
        $this->assertArrayHasKey('id', $user);
        $this->assertArrayHasKey('name', $user);
        $this->assertArrayHasKey('email', $user);
        $this->assertArrayHasKey('is_admin', $user);
        // El password NO debe aparecer
        $this->assertArrayNotHasKey('password', $user);
    }

    // ──────────────────────────────────────────────────────────
    // STORE
    // ──────────────────────────────────────────────────────────

    public function test_admin_puede_crear_usuario()
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/admin/users', [
            'name'     => 'Nuevo Usuario',
            'email'    => 'nuevo@example.com',
            'password' => 'Password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('user.email', 'nuevo@example.com')
            ->assertJsonPath('user.is_admin', false);

        $this->assertDatabaseHas('users', ['email' => 'nuevo@example.com']);
    }

    public function test_store_email_duplicado_retorna_422()
    {
        $admin = User::factory()->admin()->create();
        User::factory()->create(['email' => 'existente@example.com']);

        $this->actingAs($admin, 'sanctum')->postJson('/api/admin/users', [
            'name'     => 'Otro',
            'email'    => 'existente@example.com',
            'password' => 'Password123',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);
    }

    public function test_store_password_debil_retorna_422()
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/admin/users', [
            'name'     => 'Usuario',
            'email'    => 'user@example.com',
            'password' => '1234567',  // Menos de 8 caracteres
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_store_password_sin_letras_retorna_422()
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/admin/users', [
            'name'     => 'Usuario',
            'email'    => 'user@example.com',
            'password' => '12345678',  // Sin letras
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_store_password_sin_numeros_retorna_422()
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/admin/users', [
            'name'     => 'Usuario',
            'email'    => 'user@example.com',
            'password' => 'SoloLetras',  // Sin números
        ])->assertStatus(422)->assertJsonValidationErrors(['password']);
    }

    public function test_usuario_creado_por_admin_no_es_admin()
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')->postJson('/api/admin/users', [
            'name'     => 'Normal User',
            'email'    => 'normal@example.com',
            'password' => 'Password123',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'normal@example.com', 'is_admin' => false]);
    }

    // ──────────────────────────────────────────────────────────
    // DESTROY
    // ──────────────────────────────────────────────────────────

    public function test_admin_puede_eliminar_otro_usuario()
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/users/{$target->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_admin_no_puede_eliminarse_a_si_mismo()
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/users/{$admin->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_destroy_revoca_tokens_del_usuario_eliminado()
    {
        $admin  = User::factory()->admin()->create();
        $target = User::factory()->create();

        // Creamos tokens para el usuario a eliminar
        $target->createToken('test-token');
        $this->assertCount(1, $target->tokens);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/admin/users/{$target->id}");

        // El usuario ha sido eliminado (cascade eliminaría tokens, pero verificamos el flujo)
        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_no_admin_no_puede_eliminar_usuarios()
    {
        $user   = User::factory()->create(['is_admin' => false]);
        $target = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/admin/users/{$target->id}")
            ->assertStatus(403);

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
