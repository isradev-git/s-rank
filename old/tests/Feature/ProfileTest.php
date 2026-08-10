<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WeightLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_retorna_usuario_autenticado()
    {
        $user = User::factory()->create(['age' => 25, 'weight' => 70, 'height' => 175]);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/profile');

        $response->assertStatus(200)
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.age', 25)
            ->assertJsonPath('user.weight', 70)
            ->assertJsonPath('user.height', 175);
    }

    public function test_show_sin_autenticacion_retorna_401()
    {
        $this->getJson('/api/profile')->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // UPDATE PROFILE
    // ──────────────────────────────────────────────────────────

    public function test_update_modifica_datos_del_perfil()
    {
        $user = User::factory()->create(['age' => 25, 'main_goal' => 'strength']);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/user/profile', [
            'age'       => 30,
            'main_goal' => 'health',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['id' => $user->id, 'age' => 30, 'main_goal' => 'health']);
    }

    public function test_update_con_nuevo_peso_crea_registro_en_weight_log()
    {
        $user = User::factory()->create(['weight' => 70]);

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/user/profile', [
            'weight' => 72.5,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('weight_logs', ['user_id' => $user->id, 'weight' => 72.5]);
    }

    public function test_update_mismo_peso_no_crea_registro_en_weight_log()
    {
        $user = User::factory()->create(['weight' => 70]);

        $this->actingAs($user, 'sanctum')->putJson('/api/user/profile', [
            'weight' => 70,
        ]);

        $this->assertDatabaseMissing('weight_logs', ['user_id' => $user->id]);
    }

    public function test_update_peso_dos_veces_el_mismo_dia_no_genera_error()
    {
        $user = User::factory()->create(['weight' => 70]);

        // Primera actualización
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/profile', ['weight' => 71])
            ->assertStatus(200);

        // Segunda actualización el mismo día → debe actualizar, no duplicar
        $this->actingAs($user, 'sanctum')
            ->putJson('/api/user/profile', ['weight' => 72])
            ->assertStatus(200);

        // Solo debe haber 1 registro para hoy
        $this->assertEquals(
            1,
            WeightLog::where('user_id', $user->id)
                ->whereDate('date', today())
                ->count()
        );
        // El valor debe ser el último (72)
        $this->assertDatabaseHas('weight_logs', ['user_id' => $user->id, 'weight' => 72]);
    }

    public function test_update_sin_autenticacion_retorna_401()
    {
        $this->putJson('/api/user/profile', ['age' => 30])->assertStatus(401);
    }

    // ──────────────────────────────────────────────────────────
    // WEIGHT HISTORY
    // ──────────────────────────────────────────────────────────

    public function test_weight_history_retorna_registros_del_usuario()
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        WeightLog::create(['user_id' => $user->id, 'weight' => 70, 'date' => '2026-03-01']);
        WeightLog::create(['user_id' => $user->id, 'weight' => 71, 'date' => '2026-03-15']);
        WeightLog::create(['user_id' => $other->id, 'weight' => 80, 'date' => '2026-03-01']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/weight-history');

        $response->assertStatus(200);
        $this->assertCount(2, $response->json());
    }

    public function test_weight_history_retorna_registros_en_orden_ascendente()
    {
        $user = User::factory()->create();

        WeightLog::create(['user_id' => $user->id, 'weight' => 72, 'date' => '2026-03-15']);
        WeightLog::create(['user_id' => $user->id, 'weight' => 70, 'date' => '2026-03-01']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user/weight-history');

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertEquals('2026-03-01', $data[0]['date']);
        $this->assertEquals('2026-03-15', $data[1]['date']);
    }

    // ──────────────────────────────────────────────────────────
    // CHANGE PASSWORD
    // ──────────────────────────────────────────────────────────

    public function test_change_password_con_datos_correctos()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->putJson('/api/user/password', [
            'current_password'          => 'password',
            'new_password'              => 'NuevaPass123',
            'new_password_confirmation' => 'NuevaPass123',
        ]);

        $response->assertStatus(200);
        // Todos los tokens deben haber sido revocados
        $this->assertCount(0, $user->fresh()->tokens);
    }

    public function test_change_password_con_contrasena_actual_incorrecta_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->putJson('/api/user/password', [
            'current_password'          => 'wrongpassword',
            'new_password'              => 'NuevaPass123',
            'new_password_confirmation' => 'NuevaPass123',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);
    }

    public function test_change_password_nueva_contrasena_demasiado_corta_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->putJson('/api/user/password', [
            'current_password'          => 'password',
            'new_password'              => 'corta',
            'new_password_confirmation' => 'corta',
        ])->assertStatus(422)->assertJsonValidationErrors(['new_password']);
    }

    public function test_change_password_sin_confirmacion_retorna_422()
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->putJson('/api/user/password', [
            'current_password' => 'password',
            'new_password'     => 'NuevaPass123',
        ])->assertStatus(422)->assertJsonValidationErrors(['new_password']);
    }

    // ──────────────────────────────────────────────────────────
    // DESTROY ACCOUNT
    // ──────────────────────────────────────────────────────────

    public function test_destroy_elimina_cuenta_del_usuario()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')->deleteJson('/api/user');

        $response->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_destroy_sin_autenticacion_retorna_401()
    {
        $this->deleteJson('/api/user')->assertStatus(401);
    }
}
