<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea usuarios iniciales solo cuando está habilitado por entorno.
 * Las credenciales deben venir de variables de entorno para evitar
 * secretos hardcodeados en el repositorio.
 */
class UsersSeeder extends Seeder
{
    private const USERS = [
        [
            'name' => 'Admin',
            'email_env' => 'SEED_ADMIN_EMAIL',
            'password_env' => 'SEED_ADMIN_PASSWORD',
            'default_email' => 'admin@thevikingmonkey.com',
            'is_admin' => true,
            'weekly_goal' => 3,
            'main_goal' => 'health',
        ],
        [
            'name' => 'Isra',
            'email_env' => 'SEED_ISRA_EMAIL',
            'password_env' => 'SEED_ISRA_PASSWORD',
            'default_email' => 'isra@thevikingmonkey.com',
            'is_admin' => false,
            'weekly_goal' => 3,
            'main_goal' => 'strength',
        ],
        [
            'name' => 'Slavka',
            'email_env' => 'SEED_SLAVKA_EMAIL',
            'password_env' => 'SEED_SLAVKA_PASSWORD',
            'default_email' => 'slavka@thevikingmonkey.com',
            'is_admin' => false,
            'weekly_goal' => 3,
            'main_goal' => 'health',
        ],
    ];

    public function run(): void
    {
        $enabled = filter_var(
            (string) env('SEED_DEFAULT_USERS', app()->environment(['local', 'testing']) ? 'true' : 'false'),
            FILTER_VALIDATE_BOOL
        );

        if (!$enabled) {
            $this->command?->info('UsersSeeder: omitido porque SEED_DEFAULT_USERS está desactivado.');
            return;
        }

        $emails = array_map(
            fn (array $user): string => (string) env($user['email_env'], $user['default_email']),
            self::USERS
        );

        $existing = User::whereIn('email', $emails)->pluck('email')->toArray();

        foreach (self::USERS as $userConfig) {
            $email = (string) env($userConfig['email_env'], $userConfig['default_email']);
            $password = (string) env($userConfig['password_env'], $userConfig['default_password'] ?? '');

            if (in_array($email, $existing, true)) {
                continue;
            }

            if ($password === '') {
                $this->command?->warn(sprintf(
                    'UsersSeeder: omitido %s porque falta %s.',
                    $email,
                    $userConfig['password_env']
                ));
                continue;
            }

            User::create([
                'name'        => $userConfig['name'],
                'email'       => $email,
                'password'    => Hash::make($password),
                'is_admin'    => $userConfig['is_admin'],
                'weekly_goal' => $userConfig['weekly_goal'],
                'main_goal'   => $userConfig['main_goal'],
            ]);

            $this->command?->info(sprintf('UsersSeeder: creado %s.', $email));
        }
    }
}
