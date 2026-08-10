<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Controlador para buscar y mostrar recetas.
 *
 * Endpoints:
 * - GET /api/recipes                      → listar todas las recetas
 * - GET /api/recipes?max_calories=500     → recetas dentro de un límite calórico
 * - GET /api/recipes?category=desayuno    → recetas por categoría
 * - GET /api/recipes/{recipe}             → detalle de una receta
 */
class RecipeController extends Controller
{
    /**
     * Lista recetas con filtros opcionales.
     *
     * Filtros disponibles:
     * - category: desayuno | almuerzo | cena | snack
     * - max_calories: máximo de calorías por porción (ej: 400)
     * - min_calories: mínimo de calorías por porción
     * - search: búsqueda por nombre
     * - difficulty: fácil | media | difícil
     *
     * Este endpoint es el más usado: "dame recetas de cena con máximo 400 calorías"
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $query = Recipe::query()
            ->where(function ($q) use ($request) {
                // Muestra recetas del sistema + recetas del usuario
                $q->where('is_system', true)
                  ->orWhere('user_id', $request->user()->id);
            });

        // Filtro por categoría (desayuno, almuerzo, cena, snack)
        if ($request->filled('category')) {
            $query->where('category', $request->string('category'));
        }

        // Filtro por máximo de calorías
        if ($request->filled('max_calories')) {
            $query->where('calories_per_serving', '<=', $request->integer('max_calories'));
        }

        // Filtro por mínimo de calorías
        if ($request->filled('min_calories')) {
            $query->where('calories_per_serving', '>=', $request->integer('min_calories'));
        }

        // Búsqueda por nombre
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Filtro por dificultad
        if ($request->filled('difficulty')) {
            $query->where('difficulty', $request->string('difficulty'));
        }

        // Ordenamos por calorías ascendente (las más ligeras primero)
        $recipes = $query
            ->orderBy('calories_per_serving')
            ->get([
                'id', 'name', 'description', 'category',
                'image_path',
                'calories_per_serving', 'protein_per_serving',
                'carbs_per_serving', 'fat_per_serving',
                'servings', 'prep_time_min', 'cook_time_min',
                'difficulty', 'image_url', 'is_system', 'user_id',
            ]);

        return response()->json(['recipes' => $recipes]);
    }

    /**
     * Muestra el detalle completo de una receta, incluyendo
     * ingredientes e instrucciones de preparación.
     *
     * @param Request $request
     * @param Recipe  $recipe
     * @return JsonResponse
     */
    public function show(Request $request, Recipe $recipe): JsonResponse
    {
        // Verificamos que el usuario pueda ver esta receta
        // (del sistema = todos pueden; personalizada = solo el dueño)
        if (!$recipe->is_system && $recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Receta no encontrada.'], 404);
        }

        return response()->json(['recipe' => $recipe]);
    }

    /**
     * Crea una receta personalizada para el usuario autenticado.
     *
     * El frontend envía el nombre, ingredientes (array de objetos {name, quantity})
     * e instrucciones. Los macros se calculan automáticamente si el usuario
     * proporciona los ingredientes con sus IDs de alimento del catálogo,
     * o bien los puede introducir manualmente como totales del plato.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'description'           => 'nullable|string|max:500',
            'category'              => 'required|in:desayuno,almuerzo,cena,snack',
            'calories_per_serving'  => 'required|numeric|min:0',
            'protein_per_serving'   => 'nullable|numeric|min:0',
            'carbs_per_serving'     => 'nullable|numeric|min:0',
            'fat_per_serving'       => 'nullable|numeric|min:0',
            'servings'              => 'nullable|integer|min:1',
            'prep_time_min'         => 'nullable|integer|min:0',
            'cook_time_min'         => 'nullable|integer|min:0',
            'ingredients'           => 'nullable|array',
            'ingredients.*.name'    => 'required|string',
            'ingredients.*.quantity'=> 'required|string',
            'instructions'          => 'nullable|string',
            'difficulty'            => 'nullable|in:fácil,media,difícil',
        ]);

        $recipe = Recipe::create([
            'user_id'               => $request->user()->id,
            // Las recetas creadas por usuarios se marcan como is_system=true
            // para que aparezcan en el catálogo general y todos puedan usarlas.
            // El user_id sigue guardado para saber quién la creó (y quién puede eliminarla).
            'is_system'             => true,
            'name'                  => $validated['name'],
            'description'           => $validated['description'] ?? null,
            'category'              => $validated['category'],
            'calories_per_serving'  => $validated['calories_per_serving'],
            'protein_per_serving'   => $validated['protein_per_serving'] ?? 0,
            'carbs_per_serving'     => $validated['carbs_per_serving']   ?? 0,
            'fat_per_serving'       => $validated['fat_per_serving']     ?? 0,
            'fiber_per_serving'     => 0,
            'servings'              => $validated['servings']      ?? 1,
            'prep_time_min'         => $validated['prep_time_min'] ?? 0,
            'cook_time_min'         => $validated['cook_time_min'] ?? 0,
            'ingredients'           => $validated['ingredients']   ?? [],
            'instructions'          => $validated['instructions']  ?? '',
            'difficulty'            => $validated['difficulty']    ?? 'fácil',
        ]);

        return response()->json(['recipe' => $recipe], 201);
    }

    /**
     * Elimina una receta del usuario.
     *
     * Solo puede eliminar recetas que ÉL mismo creó (user_id coincide).
     * Las recetas originales del sistema (user_id = null) no se pueden borrar.
     *
     * @param Request $request
     * @param Recipe  $recipe
     * @return JsonResponse
     */
    public function destroy(Request $request, Recipe $recipe): JsonResponse
    {
        // Recetas originales del sistema (sin user_id) no se pueden borrar
        if ($recipe->user_id === null) {
            return response()->json(['message' => 'No se pueden eliminar recetas del sistema.'], 403);
        }

        // Solo el creador puede eliminar su receta
        if ($recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No tienes permiso para eliminar esta receta.'], 403);
        }

        $recipe->delete();

        return response()->json(['message' => 'Receta eliminada.']);
    }

