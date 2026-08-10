<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\ResetPasswordCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Último código generado. Solo lo lee la suite de tests: en producción el código
     * viaja por correo y nunca sale en ninguna respuesta.
     */
    public static ?string $lastCodeForTesting = null;

    public function register(Request $request)
    {
        $validated = $request->validate([
            'name'     => 'required|string|max:60',
            'email'    => 'required|email|max:190|unique:users,email',
            'password' => 'required|string|min:8|max:200',
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
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type'   => 'Bearer',
            'user_name'    => $user->name,
            'is_admin'     => false,
        ], 201);
    }

    /**
     * Manda un código de seis cifras. Responde 200 exista o no el correo: decir
     * «ese usuario no existe» es regalar una lista de cuentas válidas.
     */
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email']);

        $user = User::where('email', $request->email)->first();

        if ($user) {
            $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            self::$lastCodeForTesting = $code;

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );

            // Si el SMTP falla, el que pregunta no puede enterarse por la respuesta:
            // contestar distinto aquí convertiría este endpoint en un comprobador de
            // qué correos están registrados, que es justo lo que evita responder
            // siempre lo mismo. El fallo queda en el log, que es donde hay que mirarlo.
            try {
                $user->notify(new ResetPasswordCode($code));
            } catch (\Throwable $e) {
                report($e);
            }
        }

        return response()->json([
            'message' => 'Si ese correo está registrado, te hemos enviado un código.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validated = $request->validate([
            'email'    => 'required|email',
            'code'     => 'required|string|size:6',
            'password' => 'required|string|min:8|max:200',
        ]);

        $row = DB::table('password_reset_tokens')->where('email', $validated['email'])->first();
        $user = User::where('email', $validated['email'])->first();

        $valid = $row
            && $user
            && \Illuminate\Support\Carbon::parse($row->created_at)->addMinutes(30)->isFuture()
            && Hash::check($validated['code'], $row->token);

        if (! $valid) {
            throw ValidationException::withMessages([
                'code' => ['El código no es válido o ha caducado.'],
            ]);
        }

        $user->update(['password' => Hash::make($validated['password'])]);
        $user->tokens()->delete();

        DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return response()->json(['message' => 'Contraseña cambiada. Ya puedes entrar.']);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'access_token' => $token,
            'token_type'   => 'Bearer',
            'user_name'    => $user->name,
            'is_admin'     => (bool) $user->is_admin,
        ])->cookie('fitloop_token', $token, 60 * 24 * 7, '/', null, false, true);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.'])
            ->withoutCookie('fitloop_token');
    }
}
