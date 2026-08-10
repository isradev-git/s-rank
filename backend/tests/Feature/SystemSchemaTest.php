<?php

namespace Tests\Feature;

use App\Models\DailyQuest;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_cada_usuario_tiene_una_fila_de_progreso()
    {
        $user = User::factory()->create();

        $progress = UserProgress::create(['user_id' => $user->id]);

        $this->assertSame(1, $progress->level);
        $this->assertSame(0, $progress->xp_total);
        $this->assertTrue($user->progress->is($progress));
    }

    public function test_no_puede_haber_dos_misiones_iguales_el_mismo_dia()
    {
        $user = User::factory()->create();

        DailyQuest::create([
            'user_id' => $user->id, 'date' => '2026-08-10',
            'quest_key' => 'water', 'target' => 2000, 'xp_reward' => 20,
        ]);

        $this->expectException(QueryException::class);

        DailyQuest::create([
            'user_id' => $user->id, 'date' => '2026-08-10',
            'quest_key' => 'water', 'target' => 2000, 'xp_reward' => 20,
        ]);
    }
}
