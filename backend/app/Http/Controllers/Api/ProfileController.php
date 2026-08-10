<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WeightLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function show(Request $request)
    {
        return response()->json(['user' => $request->user()]);
    }

    public function update(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'gender' => 'nullable|in:male,female',
            'age' => 'nullable|integer',
            'main_goal' => 'nullable|string',
            'weight' => 'nullable|numeric',
            'height' => 'nullable|numeric',
            'weekly_goal' => 'nullable|integer',
        ]);

        // Registrar el cambio de peso — usa updateOrCreate para evitar violar
        // la constraint única [user_id, date] si se actualiza varias veces el mismo día.
        if (isset($validated['weight']) && $validated['weight'] != $user->weight) {
            WeightLog::updateOrCreate(
                ['user_id' => $user->id, 'date' => now()->toDateString()],
                ['weight' => $validated['weight']]
            );
        }

        $user->update($validated);

        return response()->json(['message' => 'Profile updated', 'user' => $user]);
    }

    public function weightHistory(Request $request)
    {
        $user = $request->user();

        $history = WeightLog::where('user_id', $user->id)
            ->orderBy('date', 'asc')
            ->get(['date', 'weight']);

        return response()->json($history);
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $user = $request->user();

        if (! Hash::check($request->current_password, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['La contraseña actual es incorrecta.'],
            ]);
        }

        $user->update(['password' => Hash::make($request->new_password)]);
        $user->tokens()->delete();

        return response()->json(['message' => 'Contraseña actualizada. Por favor vuelve a iniciar sesión.']);
    }

    public function destroy(Request $request)
    {
        $user = $request->user();
        
        // Optional: Delete related data if not cascading (Laravel foreign keys usually handle this or soft deletes)
        // For safely, let's just delete the user, relying on DB cascade or model events.
        
        $user->tokens()->delete(); // Revoke tokens
        $user->delete();

        return response()->json(['message' => 'Account deleted']);
    }
}
