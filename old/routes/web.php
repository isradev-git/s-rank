<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureAuthenticated;

// Aquí vivían tres rutas GET de mantenimiento sin autenticar (migrate:fresh,
// reset de la contraseña del admin y recarga del catálogo) con las credenciales
// escritas en claro. Se eliminaron al archivar FitLoop. No se reponen.

Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::middleware(EnsureAuthenticated::class)->group(function () {
    Route::get('/', function () {
        return view('dashboard');
    });

    Route::get('/history', function () {
        return view('history');
    });

    // Informe de salud unificado (para médico/nutricionista) + PDF
    Route::get('/informe-salud', [\App\Http\Controllers\ReportController::class, 'show']);
    Route::get('/informe-salud/pdf', [\App\Http\Controllers\ReportController::class, 'pdf']);

    Route::get('/profile', function () {
        return view('profile');
    });

    Route::get('/log', function () {
        $queryString = request()->getQueryString();
        $target = '/training' . ($queryString ? ('?' . $queryString) : '');
        return redirect($target);
    });

    Route::get('/logros', function () {
        return view('achievements');
    });

    Route::get('/training', function () {
        return view('training');
    });

    Route::get('/tools/1rm', function () {
        return view('tools.1rm');
    });

    Route::get('/tools/timer', function () {
        return view('tools.timer');
    });

    Route::get('/tools/explore', function () {
        return view('tools.explore');
    });

    // ── Nutrición ──────────────────────────────────────────────────────────
    Route::get('/nutrition', function () {
        return view('nutrition.dashboard');
    });

    Route::get('/nutrition/log', function () {
        return view('nutrition.log');
    });

    Route::get('/nutrition/history', function () {
        return view('nutrition.history');
    });

    Route::get('/nutrition/recipes', function () {
        return view('nutrition.recipes');
    });

    Route::get('/nutrition/ingredients', function () {
        return view('nutrition.ingredients');
    });
});

