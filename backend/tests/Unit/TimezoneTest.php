<?php

namespace Tests\Unit;

use Tests\TestCase;

class TimezoneTest extends TestCase
{
    public function test_el_sistema_calcula_las_fechas_en_madrid()
    {
        $this->assertSame('Europe/Madrid', config('srank.timezone'));
        $this->assertSame('Europe/Madrid', config('app.timezone'));
    }
}
