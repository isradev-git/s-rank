<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workout;
use App\System\AchievementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AchievementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_catalogo_tiene_cuarenta_logros_en_cuatro_rarezas()
    {
        $catalog = AchievementService::CATALOG;

        $this->assertCount(40, $catalog);

        $porRareza = collect($catalog)->countBy('rarity');

        $this->assertSame(10, $porRareza['common']);
        $this->assertSame(12, $porRareza['rare']);
        $this->assertSame(10, $porRareza['epic']);
        $this->assertSame(8,  $porRareza['legendary']);
    }

    public function test_el_primer_entreno_desbloquea_primer_paso()
    {
        $user = User::factory()->create();
        Workout::create([
            'user_id' => $user->id, 'mode' => 'gym',
            'date' => '2026-08-10 18:00:00', 'duration_minutes' => 45,
        ]);

        $nuevos = app(AchievementService::class)->evaluate($user);

        $this->assertContains('first_step', array_column($nuevos, 'key'));
        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id, 'achievement_key' => 'first_step',
        ]);
    }

    public function test_un_logro_ya_desbloqueado_no_se_devuelve_dos_veces()
    {
        $user = User::factory()->create();
        Workout::create([
            'user_id' => $user->id, 'mode' => 'gym',
            'date' => '2026-08-10 18:00:00', 'duration_minutes' => 45,
        ]);

        $service = app(AchievementService::class);
        $service->evaluate($user);
        $segunda = $service->evaluate($user);

        $this->assertSame([], $segunda);
    }

    public function test_los_pasos_no_cuentan_como_entrenamiento()
    {
        $user = User::factory()->create();
        Workout::create([
            'user_id' => $user->id, 'mode' => 'pasos',
            'date' => '2026-08-10 18:00:00', 'duration_minutes' => 60,
        ]);

        $nuevos = app(AchievementService::class)->evaluate($user);

        $this->assertNotContains('first_step', array_column($nuevos, 'key'));
    }

    public function test_el_umbral_de_diez_entrenos_es_exacto()
    {
        $user = User::factory()->create();
        $service = app(AchievementService::class);

        for ($i = 1; $i <= 9; $i++) {
            Workout::create([
                'user_id' => $user->id, 'mode' => 'gym',
                'date' => sprintf('2026-%02d-01 18:00:00', $i), 'duration_minutes' => 45,
            ]);
        }

        $service->evaluate($user);
        $this->assertDatabaseMissing('user_achievements', [
            'user_id' => $user->id, 'achievement_key' => 'workouts_10',
        ]);

        Workout::create([
            'user_id' => $user->id, 'mode' => 'gym',
            'date' => '2026-10-01 18:00:00', 'duration_minutes' => 45,
        ]);

        $this->assertContains('workouts_10', array_column($service->evaluate($user), 'key'));
    }

    public function test_la_racha_cuenta_dias_seguidos_con_cualquier_actividad()
    {
        $user = User::factory()->create();

        // Tres días seguidos mezclando entreno, agua y peso: la racha del Sistema
        // es de días activos, no solo de días entrenados.
        Workout::create([
            'user_id' => $user->id, 'mode' => 'gym',
            'date' => '2026-08-10 18:00:00', 'duration_minutes' => 45,
        ]);
        \App\Models\WaterLog::create(['user_id' => $user->id, 'date' => '2026-08-11', 'amount_ml' => 500]);
        \App\Models\WeightLog::create(['user_id' => $user->id, 'date' => '2026-08-12', 'weight' => 80]);

        $nuevos = array_column(app(AchievementService::class)->evaluate($user), 'key');

        $this->assertContains('streak_3', $nuevos);
    }

    public function test_la_lista_devuelve_los_cuarenta_con_su_estado()
    {
        $user = User::factory()->create();

        $lista = app(AchievementService::class)->listFor($user);

        $this->assertCount(40, $lista);
        $this->assertFalse($lista[0]['unlocked']);
        $this->assertArrayHasKey('rarity', $lista[0]);
        $this->assertArrayHasKey('description', $lista[0]);
    }
}
