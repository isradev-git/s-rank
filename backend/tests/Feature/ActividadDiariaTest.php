<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Los pasos son actividad diaria, no entrenamiento. La diferencia tiene dos mitades y
 * las dos importan: NO dan XP de entreno, pero SÍ marcan el día como activo y mantienen
 * la racha. Hay quien usa la aplicación solo para eso.
 */
class ActividadDiariaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guardar_pasos_mueve_la_racha_y_devuelve_el_bloque_system()
    {
        $user = User::factory()->create();

        $respuesta = $this->actingAs($user, 'sanctum')
            ->putJson('/api/activity', [
                'date'            => now()->toDateString(),
                'steps'           => 8200,
                'calories_burned' => 0,
            ])
            ->assertOk();

        $this->assertSame(1, $respuesta->json('system.progress.current_streak'));
    }

    public function test_guardar_pasos_no_da_xp_de_entreno()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')
            ->putJson('/api/activity', [
                'date'            => now()->toDateString(),
                'steps'           => 8200,
                'calories_burned' => 0,
            ])
            ->assertOk();

        // El bonus de racha sí puede sumar; lo que no puede aparecer es XP de entreno.
        // La tabla es `xp_events` y la columna `source`, comprobado en la migración
        // 2026_08_10_000004 y en XpLedger::award().
        $this->assertDatabaseMissing('xp_events', [
            'user_id' => $user->id,
            'source'  => 'workout',
        ]);
    }
}
