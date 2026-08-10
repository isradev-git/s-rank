<?php

namespace Tests\Feature;

use App\Models\SupplementLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplementLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_retorna_checklist_del_dia(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        SupplementLog::create([
            'user_id' => $user->id,
            'date' => '2026-04-01',
            'supplement_key' => 'omega3',
            'taken' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/supplements?date=2026-04-01');

        $response->assertStatus(200)
            ->assertJsonStructure(['date', 'items', 'taken_count', 'total_count'])
            ->assertJsonPath('taken_count', 1)
            ->assertJsonPath('total_count', 4);
    }

    public function test_upsert_guarda_estado_del_suplemento(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/supplements', [
            'date' => '2026-04-01',
            'supplement_key' => 'magnesio',
            'taken' => true,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('supplement_logs', [
            'user_id' => $user->id,
            'supplement_key' => 'magnesio',
            'taken' => true,
        ]);

        $log = SupplementLog::where('user_id', $user->id)
            ->where('supplement_key', 'magnesio')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('2026-04-01', $log->date->toDateString());
    }

    public function test_reset_limpia_checklist_del_dia(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        SupplementLog::create([
            'user_id' => $user->id,
            'date' => '2026-04-01',
            'supplement_key' => 'multivitaminas',
            'taken' => true,
        ]);

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/supplements', [
            'date' => '2026-04-01',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('supplement_logs', [
            'user_id' => $user->id,
            'date' => '2026-04-01',
            'supplement_key' => 'multivitaminas',
        ]);
    }
}
