<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemEndpointsTest extends TestCase
{
    use RefreshDatabase;

    public function test_today_genera_las_misiones_la_primera_vez_y_no_las_duplica()
    {
        $user = User::factory()->create();

        $primera = $this->actingAs($user)->getJson('/api/system/today')->assertOk();
        $claves = collect($primera->json('quests'))->pluck('key')->sort()->values();

        $segunda = $this->actingAs($user)->getJson('/api/system/today')->assertOk();

        $this->assertEquals($claves, collect($segunda->json('quests'))->pluck('key')->sort()->values());
        $this->assertSame(count($claves), \App\Models\DailyQuest::where('user_id', $user->id)->count());
    }

    public function test_today_devuelve_progreso_y_misiones_con_texto_en_castellano()
    {
        $user = User::factory()->create(['water_goal_ml' => 2000]);

        $response = $this->actingAs($user)->getJson('/api/system/today');

        $response->assertOk()
            ->assertJsonPath('progress.level', 1)
            ->assertJsonPath('progress.rank', 'E');

        $agua = collect($response->json('quests'))->firstWhere('key', 'water');
        $this->assertSame('Beber 2 litros de agua', $agua['label']);
    }

    public function test_profile_devuelve_las_cuatro_estadisticas_y_los_modulos()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/system/profile')->assertOk();

        $this->assertSame(
            ['consistency', 'endurance', 'strength', 'vitality'],
            collect($response->json('progress.stats'))->keys()->sort()->values()->all()
        );
        $this->assertSame(['entrenamiento', 'nutrición'], $response->json('modules'));
    }

    public function test_achievements_devuelve_los_cuarenta_con_su_rareza()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/system/achievements')->assertOk();

        $this->assertCount(40, $response->json('achievements'));
        $this->assertSame(0, $response->json('unlocked_count'));
    }

    public function test_se_puede_marcar_a_mano_la_mision_opcional()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/system/today');

        $opcional = \App\Models\DailyQuest::where('user_id', $user->id)->where('is_optional', true)->first();

        $this->actingAs($user)
            ->postJson("/api/system/quests/{$opcional->quest_key}/complete")
            ->assertOk()
            ->assertJsonPath('system.quests_completed', []);

        $this->assertNotNull($opcional->fresh()->completed_at);
    }

    public function test_no_se_puede_marcar_a_mano_una_mision_obligatoria()
    {
        $user = User::factory()->create();
        $this->actingAs($user)->getJson('/api/system/today');

        $this->actingAs($user)
            ->postJson('/api/system/quests/water/complete')
            ->assertStatus(422);
    }

    public function test_los_endpoints_del_sistema_exigen_autenticacion()
    {
        $this->getJson('/api/system/today')->assertUnauthorized();
        $this->getJson('/api/system/profile')->assertUnauthorized();
        $this->getJson('/api/system/achievements')->assertUnauthorized();
    }
}
