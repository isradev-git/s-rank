<?php

namespace Tests\Feature;

use App\Models\User;
use App\System\XpLedger;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class XpLedgerTest extends TestCase
{
    use RefreshDatabase;

    private function hoy(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-08-10');
    }

    public function test_conceder_xp_lo_suma_al_total_y_lo_apunta_en_el_libro()
    {
        $user   = User::factory()->create();
        $ledger = new XpLedger();

        $granted = $ledger->award($user, 'workout', 'w1', 50, $this->hoy());

        $this->assertSame(50, $granted);
        $this->assertSame(50, $ledger->progressFor($user)->xp_total);
        $this->assertDatabaseHas('xp_events', [
            'user_id' => $user->id, 'source' => 'workout', 'amount' => 50,
        ]);
    }

    public function test_el_tope_diario_recorta_la_ultima_concesion()
    {
        $user   = User::factory()->create();
        $ledger = new XpLedger();

        $ledger->award($user, 'workout', 'w1', 280, $this->hoy());
        $granted = $ledger->award($user, 'record', 'w1', 30, $this->hoy());

        $this->assertSame(20, $granted, 'solo caben 20 XP más hasta el tope de 300');
        $this->assertSame(300, $ledger->progressFor($user)->xp_total);
    }

    public function test_pasado_el_tope_no_se_concede_nada_mas()
    {
        $user   = User::factory()->create();
        $ledger = new XpLedger();

        $ledger->award($user, 'workout', 'w1', 300, $this->hoy());
        $granted = $ledger->award($user, 'quest', 'water', 20, $this->hoy());

        $this->assertSame(0, $granted);
        $this->assertSame(300, $ledger->progressFor($user)->xp_total);
    }

    public function test_el_tope_es_por_dia_no_acumulado()
    {
        $user   = User::factory()->create();
        $ledger = new XpLedger();

        $ledger->award($user, 'workout', 'w1', 300, $this->hoy());
        $granted = $ledger->award($user, 'workout', 'w2', 50, $this->hoy()->addDay());

        $this->assertSame(50, $granted);
        $this->assertSame(350, $ledger->progressFor($user)->xp_total);
    }

    public function test_cuenta_los_entrenos_que_han_puntuado_hoy()
    {
        $user   = User::factory()->create();
        $ledger = new XpLedger();

        $ledger->award($user, 'workout', 'w1', 50, $this->hoy());
        $ledger->award($user, 'workout', 'w2', 50, $this->hoy());

        $this->assertSame(2, $ledger->countSource($user, 'workout', $this->hoy()));
        $this->assertSame(0, $ledger->countSource($user, 'workout', $this->hoy()->addDay()));
    }

    public function test_el_nivel_se_recalcula_al_conceder()
    {
        $user   = User::factory()->create();
        $ledger = new XpLedger();

        $ledger->award($user, 'workout', 'w1', 100, $this->hoy());

        $this->assertSame(2, $ledger->progressFor($user)->level);
    }
}
