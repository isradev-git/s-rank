<?php

namespace Tests\Unit;

use App\System\Progression;
use PHPUnit\Framework\TestCase;

class ProgressionTest extends TestCase
{
    public function test_el_coste_de_cada_nivel_es_lineal()
    {
        $this->assertSame(100, Progression::xpForNextLevel(1));
        $this->assertSame(140, Progression::xpForNextLevel(2));
        $this->assertSame(660, Progression::xpForNextLevel(15));
    }

    public function test_el_xp_acumulado_coincide_con_las_fronteras_de_rango()
    {
        $this->assertSame(0,     Progression::xpToReachLevel(1));
        $this->assertSame(100,   Progression::xpToReachLevel(2));
        $this->assertSame(5040,  Progression::xpToReachLevel(15));   // rango D
        $this->assertSame(13440, Progression::xpToReachLevel(25));   // rango C
        $this->assertSame(25840, Progression::xpToReachLevel(35));   // rango B
        $this->assertSame(42240, Progression::xpToReachLevel(45));   // rango A
        $this->assertSame(62640, Progression::xpToReachLevel(55));   // rango S
    }

    public function test_el_nivel_se_deriva_del_xp_en_las_fronteras()
    {
        $this->assertSame(1,  Progression::levelForXp(0));
        $this->assertSame(1,  Progression::levelForXp(99));
        $this->assertSame(2,  Progression::levelForXp(100));
        $this->assertSame(14, Progression::levelForXp(5039));
        $this->assertSame(15, Progression::levelForXp(5040));
        $this->assertSame(55, Progression::levelForXp(62640));
    }

    public function test_el_nivel_nunca_es_menor_que_uno()
    {
        $this->assertSame(1, Progression::levelForXp(-50));
    }

    public function test_el_rango_cambia_en_los_niveles_correctos()
    {
        $this->assertSame('E', Progression::rankForLevel(1));
        $this->assertSame('E', Progression::rankForLevel(14));
        $this->assertSame('D', Progression::rankForLevel(15));
        $this->assertSame('D', Progression::rankForLevel(24));
        $this->assertSame('C', Progression::rankForLevel(25));
        $this->assertSame('B', Progression::rankForLevel(35));
        $this->assertSame('A', Progression::rankForLevel(45));
        $this->assertSame('S', Progression::rankForLevel(55));
        $this->assertSame('S', Progression::rankForLevel(300));
    }

    public function test_ida_y_vuelta_entre_nivel_y_xp_para_los_cien_primeros()
    {
        for ($level = 1; $level <= 100; $level++) {
            $exact = Progression::xpToReachLevel($level);

            $this->assertSame($level, Progression::levelForXp($exact), "nivel {$level} exacto");

            if ($level > 1) {
                $this->assertSame($level - 1, Progression::levelForXp($exact - 1), "nivel {$level} - 1 XP");
            }
        }
    }

    public function test_la_barra_de_nivel_reparte_el_xp_del_nivel_en_curso()
    {
        $bar = Progression::levelBar(150);

        $this->assertSame(50, $bar['into_level']);   // 150 - 100 del nivel 2
        $this->assertSame(140, $bar['for_next']);    // el nivel 2 cuesta 140
    }
}
