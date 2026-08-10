<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegisterAndResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_cualquiera_puede_darse_de_alta_y_recibe_un_token()
    {
        $response = $this->postJson('/api/auth/register', [
            'name'     => 'Slavka',
            'email'    => 'slavka@example.com',
            'password' => 'contrasena123',
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['access_token', 'token_type', 'user_name', 'is_admin']);

        $this->assertDatabaseHas('users', ['email' => 'slavka@example.com', 'is_admin' => false]);
    }

    public function test_no_se_puede_repetir_el_correo()
    {
        User::factory()->create(['email' => 'isra@example.com']);

        $this->postJson('/api/auth/register', [
            'name' => 'Otro', 'email' => 'isra@example.com', 'password' => 'contrasena123',
        ])->assertStatus(422);
    }

    public function test_la_contrasena_tiene_un_minimo()
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Corto', 'email' => 'corto@example.com', 'password' => '1234',
        ])->assertStatus(422);
    }

    public function test_pedir_el_codigo_envia_un_correo_y_no_revela_si_el_usuario_existe()
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'isra@example.com']);

        $this->postJson('/api/auth/forgot-password', ['email' => 'isra@example.com'])->assertOk();
        $this->postJson('/api/auth/forgot-password', ['email' => 'nadie@example.com'])->assertOk();

        Notification::assertSentTo($user, ResetPasswordCode::class);
        Notification::assertCount(1);
    }

    public function test_el_codigo_correcto_cambia_la_contrasena_y_cierra_las_sesiones()
    {
        $user = User::factory()->create(['email' => 'isra@example.com']);
        $user->createToken('viejo');

        $this->postJson('/api/auth/forgot-password', ['email' => 'isra@example.com'])->assertOk();

        $code = \App\Http\Controllers\Api\AuthController::$lastCodeForTesting;

        $this->postJson('/api/auth/reset-password', [
            'email' => 'isra@example.com', 'code' => $code, 'password' => 'nuevacontrasena',
        ])->assertOk();

        $this->assertTrue(Hash::check('nuevacontrasena', $user->fresh()->password));
        $this->assertSame(0, $user->tokens()->count());
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'isra@example.com']);
    }

    public function test_un_codigo_equivocado_no_cambia_nada()
    {
        $user = User::factory()->create(['email' => 'isra@example.com']);
        $this->postJson('/api/auth/forgot-password', ['email' => 'isra@example.com']);

        $this->postJson('/api/auth/reset-password', [
            'email' => 'isra@example.com', 'code' => '000000', 'password' => 'nuevacontrasena',
        ])->assertStatus(422);

        $this->assertFalse(Hash::check('nuevacontrasena', $user->fresh()->password));
    }

    public function test_un_codigo_caducado_no_vale()
    {
        $user = User::factory()->create(['email' => 'isra@example.com']);
        $this->postJson('/api/auth/forgot-password', ['email' => 'isra@example.com']);
        $code = \App\Http\Controllers\Api\AuthController::$lastCodeForTesting;

        $this->travel(31)->minutes();

        $this->postJson('/api/auth/reset-password', [
            'email' => 'isra@example.com', 'code' => $code, 'password' => 'nuevacontrasena',
        ])->assertStatus(422);
    }
}
