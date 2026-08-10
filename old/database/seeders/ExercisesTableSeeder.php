<?php

namespace Database\Seeders;

use App\Models\Exercise;
use Illuminate\Database\Seeder;

class ExercisesTableSeeder extends Seeder
{
    public function run(): void
    {
        if (Exercise::count() > 0) return;

        $exercises = [
            // Pierna
            ['name' => 'Sentadilla',            'category' => 'Pierna',   'muscle_group' => 'Cuádriceps'],
            ['name' => 'Sentadilla Búlgara',    'category' => 'Pierna',   'muscle_group' => 'Cuádriceps/Glúteo'],
            ['name' => 'Prensa de Piernas',     'category' => 'Pierna',   'muscle_group' => 'Cuádriceps'],
            ['name' => 'Peso Muerto',           'category' => 'Pierna',   'muscle_group' => 'Isquios/Espalda'],
            ['name' => 'Peso Muerto Rumano',    'category' => 'Pierna',   'muscle_group' => 'Isquios/Glúteo'],
            ['name' => 'Zancadas',              'category' => 'Pierna',   'muscle_group' => 'Cuádriceps/Glúteo'],
            ['name' => 'Hip Thrust',            'category' => 'Pierna',   'muscle_group' => 'Glúteo'],
            ['name' => 'Curl de Femoral',       'category' => 'Pierna',   'muscle_group' => 'Isquios'],
            // Tracción
            ['name' => 'Dominadas',             'category' => 'Tracción', 'muscle_group' => 'Espalda'],
            ['name' => 'Remo con Barra',        'category' => 'Tracción', 'muscle_group' => 'Espalda'],
            ['name' => 'Remo con Mancuerna',    'category' => 'Tracción', 'muscle_group' => 'Espalda'],
            ['name' => 'Jalón al Pecho',        'category' => 'Tracción', 'muscle_group' => 'Espalda/Bíceps'],
            ['name' => 'Curl de Bíceps',        'category' => 'Tracción', 'muscle_group' => 'Bíceps'],
            ['name' => 'Curl Martillo',         'category' => 'Tracción', 'muscle_group' => 'Bíceps/Braquial'],
            // Empuje
            ['name' => 'Press Banca',           'category' => 'Empuje',   'muscle_group' => 'Pecho'],
            ['name' => 'Press Banca Inclinado', 'category' => 'Empuje',   'muscle_group' => 'Pecho Superior'],
            ['name' => 'Press Militar',         'category' => 'Empuje',   'muscle_group' => 'Hombros'],
            ['name' => 'Press Arnold',          'category' => 'Empuje',   'muscle_group' => 'Hombros'],
            ['name' => 'Fondos',                'category' => 'Empuje',   'muscle_group' => 'Pecho/Tríceps'],
            ['name' => 'Flexiones',             'category' => 'Empuje',   'muscle_group' => 'Pecho/Tríceps'],
            ['name' => 'Elevaciones Laterales', 'category' => 'Empuje',   'muscle_group' => 'Hombros'],
            ['name' => 'Tríceps Polea',         'category' => 'Empuje',   'muscle_group' => 'Tríceps'],
            // Core
            ['name' => 'Plancha',               'category' => 'Core',     'muscle_group' => 'Abdomen'],
            ['name' => 'Crunch Abdominal',      'category' => 'Core',     'muscle_group' => 'Abdomen'],
            ['name' => 'Rueda Abdominal',       'category' => 'Core',     'muscle_group' => 'Core'],
            ['name' => 'Russian Twist',         'category' => 'Core',     'muscle_group' => 'Oblicuos'],
            // Cardio
            ['name' => 'Burpees',              'category' => 'Cardio',   'muscle_group' => 'Full Body'],
            ['name' => 'Saltos al Cajón',      'category' => 'Cardio',   'muscle_group' => 'Piernas/Glúteo'],
            ['name' => 'Mountain Climbers',    'category' => 'Cardio',   'muscle_group' => 'Core/Full Body'],
            // Natación
            ['name' => 'Crol',                 'category' => 'Natación', 'muscle_group' => 'Full Body'],
            ['name' => 'Braza',                'category' => 'Natación', 'muscle_group' => 'Full Body'],
            ['name' => 'Espalda',              'category' => 'Natación', 'muscle_group' => 'Espalda/Hombros'],
            ['name' => 'Mariposa',             'category' => 'Natación', 'muscle_group' => 'Full Body'],
        ];

        foreach ($exercises as $data) {
            Exercise::create($data);
        }
    }
}
