<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->encryptCookies(except: ['fitloop_token']);

        // Sin esto, Laravel redirige al invitado a la ruta «login», que aquí no existe:
        // el middleware revienta con RouteNotFoundException antes de que el manejador de
        // excepciones pueda intervenir, y un 401 legítimo sale como 500. Devolviendo null
        // no hay redirección y la excepción de autenticación llega entera.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Todo lo que cuelga de /api responde en JSON, mande el cliente la cabecera
        // Accept o no. Sin esto, una petición sin `Accept: application/json` a una ruta
        // autenticada acaba en 500: Laravel intenta redirigir a la pantalla de login,
        // que en una API no existe. El 401 tiene que llegar como 401.
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();
