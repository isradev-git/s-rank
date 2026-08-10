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

    /**
     * Hash de una contraseña aleatoria que nadie conoce, contra el que se compara
     * cuando el correo no existe. Sirve para que comprobar una cuenta inexistente
     * cueste el mismo tiempo que comprobar una real: ver el comentario de login().
     */
    private const HASH_SENUELO = '$2y$12$96UFsKhZ57oAENNGyJK24.Y6lWc6rcvyVJPj8ACa0yYBIBzcKMsda';

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

        // Se comprueba el hash siempre, exista el usuario o no. Saltarse bcrypt cuando
        // el correo no está registrado hace que la respuesta vuelva dos órdenes de
        // magnitud antes, y ese tiempo dice qué cuentas existen: la misma fuga que
        // forgot-password evita respondiendo siempre lo mismo.
        $correcta = Hash::check($request->password, $user?->password ?? self::HASH_SENUELO);

        if (! $user || ! $correcta) {
            throw ValidationException::withMessages([
                'email' => ['Credenciales incorrectas.'],
            ]);
        }

        return response()->json([
            'access_token' => $user->createToken('auth_token')->plainTextToken,
            'token_type'   => 'Bearer',
            'user_name'    => $user->name,
            'is_admin'     => (bool) $user->is_admin,
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada correctamente.']);
    }
}
