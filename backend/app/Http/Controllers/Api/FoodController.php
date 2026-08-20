<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FoodItem;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Controlador del catálogo de alimentos.
 *
 * Endpoints:
 * - GET  /api/foods?search=pollo&category=carnes  → buscar alimentos (para log de comidas)
 * - GET  /api/foods/all                           → todos los alimentos paginados (para vista ingredientes)
 * - GET  /api/foods/categories                    → listar categorías disponibles
 * - POST /api/foods                               → crear alimento (sistema si from_ingredients=true)
 * - PUT  /api/foods/{food}                        → editar alimento existente
 * - DELETE /api/foods/{food}                      → eliminar alimento propio
 */
class FoodController extends Controller
{
    /**
     * Busca alimentos en el catálogo (usado en el log de comidas).
     *
     * Permite filtrar por nombre (búsqueda tipo "like") y por categoría.
     * Primero aparecen los alimentos verificados (del sistema),
     * después los creados por el usuario.
     *
     * Ejemplo de petición: GET /api/foods?search=pollo&limit=10
     */
    public function index(Request $request): JsonResponse
    {
        $search   = $request->string('search')->trim();
        $category = $request->string('category')->trim();
        $limit    = $request->integer('limit', 20);

        $query = FoodItem::query()
            ->where(function ($q) use ($request) {
                $q->whereNull('user_id')
                  ->orWhere('user_id', $request->user()->id);
            });

        if ($search->isNotEmpty()) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($category->isNotEmpty()) {
            $query->where('category', $category);
        }

        $foods = $query
            ->orderByDesc('is_verified')
            ->orderBy('name')
            ->limit($limit)
            ->get([
                'id', 'name', 'brand', 'category', 'unit',
                'calories_per_100g', 'protein_per_100g',
                'carbs_per_100g', 'fat_per_100g',
                'fiber_per_100g', 'sugar_per_100g', 'is_verified',
            ]);

        return response()->json(['foods' => $foods]);
    }

    /**
     * Lista TODOS los alimentos del catálogo con paginación y filtros.
     * Usado por la vista /nutrition/ingredients para gestionar el catálogo completo.
     *
     * Parámetros:
     * - search: búsqueda por nombre
     * - category: filtro por categoría
     * - page: página actual (paginación de 50 en 50)
     */
    public function all(Request $request): JsonResponse
    {
        $search   = $request->string('search')->trim();
        $category = $request->string('category')->trim();
        $perPage  = 50;

        $query = FoodItem::query()
            ->where(function ($q) use ($request) {
                // Muestra todos los del sistema + los del usuario actual
                $q->whereNull('user_id')
                  ->orWhere('user_id', $request->user()->id);
            });

        if ($search->isNotEmpty()) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($category->isNotEmpty()) {
            $query->where('category', $category);
        }

        // Primero los del sistema (is_verified), luego los del usuario, ambos alfabéticos
        $foods = $query
            ->orderByDesc('is_verified')
            ->orderBy('name')
            ->paginate($perPage, [
                'id', 'name', 'brand', 'category', 'unit',
                'calories_per_100g', 'protein_per_100g',
                'carbs_per_100g', 'fat_per_100g',
                'fiber_per_100g', 'sugar_per_100g',
                'is_verified', 'user_id', 'image_path',
            ]);

        return response()->json([
            'foods'        => $foods->items(),
            'total'        => $foods->total(),
            'current_page' => $foods->currentPage(),
            'last_page'    => $foods->lastPage(),
            'per_page'     => $foods->perPage(),
        ]);
    }

    /**
     * Retorna la lista de categorías únicas del catálogo.
     * Útil para el filtro de categorías en la búsqueda.
     */
    public function categories(): JsonResponse
    {
        $categories = FoodItem::whereNull('user_id')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return response()->json(['categories' => $categories]);
    }

