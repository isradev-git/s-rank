<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecalculateProgressTest extends TestCase
{
    use RefreshDatabase;

    private function entreno(User $user, string $date, int $minutes = 45, string $mode = 'gym'): Workout
    {
        return Workout::create([
            'user_id' => $user->id, 'mode' => $mode,
            'date' => $date, 'duration_minutes' => $minutes,
        ]);
    }

    public function test_reconstruye_el_xp_de_los_entrenos_ya_registrados()
    {
        $user = User::factory()->create();
        $this->entreno($user, '2026-08-01 18:00:00');   // 56 XP + 2 de racha
        $this->entreno($user, '2026-08-02 18:00:00');   // 56 XP + 4 de racha

        $this->artisan('srank:recalculate')->assertSuccessful();

        $this->assertSame(118, $user->progress->fresh()->xp_total);
        $this->assertSame(2, $user->progress->fresh()->longest_streak);
    }

    public function test_es_idempotente()
    {
        $user = User::factory()->create();
        $this->entreno($user, '2026-08-01 18:00:00');

        $this->artisan('srank:recalculate');
        $primera = $user->progress->fresh()->xp_total;

        $this->artisan('srank:recalculate');

        $this->assertSame($primera, $user->progress->fresh()->xp_total);
        $this->assertSame(2, \App\Models\XpEvent::where('user_id', $user->id)->count());
    }

    public function test_respeta_el_tope_de_dos_entrenos_al_dia()
    {
        $user = User::factory()->create();
        $this->entreno($user, '2026-08-01 08:00:00');
        $this->entreno($user, '2026-08-01 13:00:00');
        $this->entreno($user, '2026-08-01 20:00:00');

        $this->artisan('srank:recalculate');

        $this->assertSame(2, \App\Models\XpEvent::where('user_id', $user->id)
            ->where('source', 'workout')->count());
    }

    public function test_avisa_de_cuantos_entrenos_no_aportaron_kilos()
    {
        $user = User::factory()->create();
        $this->entreno($user, '2026-08-01 18:00:00');

        $this->artisan('srank:recalculate')
            ->expectsOutputToContain('sin detalle de series')
            ->assertSuccessful();
    }

    public function test_la_racha_reconstruida_cuenta_los_dias_sin_entreno()
    {
        $user = User::factory()->create();
        $this->entreno($user, '2026-08-01 18:00:00');
        \App\Models\WaterLog::create(['user_id' => $user->id, 'date' => '2026-08-02', 'amount_ml' => 500]);
        \App\Models\WeightLog::create(['user_id' => $user->id, 'date' => '2026-08-03', 'weight' => 80]);

        $this->artisan('srank:recalculate');

        // Tres días seguidos: entreno, agua y peso. La racha es de días activos.
        $this->assertSame(3, $user->progress->fresh()->longest_streak);
        $this->assertSame(2 + 4 + 6, \App\Models\XpEvent::where('user_id', $user->id)
            ->where('source', 'streak')->sum('amount'));
    }

    public function test_apuntar_los_pasos_mantiene_la_racha_pero_no_da_xp_de_entreno()
    {
        $user = User::factory()->create();
        $this->entreno($user, '2026-08-01 09:00:00', 0, 'pasos');
        $this->entreno($user, '2026-08-02 09:00:00', 0, 'pasos');

        $this->artisan('srank:recalculate');

        $this->assertSame(2, $user->progress->fresh()->longest_streak);
        $this->assertSame(0, \App\Models\XpEvent::where('user_id', $user->id)
            ->where('source', 'workout')->count());
    }

    public function test_desbloquea_los_logros_que_correspondan()
    {
        $user = User::factory()->create();
        $this->entreno($user, '2026-08-01 18:00:00');

        $this->artisan('srank:recalculate');

        $this->assertDatabaseHas('user_achievements', [
            'user_id' => $user->id, 'achievement_key' => 'first_step',
        ]);
    }
}
