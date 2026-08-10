<?php

namespace Tests\Feature;

use App\Models\DailyQuest;
use App\Models\NutritionGoal;
use App\Models\User;
use App\Models\WaterLog;
use App\Models\Workout;
use App\Models\XpEvent;
use App\System\QuestService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestServiceTest extends TestCase
{
    use RefreshDatabase;

    private CarbonImmutable $hoy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->hoy = CarbonImmutable::parse('2026-08-10');   // lunes
    }

    public function test_genera_como_mucho_cuatro_obligatorias_y_una_opcional()
    {
        $user = User::factory()->create(['weekly_goal' => 3, 'water_goal_ml' => 2000]);
        NutritionGoal::create(['user_id' => $user->id, 'target_protein' => 130]);

        app(QuestService::class)->generate($user, $this->hoy);

        $quests = DailyQuest::where('user_id', $user->id)->get();

        $this->assertCount(4, $quests->where('is_optional', false));
        $this->assertCount(1, $quests->where('is_optional', true));
    }

    public function test_generar_dos_veces_el_mismo_dia_no_duplica_nada()
    {
        $user = User::factory()->create();
        $service = app(QuestService::class);

        $service->generate($user, $this->hoy);
        $primera = DailyQuest::where('user_id', $user->id)->pluck('quest_key')->sort()->values();

        $service->generate($user, $this->hoy);
        $segunda = DailyQuest::where('user_id', $user->id)->pluck('quest_key')->sort()->values();

        $this->assertEquals($primera, $segunda);
    }

    public function test_la_rotativa_es_estable_para_el_mismo_usuario_y_dia()
    {
        $user = User::factory()->create();
        $service = app(QuestService::class);

        $service->generate($user, $this->hoy);
        $elegida = DailyQuest::where('user_id', $user->id)
            ->whereIn('quest_key', ['weight', 'meals_3', 'supplements'])
            ->value('quest_key');

        DailyQuest::where('user_id', $user->id)->delete();
        $service->generate($user, $this->hoy);

        $this->assertSame($elegida, DailyQuest::where('user_id', $user->id)
            ->whereIn('quest_key', ['weight', 'meals_3', 'supplements'])
            ->value('quest_key'));
    }

    public function test_sin_objetivo_nutricional_no_hay_mision_de_proteina()
    {
        $user = User::factory()->create();

        app(QuestService::class)->generate($user, $this->hoy);

        $this->assertDatabaseMissing('daily_quests', [
            'user_id' => $user->id, 'quest_key' => 'protein',
        ]);
    }

    public function test_no_aparece_entrenar_si_la_meta_semanal_ya_esta_cumplida()
    {
        $user = User::factory()->create(['weekly_goal' => 2]);

        foreach ([0, 1] as $offset) {
            Workout::create([
                'user_id' => $user->id, 'mode' => 'gym',
                'date' => $this->hoy->addDays($offset)->toDateTimeString(),
                'duration_minutes' => 45,
            ]);
        }

        app(QuestService::class)->generate($user, $this->hoy);

        $this->assertDatabaseMissing('daily_quests', [
            'user_id' => $user->id, 'quest_key' => 'train',
        ]);
    }

    public function test_al_llegar_al_objetivo_de_agua_la_mision_se_completa_y_da_xp()
    {
        $user = User::factory()->create(['water_goal_ml' => 2000]);
        $service = app(QuestService::class);
        $service->generate($user, $this->hoy);

        WaterLog::create(['user_id' => $user->id, 'date' => $this->hoy->toDateString(), 'amount_ml' => 2000]);

        $resultado = $service->sync($user, $this->hoy);

        $this->assertContains('water', $resultado['completed']);
        $this->assertSame(20, $resultado['xp']);
        $this->assertNotNull(DailyQuest::where('user_id', $user->id)->where('quest_key', 'water')->value('completed_at'));
    }

    public function test_una_mision_completada_no_vuelve_a_pagar()
    {
        $user = User::factory()->create(['water_goal_ml' => 2000]);
        $service = app(QuestService::class);
        $service->generate($user, $this->hoy);

        WaterLog::create(['user_id' => $user->id, 'date' => $this->hoy->toDateString(), 'amount_ml' => 2000]);

        $service->sync($user, $this->hoy);
        $segunda = $service->sync($user, $this->hoy);

        $this->assertSame([], $segunda['completed']);
        $this->assertSame(0, $segunda['xp']);
    }

    public function test_completar_todas_las_obligatorias_da_el_bonus_una_sola_vez()
    {
        // Sin objetivo nutricional no hay misión de proteína, así que las obligatorias
        // son tres: entrenar, agua y la rotativa. Se cumplen las tres de verdad —la
        // rotativa se decide sola, así que se satisfacen las tres posibilidades.
        $user = User::factory()->create(['weekly_goal' => 1, 'water_goal_ml' => 500]);
        $service = app(QuestService::class);

        Workout::create([
            'user_id' => $user->id, 'mode' => 'gym',
            'date' => $this->hoy->toDateTimeString(), 'duration_minutes' => 45,
        ]);
        WaterLog::create(['user_id' => $user->id, 'date' => $this->hoy->toDateString(), 'amount_ml' => 500]);
        \App\Models\WeightLog::create([
            'user_id' => $user->id, 'date' => $this->hoy->toDateString(), 'weight' => 80,
        ]);

        foreach (['breakfast', 'lunch', 'dinner'] as $tipo) {
            \App\Models\MealLog::create([
                'user_id' => $user->id, 'date' => $this->hoy->toDateString(),
                'meal_type' => $tipo, 'custom_food_name' => 'Algo', 'quantity_grams' => 100,
            ]);
        }

        foreach (['multivitaminas', 'omega3', 'vitamina_d', 'magnesio'] as $suplemento) {
            \App\Models\SupplementLog::create([
                'user_id' => $user->id, 'date' => $this->hoy->toDateString(),
                'supplement_key' => $suplemento, 'taken' => true,
            ]);
        }

        $service->generate($user, $this->hoy);

        $resultado = $service->sync($user, $this->hoy);

        $this->assertDatabaseHas('xp_events', [
            'user_id' => $user->id, 'source' => 'quest_bonus', 'amount' => 40,
        ]);
        $this->assertGreaterThan(0, $resultado['xp']);

        $service->sync($user, $this->hoy);

        $this->assertSame(1, XpEvent::where('user_id', $user->id)->where('source', 'quest_bonus')->count());
    }

    public function test_la_opcional_se_marca_a_mano()
    {
        $user = User::factory()->create();
        $service = app(QuestService::class);
        $service->generate($user, $this->hoy);

        $opcional = DailyQuest::where('user_id', $user->id)->where('is_optional', true)->first();

        $this->assertTrue($service->completeOptional($user, $opcional->quest_key, $this->hoy));
        $this->assertFalse($service->completeOptional($user, $opcional->quest_key, $this->hoy), 'no paga dos veces');
        $this->assertFalse($service->completeOptional($user, 'water', $this->hoy), 'las obligatorias no se marcan a mano');
    }

    public function test_la_mision_del_agua_se_lee_en_castellano_con_los_litros_del_usuario()
    {
        $user = User::factory()->create(['water_goal_ml' => 2500]);
        $service = app(QuestService::class);
        $service->generate($user, $this->hoy);

        $agua = collect($service->forDate($user, $this->hoy))->firstWhere('key', 'water');

        $this->assertSame('Beber 2,5 litros de agua', $agua['label']);
    }
}
