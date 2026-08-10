<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ImportSqlite extends Command
{
    protected $signature = 'srank:import-sqlite
                            {path : Ruta al database.sqlite de FitLoop}
                            {--force : No preguntar antes de vaciar las tablas de destino}';

    protected $description = 'Copia los datos de FitLoop (SQLite) a la base MySQL de S-RANK';

    /**
     * Orden de inserción: las tablas padre van antes que las que las referencian.
     */
    private const TABLES = [
        'users',
        'exercises',
        'food_items',
        'recipes',
        'templates',
        'template_exercises',
        'workouts',
        'exercise_sets',
        'weight_logs',
        'nutrition_goals',
        'meal_logs',
        'water_logs',
        'supplement_logs',
    ];

    public function handle(): int
    {
        $path = $this->argument('path');

        if (! is_file($path)) {
            $this->error("No existe el fichero: {$path}");

            return self::FAILURE;
        }

        Config::set('database.connections.sqlite_legacy.database', realpath($path));
        $source = DB::connection('sqlite_legacy');

        if (! $this->option('force')
            && ! $this->confirm('Esto BORRA el contenido actual de las tablas de destino. ¿Seguir?', false)) {
            return self::FAILURE;
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (array_reverse(self::TABLES) as $table) {
            DB::table($table)->delete();
        }

        $report = [];

        foreach (self::TABLES as $table) {
            $count = 0;

            $source->table($table)->orderBy('id')->chunk(500, function ($rows) use ($table, &$count) {
                $insert = array_map(fn ($row) => (array) $row, $rows->all());
                DB::table($table)->insert($insert);
                $count += count($insert);
            });

            $report[] = [$table, $count];
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $this->table(['tabla', 'filas'], $report);
        $this->info('Importación terminada.');

        return self::SUCCESS;
    }
}
