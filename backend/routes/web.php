<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Aquí vivía /informe-salud, que autenticaba leyendo una cookie propia. Estaba caído
// en producción (500) por dos motivos a la vez: redirigía a una ruta «login» que no
// existe, y su middleware validaba el token sin llegar a autenticar a nadie, así que
// el controlador recibía un usuario vacío. No lo cubría ningún test.
//
// La lógica del informe sigue en ReportController, sin ruta que la alcance. La fase 1.5
// la vuelve a publicar con una URL firmada (URL::temporarySignedRoute), que da un enlace
// que se le puede pasar al médico sin abrir una segunda vía de autenticación.

/**
 * El frontend es una aplicación de una sola página: /login, /perfil y las demás no son
 * rutas del servidor, las resuelve React en el navegador. Al recargar o al entrar por un
 * enlace directo, quien recibe la petición es Laravel, y tiene que devolver el mismo
 * index.html para que React decida qué pintar.
 *
 * Lo que este cierre NO puede hacer es tragarse los 404 de la API. Devolver HTML donde el
 * cliente espera JSON es exactamente el fallo que ya costó un despliegue aquí: la app
 * recibe una página entera, intenta leerla como JSON y enseña un error que no dice nada.
 */
Route::fallback(function (Request $request) {
    $index = public_path('index.html');

    if ($request->is('api/*') || $request->is('sanctum/*') || ! file_exists($index)) {
        abort(404);
    }

    return response(file_get_contents($index))
        ->header('Content-Type', 'text/html; charset=UTF-8')
        // El index no se guarda en caché nunca. Es lo único que apunta a los ficheros de
        // JavaScript y CSS, que sí llevan hash en el nombre y se pueden guardar para
        // siempre. Si el navegador se queda con un index viejo, sigue pidiendo un
        // JavaScript que el despliegue nuevo ya no tiene, y la app no arranca.
        ->header('Cache-Control', 'no-cache');
});
