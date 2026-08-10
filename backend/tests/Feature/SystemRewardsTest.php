<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemRewardsTest extends TestCase
{
    use RefreshDatabase;

    private function entreno(array $override = []): array
    {
        return array_merge([
            'mode'             => 'gym',
            'date'             => '2026-08-10 18:00:00',
            'duration_minutes' => 45,
            'exercises'        => [[
                'name' => 'Press banca',
                'sets' => [['weight_kg' => 80, 'reps' => 5]],
            ]],
        ], $override);
    }

    public function test_guardar_un_entreno_devuelve_el_bloque_system_con_xp()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/workouts', $this->entreno());

        $response->assertCreated();

        // 50 de base + 6 por los 30 minutos por encima del mínimo
        // + 30 del récord + 2 del primer día de racha
        $this->assertSame(88, $response->json('system.xp_gained'));
        $this->assertSame('Press banca', $response->json('system.records.0.exercise'));
        $this->assertSame(1, $response->json('system.progress.level'));
        $this->assertSame('E', $response->json('system.progress.rank'));
    }

    public function test_un_entreno_de_menos_de_quince_minutos_no_da_xp_de_entreno()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson('/api/workouts', $this->entreno(['duration_minutes' => 10, 'exercises' => []]));

        $this->assertDatabaseMissing('xp_events', ['user_id' => $user->id, 'source' => 'workout']);

        // Lo único que gana es el punto de racha por haber tenido actividad hoy
        $this->assertSame(2, $response->json('system.xp_gained'));
    }

    public function test_el_tercer_entreno_del_dia_no_puntua()
    {
        $user = User::factory()->create();

        // 58 el primero (56 + 2 de racha), 56 el segundo, 0 el tercero
        $this->actingAs($user)->postJson('/api/workouts', $this->entreno(['exercises' => []]));
        $this->actingAs($user)->postJson('/api/workouts', $this->entreno(['exercises' => []]));
        $tercero = $this->actingAs($user)->postJson('/api/workouts', $this->entreno(['exercises' => []]));

        $tercero->assertCreated();
        $this->assertSame(0, $tercero->json('system.xp_gained'), 'el entreno se guarda pero no da XP');
        $this->assertDatabaseCount('workouts', 3);
    }

    public function test_subir_de_nivel_se_anuncia_en_la_respuesta()
    {
        $user = User::factory()->create();

        // Primer entreno: 56 + 2 de racha. Segundo: 56 más → 114, pasa el umbral de 100.
        $this->actingAs($user)->postJson('/api/workouts', $this->entreno(['exercises' => []]));
        $segundo = $this->actingAs($user)->postJson('/api/workouts', $this->entreno(['exercises' => []]));

        $this->assertSame(['from' => 1, 'to' => 2], $segundo->json('system.level_up'));
    }

    public function test_registrar_agua_devuelve_tambien_el_bloque_system()
    {
        $user = User::factory()->create(['water_goal_ml' => 500]);

        // La primera lectura del día genera las misiones
        $this->actingAs($user)->getJson('/api/system/today');

        $response = $this->actingAs($user)->postJson('/api/water', ['amount_ml' => 500]);

        $response->assertCreated();
        $this->assertContains('water', $response->json('system.quests_completed'));
    }

    public function test_la_racha_sube_con_dias_seguidos_de_actividad()
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/workouts', $this->entreno([
            'date' => '2026-08-10 18:00:00', 'exercises' => [],
        ]));
        $segundo = $this->actingAs($user)->postJson('/api/workouts', $this->entreno([
            'date' => '2026-08-11 18:00:00', 'exercises' => [],
        ]));

        $this->assertSame(2, $segundo->json('system.progress.current_streak'));
    }
}
