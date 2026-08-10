<?php

namespace Tests\Unit;

use App\System\Stats;
use PHPUnit\Framework\TestCase;

class StatsTest extends TestCase
{
    public function test_la_estadistica_es_la_raiz_del_acumulador_partido_por_k()
    {
        $this->assertSame(0,  Stats::value(0, 1500));
        $this->assertSame(1,  Stats::value(1500, 1500));
        $this->assertSame(10, Stats::value(150000, 1500));   // 100 entrenos de gimnasio
        $this->assertSame(13, Stats::value(4500, 25));       // 100 sesiones de 45 minutos
        $this->assertSame(23, Stats::value(800, 1.5));       // 200 días + 600 misiones
        $this->assertSame(10, Stats::value(300, 2.5));       // 300 objetivos de hábito
    }

    public function test_un_acumulador_negativo_o_una_k_invalida_dan_cero()
    {
        $this->assertSame(0, Stats::value(-10, 1500));
        $this->assertSame(0, Stats::value(1500, 0));
    }
}
