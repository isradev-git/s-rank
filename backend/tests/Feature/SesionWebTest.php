<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * El frontend web entra con cookie de sesión; el móvil, con el token de siempre. Son las
 * mismas dos rutas comportándose de dos maneras según de dónde venga la petición, y la
 * que no está probada es exactamente la que se cae sin que nadie se entere.
 *
 * ⚠️ Lo que estos tests NO demuestran: que la cookie viaje. La suite corre con
 * SESSION_DRIVER=array, que no guarda nada entre peticiones, así que el ida y vuelta real
 * del navegador no se puede reproducir aquí. Lo que sí se comprueba es lo único que este
 * código decide: a quién se le abre sesión y a quién no. Que la cookie llegue y vuelva se
 * verifica en el navegador, y está anotado como tal en el plan de la fase.
 */
class SesionWebTest extends TestCase
{
    use RefreshDatabase;

    /** Marca la petición como venida del frontend, que es lo que hace que Sanctum le dé
     *  sesión. Sin cabecera Origin, la petición es la de una app nativa. */
    private function desdeElFrontend(): static
    {
        config(['sanctum.stateful' => ['localhost:5173']]);

        return $this->withHeader('Origin', 'http://localhost:5173');
    }

    public function test_entrar_desde_el_frontend_abre_sesion()
    {
        $user = User::factory()->create(['email' => 'user@example.com']);

        $this->desdeElFrontend()
            ->postJson('/api/auth/login', [
                'email'    => 'user@example.com',
                'password' => 'password',
            ])
            ->assertStatus(200);

        $this->assertAuthenticatedAs($user, 'web');
    }

    public function test_registrarse_desde_el_frontend_abre_sesion()
    {
        $this->desdeElFrontend()
            ->postJson('/api/auth/register', [
                'name'     => 'Nueva',
                'email'    => 'nueva@example.com',
                'password' => 'contrasena8',
            ])
            ->assertStatus(201);

        $this->assertAuthenticated('web');
        $this->assertSame('nueva@example.com', auth('web')->user()->email);
    }

    public function test_entrar_desde_el_movil_no_abre_ninguna_sesion()
    {
        User::factory()->create(['email' => 'user@example.com']);

        // Sin Origin: es como llega la petición de una app nativa. Aquí no debe quedar
        // sesión ninguna, o el servidor estaría repartiendo cookies a quien no las pidió.
        $this->postJson('/api/auth/login', [
            'email'    => 'user@example.com',
            'password' => 'password',
        ])->assertStatus(200);

        $this->assertGuest('web');

        // Y sin cabecera Authorization no se entra a ningún sitio.
        $this->getJson('/api/user')->assertStatus(401);
    }

    public function test_salir_con_sesion_de_cookie_no_revienta()
    {
        $user = User::factory()->create();

        // actingAs autentica por el guard de sesión, así que currentAccessToken() devuelve
        // un TransientToken. Llamar a ->delete() sobre él es un error fatal, y esta ruta
        // lo hacía sin condición ninguna.
        $this->actingAs($user)
            ->postJson('/api/auth/logout')
            ->assertStatus(200)
            ->assertJsonPath('message', 'Sesión cerrada correctamente.');
    }

    public function test_salir_desde_el_frontend_cierra_la_sesion_ademas_de_responder()
    {
        $user = User::factory()->create();

        // Con Origin, para que la petición sea stateful y tenga sesión que invalidar.
        // Responder 200 y dejar la sesión viva sería peor que fallar: el usuario cree
        // que ha salido y no lo ha hecho.
        $this->desdeElFrontend()
            ->actingAs($user)
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        $this->assertGuest('web');
    }

    public function test_salir_con_token_sigue_borrando_el_token()
    {
        $user  = User::factory()->create();
        $token = $user->createToken('auth_token')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        $this->assertSame(0, $user->tokens()->count());
    }
}
