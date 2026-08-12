<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * La ruta de reserva que sirve el frontend. Tiene una rama, y la rama equivocada
 * devuelve HTML a un cliente que espera JSON: el mismo fallo que ya tumbó una ruta
 * en producción con la suite entera en verde.
 */
class FrontendSpaTest extends TestCase
{
    private string $index;

    protected function setUp(): void
    {
        parent::setUp();
        $this->index = public_path('index.html');
    }

    protected function tearDown(): void
    {
        // El index.html de verdad lo genera el build y no está en el repositorio, así que
        // cada test que lo necesite lo crea y lo borra.
        if (file_exists($this->index)) {
            unlink($this->index);
        }

        parent::tearDown();
    }

    public function test_una_ruta_del_frontend_devuelve_el_index()
    {
        file_put_contents($this->index, '<!doctype html><title>S-RANK</title>');

        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('S-RANK', false)
            // Sin esto, un despliegue nuevo deja al navegador pidiendo un JavaScript
            // con un hash que ya no existe, y la app no arranca.
            ->assertHeader('Cache-Control', 'no-cache, private');
    }

    public function test_una_ruta_de_la_api_que_no_existe_sigue_contestando_json()
    {
        file_put_contents($this->index, '<!doctype html><title>S-RANK</title>');

        $respuesta = $this->getJson('/api/esto-no-existe');

        $respuesta->assertStatus(404);
        $this->assertStringContainsString('application/json', $respuesta->headers->get('content-type'));
        $this->assertStringNotContainsString('<!doctype html>', $respuesta->getContent());
    }

    public function test_sin_index_construido_no_revienta_con_un_500()
    {
        // Es el estado normal en local: se desarrolla contra el servidor de Vite y aquí
        // no hay ningún index.html. Un 500 mandaría a buscar un fallo que no existe.
        $this->assertFileDoesNotExist($this->index);

        $this->get('/login')->assertStatus(404);
    }
}
