<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureAuthenticated;

// La app es Android. Lo único que sigue siendo una página web es el informe de salud,
// para poder pasarle un enlace al médico o al nutricionista.
Route::middleware(EnsureAuthenticated::class)->group(function () {
    Route::get('/informe-salud', [\App\Http\Controllers\ReportController::class, 'show']);
    Route::get('/informe-salud/pdf', [\App\Http\Controllers\ReportController::class, 'pdf']);
});