    /**
     * Sube o reemplaza la imagen de una receta.
     *
     * Acepta multipart/form-data con campo "image" (jpeg|png|webp, máx 2MB).
     * Guarda en storage/app/public/nutrition/recipes/ y actualiza image_path.
     * Solo puede subir imagen el creador de la receta (o cualquiera si is_system y user_id null).
     */
    public function uploadImage(Request $request, Recipe $recipe): JsonResponse
    {
        // Solo el creador puede editar la foto de recetas personales
        if ($recipe->user_id !== null && $recipe->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No tienes permiso para editar esta receta.'], 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        // Borramos la imagen anterior si existía (evitamos archivos huérfanos)
        if ($recipe->image_path) {
            Storage::disk('public')->delete($recipe->image_path);
        }

        // Guardamos en storage/app/public/nutrition/recipes/{uuid}.{ext}
        $path = $request->file('image')->store('nutrition/recipes', 'public');

        $recipe->update(['image_path' => $path]);

        return response()->json([
            'message'    => 'Imagen subida correctamente.',
            'image_path' => $path,
            'image_url'  => Storage::url($path),
        ]);
    }

    /**
     * Retorna recetas recomendadas basadas en las calorías disponibles del usuario.
     *
     * El frontend llama a este endpoint pasando cuántas calorías le quedan
     * en el día, y devolvemos recetas que caben en ese presupuesto calórico.
     *
     * Ejemplo: GET /api/recipes/recommended?remaining_calories=600&meal_type=cena
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recommended(Request $request): JsonResponse
    {
        $remainingCalories = $request->integer('remaining_calories', 500);
        $mealType          = $request->string('meal_type'); // desayuno, almuerzo, cena, snack

        // Mapeamos el tipo de comida a categoría de receta
        $categoryMap = [
            'breakfast' => 'desayuno',
            'lunch'     => 'almuerzo',
            'dinner'    => 'cena',
            'snack'     => 'snack',
        ];
        $category = $categoryMap[$mealType->value()] ?? null;

        $query = Recipe::query()
            ->where(function ($q) use ($request) {
                $q->where('is_system', true)
                  ->orWhere('user_id', $request->user()->id);
            })
            ->where('calories_per_serving', '<=', $remainingCalories);

        if ($category) {
            $query->where('category', $category);
        }

        // Devolvemos hasta 6 recetas aleatorias que caben en el presupuesto
        $recipes = $query
            ->inRandomOrder()
            ->limit(6)
            ->get([
                'id', 'name', 'description', 'category',
                'calories_per_serving', 'protein_per_serving',
                'carbs_per_serving', 'fat_per_serving',
                'prep_time_min', 'cook_time_min', 'difficulty', 'image_url', 'image_path',
            ]);

        return response()->json([
            'recipes'            => $recipes,
            'remaining_calories' => $remainingCalories,
        ]);
    }
}