    /**
     * Crea un alimento nuevo en el catálogo.
     *
     * Si viene con from_ingredients=true (desde la vista de ingredientes),
     * se guarda como alimento del sistema (is_verified=true, user_id=null)
     * para que todos los usuarios lo puedan ver y usar.
     *
     * Si viene desde el log de comidas (creación rápida personal),
     * se guarda como alimento del usuario (is_verified=false).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'              => 'required|string|max:200',
            'brand'             => 'nullable|string|max:100',
            'category'          => 'nullable|string|max:100',
            'unit'              => 'nullable|in:g,ml',
            'calories_per_100g' => 'required|numeric|min:0|max:9999',
            'protein_per_100g'  => 'nullable|numeric|min:0|max:999',
            'carbs_per_100g'    => 'nullable|numeric|min:0|max:999',
            'fat_per_100g'      => 'nullable|numeric|min:0|max:999',
            'fiber_per_100g'    => 'nullable|numeric|min:0|max:999',
            'sugar_per_100g'    => 'nullable|numeric|min:0|max:999',
            // true = se añade al catálogo global del sistema (visible para todos)
            'from_ingredients'  => 'nullable|boolean',
        ]);

        $fromIngredients = $request->boolean('from_ingredients', false);

        $food = FoodItem::create([
            'name'              => $validated['name'],
            'brand'             => $validated['brand']             ?? null,
            'category'          => $validated['category']          ?? null,
            'unit'              => $validated['unit']              ?? 'g',
            'calories_per_100g' => $validated['calories_per_100g'],
            'protein_per_100g'  => $validated['protein_per_100g']  ?? 0,
            'carbs_per_100g'    => $validated['carbs_per_100g']    ?? 0,
            'fat_per_100g'      => $validated['fat_per_100g']      ?? 0,
            'fiber_per_100g'    => $validated['fiber_per_100g']    ?? 0,
            'sugar_per_100g'    => $validated['sugar_per_100g']    ?? 0,
            // Desde ingredientes → sistema (user_id null). Desde log → personal (user_id del usuario)
            'user_id'           => $fromIngredients ? null : $request->user()->id,
            'is_verified'       => $fromIngredients,
        ]);

        return response()->json([
            'message' => 'Alimento creado correctamente.',
            'food'    => $food,
        ], 201);
    }

    /**
     * Edita un alimento existente del catálogo.
     *
     * Los alimentos del sistema (is_verified=true) pueden ser editados
     * por cualquier usuario para completar los macros que faltan.
     * Los alimentos del usuario solo los puede editar su dueño.
     */
    public function update(Request $request, FoodItem $food): JsonResponse
    {
        // Si el alimento es personal de OTRO usuario, denegar acceso.
        // Los alimentos del sistema (user_id = null) pueden ser editados por cualquiera
        // → así la comunidad puede completar los macros que faltan (retroalimentación).
        if ($food->user_id !== null && $food->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No tienes permiso para editar este alimento.'], 403);
        }

        $validated = $request->validate([
            'name'              => 'sometimes|string|max:200',
            'brand'             => 'nullable|string|max:100',
            'category'          => 'nullable|string|max:100',
            'unit'              => 'nullable|in:g,ml',
            'calories_per_100g' => 'sometimes|numeric|min:0|max:9999',
            'protein_per_100g'  => 'nullable|numeric|min:0|max:999',
            'carbs_per_100g'    => 'nullable|numeric|min:0|max:999',
            'fat_per_100g'      => 'nullable|numeric|min:0|max:999',
            'fiber_per_100g'    => 'nullable|numeric|min:0|max:999',
            'sugar_per_100g'    => 'nullable|numeric|min:0|max:999',
        ]);

        // Eliminamos del update los campos que vengan como null para no borrar
        // datos que ya existían (ej: no queremos poner proteínas a null si no se envió el campo)
        $updateData = array_filter($validated, fn($v) => $v !== null);

        $food->update($updateData);

        return response()->json([
            'message' => 'Alimento actualizado correctamente.',
            'food'    => $food->fresh(),
        ]);
    }

    /**
     * Sube o reemplaza la imagen de un alimento.
     *
     * Acepta multipart/form-data con campo "image" (jpeg|png|webp, máx 2MB).
     * Guarda el archivo en storage/app/public/nutrition/foods/ y actualiza
     * el campo image_path del alimento. Devuelve la URL pública de la imagen.
     *
     * Los alimentos del sistema también pueden recibir imagen (para enriquecer
     * el catálogo). Los alimentos personales solo los puede editar su dueño.
     */
    public function uploadImage(Request $request, FoodItem $food): JsonResponse
    {
        // Solo el dueño puede subir imagen a alimentos personales
        if ($food->user_id !== null && $food->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No tienes permiso para editar este alimento.'], 403);
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,webp|max:2048',
        ]);

        try {
            $disk = Storage::disk('uploads');

            // En algunos hostings la carpeta no existe inicialmente.
            if (! $disk->exists('nutrition/foods')) {
                $disk->makeDirectory('nutrition/foods');
            }

            // Si ya tenía imagen, la borramos para no acumular basura
            if ($food->image_path) {
                $disk->delete($food->image_path);
            }

            // Guardamos en public/uploads/nutrition/foods/{uuid}.{ext}
            $path = $request->file('image')->store('nutrition/foods', 'uploads');
            $food->update(['image_path' => $path]);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'No se pudo guardar la imagen en el servidor. Vuelve a intentarlo.',
            ], 500);
        }

        return response()->json([
            'message'    => 'Imagen subida correctamente.',
            'image_path' => $path,
            // Storage::disk('uploads')->url() y no asset('storage/...'): el disco apunta a
            // public/uploads y en Ginernet no se pueden crear symlinks, así que /storage
            // no existe. RecipeController ya lo hacía bien; esto lo iguala.
            'image_url'  => Storage::disk('uploads')->url($path),
        ]);
    }

    /**
     * Elimina un alimento personalizado del usuario.
     * Solo puede eliminar alimentos que ÉL mismo creó, nunca los del sistema.
     */
    public function destroy(Request $request, FoodItem $food): JsonResponse
    {
        // Alimentos del sistema: solo admin.
        if ($food->user_id === null) {
            if (! (bool) $request->user()->is_admin) {
                return response()->json(['message' => 'Solo un administrador puede eliminar alimentos del sistema.'], 403);
            }
        }

        // Solo el dueño puede eliminar su alimento
        if ($food->user_id !== $request->user()->id) {
            if ($food->user_id !== null || ! (bool) $request->user()->is_admin) {
                return response()->json(['message' => 'No tienes permiso para eliminar este alimento.'], 403);
            }
        }

        if ($food->image_path) {
            Storage::disk('uploads')->delete($food->image_path);
        }

        $food->delete();

        return response()->json(['message' => 'Alimento eliminado correctamente.']);
    }
}
