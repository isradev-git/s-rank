<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordCode;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
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

    /**
     * El correo desconocido va primero a propósito: así se comprueba que no manda nada
     * en el momento exacto en que podría hacerlo, y no al final sumando envíos.
     *
     * Hace falta porque el envío se difiere con app()->terminating(), y esos callbacks
     * se acumulan mientras la aplicación siga viva: en un test, dos peticiones seguidas
     * de un correo que sí existe cuentan dos envíos aunque el segundo sea el primero
     * repitiéndose. En producción no ocurre —cada petición arranca su propia
     * aplicación— pero contar envíos aquí mide el banco de pruebas, no el código.
     */
    public function test_pedir_el_codigo_envia_un_correo_y_no_revela_si_el_usuario_existe()
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'isra@example.com']);

        $noExiste = $this->postJson('/api/auth/forgot-password', ['email' => 'nadie@example.com']);
        $noExiste->assertOk();
        Notification::assertNothingSent();

        $existe = $this->postJson('/api/auth/forgot-password', ['email' => 'isra@example.com']);
        $existe->assertOk();
        Notification::assertSentTo($user, ResetPasswordCode::class);
        Notification::assertCount(1);

        // Y desde fuera las dos respuestas son la misma palabra por palabra.
        $this->assertSame($noExiste->json('message'), $existe->json('message'));
    }

    /**
     * Pedir el código para un correo que no existe tiene que costar lo mismo que para uno
     * que sí. Sin señuelo, la rama vacía no paga bcrypt y vuelve mucho antes: el tiempo
     * dice qué cuentas hay dadas de alta, que es justo lo que el mensaje único esconde.
     *
     * Se comprueba que se paga el hash, no que el reloj marque lo mismo: medir
     * milisegundos en un test lo vuelve inestable sin demostrar nada más. Igual que
     * test_login_con_email_inexistente_tambien_comprueba_un_hash.
     */
    public function test_pedir_el_codigo_para_un_correo_inexistente_tambien_paga_el_hash()
    {
        Hash::spy();

        $this->postJson('/api/auth/forgot-password', ['email' => 'nadie@example.com'])->assertOk();

        Hash::shouldHaveReceived('make')->once();
    }

    /**
     * La otra mitad: el bcrypt del señuelo no sirve de nada si la rama que sí existe
     * añade encima el segundo que cuesta saludar al SMTP. El envío tiene que quedar
     * fuera de la petición, así que cuando la respuesta está lista no puede haberse
     * mandado todavía ningún correo.
     */
    public function test_el_correo_sale_despues_de_haber_respondido()
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'isra@example.com']);

        $request = Request::create(
            '/api/auth/forgot-password', 'POST', [], [], [],
            ['HTTP_ACCEPT' => 'application/json', 'CONTENT_TYPE' => 'application/json'],
            json_encode(['email' => 'isra@example.com'])
        );

        $kernel   = $this->app->make(HttpKernel::class);
        $response = $kernel->handle($request);

        $this->assertSame(200, $response->getStatusCode());
        Notification::assertNothingSent();

        // Y después de responder, sí sale.
        $kernel->terminate($request, $response);
        Notification::assertSentTo($user, ResetPasswordCode::class);
    }

    public function test_si_el_smtp_falla_la_respuesta_sigue_siendo_la_misma()
    {
        // Un servidor de correo inalcanzable: el envío lanza excepción de verdad.
        config([
            'mail.default'            => 'smtp',
            'mail.mailers.smtp.host'  => '127.0.0.1',
            'mail.mailers.smtp.port'  => 1,
        ]);

        User::factory()->create(['email' => 'isra@example.com']);

        $existe   = $this->postJson('/api/auth/forgot-password', ['email' => 'isra@example.com']);
        $noExiste = $this->postJson('/api/auth/forgot-password', ['email' => 'nadie@example.com']);

        // Mismo código y mismo cuerpo: desde fuera no se distingue si la cuenta existe.
        $existe->assertOk();
        $noExiste->assertOk();
        $this->assertSame($noExiste->json('message'), $existe->json('message'));

        // Y el código se ha guardado, para que reenviar el correo más tarde valga.
        $this->assertDatabaseHas('password_reset_tokens', ['email' => 'isra@example.com']);
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
