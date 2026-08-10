<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

/**
 * Gestión de usuarios — solo accesible para administradores.
 * Las rutas están protegidas por el middleware EnsureAdmin.
 *
 * GET    /api/admin/users          → listar todos los usuarios
 * POST   /api/admin/users          → crear un usuario nuevo
 * DELETE /api/admin/users/{user}   → eliminar un usuario
 */
class UserManagementController extends Controller
{
    /**
     * Devuelve la lista de todos los usuarios registrados.
     */
    public function index(): JsonResponse
    {
        $users = User::orderBy('created_at')
            ->get(['id', 'name', 'email', 'is_admin', 'created_at']);

        return response()->json(['users' => $users]);
    }

    /**
     * Crea un nuevo usuario (solo admin).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'string', Password::min(8)->letters()->numbers()],
        ]);

        $user = User::create([
            'name'        => $validated['name'],
            'email'       => $validated['email'],
            'password'    => Hash::make($validated['password']),
            'is_admin'    => false,
            'weekly_goal' => 3,
            'main_goal'   => 'health',
        ]);

        return response()->json([
            'message' => "Usuario {$user->name} creado correctamente.",
            'user'    => $user->only(['id', 'name', 'email', 'is_admin', 'created_at']),
        ], 201);
    }

    /**
     * Elimina un usuario.
     * Un administrador no puede eliminarse a sí mismo.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        if ($request->user()->id === $user->id) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta desde aquí.'], 403);
        }

        // Revocar tokens antes de eliminar
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => "Usuario {$user->name} eliminado correctamente."]);
    }
}
