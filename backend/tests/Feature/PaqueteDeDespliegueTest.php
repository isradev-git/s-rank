<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Lo que el paquete que se sube por FTP tiene que llevar dentro sí o sí.
 *
 * No prueba la aplicación: prueba el despliegue. Está aquí porque es el único sitio que
 * corre solo, y porque el fallo que cubre no se ve hasta estar en el servidor —donde
 * además aparece como una pantalla en blanco con una excepción de PHP, no como un 500
 * cualquiera.
 */
class PaqueteDeDespliegueTest extends TestCase
{
    /**
     * La raíz del subdominio necesita su propio index.php.
     *
     * Ginernet sirve el subdominio desde la carpeta raíz, no desde public/. El .htaccess
     * de la raíz reescribe a public/index.php, pero eso solo cubre las peticiones que
     * llegan a mod_rewrite: si Apache resuelve antes el DirectoryIndex y encuentra un
     * index.php ahí, ejecuta ese.
     *
     * Y ahí había uno: el de FitLoop, que busca la aplicación en ../../data/fitloop.
     * Como el paquete no traía ningún index.php de raíz, subirlo por FTP no lo
     * sobrescribía, y el sitio entero contestaba «No se pudo localizar la raiz de
     * Laravel». Con este fichero dentro, el despliegue deja de depender de lo que
     * quedara de antes.
     */
    public function test_la_raiz_lleva_un_index_que_entrega_la_peticion_a_public()
    {
        $raiz = base_path('index.php');

        $this->assertFileExists($raiz, 'Sin este fichero, un index.php viejo en la raíz del subdominio se queda mandando.');
        $this->assertStringContainsString("require __DIR__.'/public/index.php'", file_get_contents($raiz));
    }

    /**
     * El puente funciona porque `__DIR__` es del fichero incluido, no del que lo incluye:
     * dentro de public/index.php sigue valiendo .../public, así que `dirname(__DIR__)`
     * cae en la raíz, que es donde están vendor/ y bootstrap/. Si alguien cambiara
     * public/index.php por uno que resolviera la raíz desde el fichero que llama, el
     * puente dejaría de encontrarla.
     */
    public function test_public_localiza_la_raiz_por_su_propia_ubicacion()
    {
        $this->assertStringContainsString('dirname(__DIR__)', file_get_contents(public_path('index.php')));
    }
}
