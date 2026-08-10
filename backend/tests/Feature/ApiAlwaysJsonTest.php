<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiAlwaysJsonTest extends TestCase
{
    use RefreshDatabase;

    public function test_una_ruta_autenticada_sin_cabecera_accept_devuelve_401_y_no_500()
    {
        // Un navegador no manda `Accept: application/json`. Sin la configuración de
        // bootstrap/app.php, Laravel intentaba redirigir a la ruta «login», que en una
        // API no existe, y el 401 se convertía en un 500.
        $response = $this->get('/api/system/today');

        $response->assertUnauthorized();
        $this->assertSame('Unauthenticated.', $response->json('message'));
    }

    public function test_una_ruta_que_no_existe_devuelve_404_en_json()
    {
        $this->get('/api/no-existe')->assertNotFound()->assertJsonStructure(['message']);
    }
}
