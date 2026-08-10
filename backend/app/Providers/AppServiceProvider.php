<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Los listeners del Sistema no se registran aquí: Laravel descubre solo los
        // métodos handle* de app/Listeners por el tipo de su primer parámetro, incluidos
        // los tipos unión. Registrarlos además a mano los dispararía dos veces.
    }
}
