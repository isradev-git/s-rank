<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Los usuarios tienen UUID. Cualquier columna que apunte a un usuario tiene que ser
 * de texto, no un entero. SQLite no tipa las columnas, así que una de estas mal puesta
 * pasa los tests sin protestar y solo revienta en MySQL, en producción, con un 500
 * imposible de leer. Por eso aquí se mira el tipo declarado y no el comportamiento.
 */
class UuidSchemaTest extends TestCase
{
    use RefreshDatabase;

    public static function columnasQueApuntanAUnUsuario(): array
    {
        return [
            'tokens de Sanctum'  => ['personal_access_tokens', 'tokenable_id'],
            'progreso'           => ['user_progress', 'user_id'],
            'misiones diarias'   => ['daily_quests', 'user_id'],
            'logros'             => ['user_achievements', 'user_id'],
            'libro mayor de XP'  => ['xp_events', 'user_id'],
            'entrenos'           => ['workouts', 'user_id'],
            'comidas'            => ['meal_logs', 'user_id'],
            'agua'               => ['water_logs', 'user_id'],
            'peso'               => ['weight_logs', 'user_id'],
            'suplementos'        => ['supplement_logs', 'user_id'],
            'plantillas'         => ['templates', 'user_id'],
            'alimentos'          => ['food_items', 'user_id'],
            'recetas'            => ['recipes', 'user_id'],
            'objetivo nutricional' => ['nutrition_goals', 'user_id'],
        ];
    }

    #[DataProvider('columnasQueApuntanAUnUsuario')]
    public function test_la_columna_es_de_texto_y_no_un_entero(string $tabla, string $columna)
    {
        $tipo = Schema::getColumnType($tabla, $columna);

        $this->assertContains(
            $tipo,
            ['char', 'varchar', 'string', 'text'],
            "{$tabla}.{$columna} es «{$tipo}». Un id de usuario es un UUID de 36 caracteres: "
            .'con un entero MySQL trunca el valor y la escritura falla en producción.'
        );
    }
}
