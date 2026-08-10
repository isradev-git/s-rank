<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    // ──────────────────────────────────────────────────────────
    // LOGIN
    // ──────────────────────────────────────────────────────────

    public function test_login_con_credenciales_validas_retorna_token()
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'user_name',
                'is_admin',
            ])
            ->assertJson([
                'token_type' => 'Bearer',
                'user_name'  => $user->name,
                'is_admin'   => false,
            ]);

        // La cookie fitloop_token debe estar presente
        $this->assertNotNull($response->headers->getCookies());
    }

    public function test_login_admin_retorna_is_admin_true()
    {
        User::factory()->admin()->create(['email' => 'admin@example.com']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)->assertJson(['is_admin' => true]);
    }

    public function test_login_con_email_inexistente_retorna_422()
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'noexiste@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_con_password_incorrecta_retorna_422()
    {
        User::factory()->create(['email' => 'user@example.com']);

        $response = $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_sin_email_retorna_422()
    {
        $response = $this->postJson('/api/auth/login', [
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_login_sin_password_retorna_422()
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'user@example.com',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_login_con_email_invalido_retorna_422()
    {
        $response = $this->postJson('/api/auth/login', [
            'email'    => 'no-es-un-email',
            'password' => 'password',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    // ──────────────────────────────────────────────────────────
    // LOGOUT
    // ──────────────────────────────────────────────────────────

    public function test_logout_elimina_token_del_usuario()
    {
        $user  = User::factory()->create();
        $token = $user->createToken('test-token')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/api/auth/logout');

        $response->assertStatus(200)
            ->assertJson(['message' => 'Sesión cerrada correctamente.']);

        // El token debe haber sido eliminado
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_logout_sin_autenticacion_retorna_401()
    {
        $response = $this->postJson('/api/auth/logout');
        $response->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // RUTAS PROTEGIDAS
    // ──────────────────────────────────────────────────────────

    public function test_acceso_a_ruta_protegida_sin_token_retorna_401()
    {
        $response = $this->getJson('/api/user');
        $response->assertStatus(401);
    }

    public function test_acceso_a_ruta_protegida_con_token_valido_retorna_200()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');
        $response->assertStatus(200);
    }
}
