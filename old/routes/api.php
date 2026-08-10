<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TemplateController;
use App\Http\Controllers\Api\WorkoutController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\ExerciseController;
use App\Http\Controllers\Api\AchievementController;
use App\Http\Controllers\Api\NutritionGoalController;
use App\Http\Controllers\Api\FoodController;
use App\Http\Controllers\Api\MealLogController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\WaterController;
use App\Http\Controllers\Api\SupplementController;
use App\Http\Controllers\Api\UserManagementController;
use App\Http\Controllers\Api\ActivityController;
use App\Http\Middleware\EnsureAdmin;

// Solo login (el registro público está desactivado)
Route::prefix('auth')->middleware('throttle:5,1')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});


Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/stats', [DashboardController::class, 'stats']);
    Route::get('/stats/heatmap', [DashboardController::class, 'heatmap']);
    Route::get('/stats/calendar', [DashboardController::class, 'calendar']);
    Route::get('/stats/reports', [DashboardController::class, 'reports']);
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::post('/templates', [TemplateController::class, 'store']);
    Route::put('/templates/{template}', [TemplateController::class, 'update']);

    Route::get('/workouts', [WorkoutController::class, 'index']);
    Route::post('/workouts', [WorkoutController::class, 'store']);
    Route::get('/workouts/{workout}', [WorkoutController::class, 'show']);
    Route::put('/workouts/{workout}', [WorkoutController::class, 'update']);
    Route::delete('/workouts/{workout}', [WorkoutController::class, 'destroy']);

    Route::delete('/templates/{template}', [TemplateController::class, 'destroy']);

    Route::get('/profile', [ProfileController::class, 'show']);
    Route::put('/user/profile', [ProfileController::class, 'update']);
    Route::put('/user/password', [ProfileController::class, 'changePassword']);
    Route::get('/user/weight-history', [ProfileController::class, 'weightHistory']);
    Route::delete('/user', [ProfileController::class, 'destroy']);

    Route::get('/exercises', [ExerciseController::class, 'index']);
    Route::get('/exercises/suggestions', [ExerciseController::class, 'suggestions']);
    Route::get('/exercises/history', [ExerciseController::class, 'history']);
    Route::get('/exercises/last-session', [ExerciseController::class, 'lastSession']);
    Route::get('/exercises/progress', [ExerciseController::class, 'progress']);
    Route::get('/exercises/records', [ExerciseController::class, 'records']);

    Route::get('/achievements', [AchievementController::class, 'index']);

    // ── Nutrición ──────────────────────────────────────────────────────────
    // Objetivo nutricional del usuario
    Route::get('/nutrition/goal', [NutritionGoalController::class, 'show']);
    Route::put('/nutrition/goal', [NutritionGoalController::class, 'update']);
    Route::post('/nutrition/goal', [NutritionGoalController::class, 'update']); // alias POST para el wizard

    // Catálogo de alimentos
    Route::get('/foods', [FoodController::class, 'index']);
    Route::get('/foods/categories', [FoodController::class, 'categories']);
    Route::get('/foods/all', [FoodController::class, 'all']);
    Route::post('/foods', [FoodController::class, 'store']);
    Route::post('/foods/{food}/image', [FoodController::class, 'uploadImage']);
    Route::put('/foods/{food}', [FoodController::class, 'update']);
    Route::delete('/foods/{food}', [FoodController::class, 'destroy']);

    // Registro de comidas
    Route::get('/meals', [MealLogController::class, 'index']);
    Route::post('/meals', [MealLogController::class, 'store']);
    Route::delete('/meals/{uuid}', [MealLogController::class, 'destroy']);
    Route::get('/meals/history', [MealLogController::class, 'history']);

    // Recetas
    Route::get('/recipes', [RecipeController::class, 'index']);
    Route::post('/recipes', [RecipeController::class, 'store']);
    Route::get('/recipes/recommended', [RecipeController::class, 'recommended']);
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show']);
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy']);
    Route::post('/recipes/{recipe}/image', [RecipeController::class, 'uploadImage']);

    // Agua / hidratación
    Route::get('/water', [WaterController::class, 'index']);
    Route::post('/water', [WaterController::class, 'store']);
    Route::delete('/water/{id}', [WaterController::class, 'destroy']);
    Route::put('/water/goal', [WaterController::class, 'updateGoal']);

    // Actividad diaria (pasos + calorías del reloj)
    Route::get('/activity', [ActivityController::class, 'index']);
    Route::put('/activity', [ActivityController::class, 'upsert']);

    // Suplementos diarios
    Route::get('/supplements', [SupplementController::class, 'index']);
    Route::put('/supplements', [SupplementController::class, 'upsert']);
    Route::delete('/supplements', [SupplementController::class, 'reset']);

    // Admin
    Route::middleware(EnsureAdmin::class)->group(function () {
        Route::get('/admin/users', [UserManagementController::class, 'index']);
        Route::post('/admin/users', [UserManagementController::class, 'store']);
        Route::delete('/admin/users/{user}', [UserManagementController::class, 'destroy']);
        Route::put('/admin/users/{user}/password', [UserManagementController::class, 'changePassword']);
    });
});
