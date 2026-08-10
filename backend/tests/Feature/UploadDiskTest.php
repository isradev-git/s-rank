<?php

namespace Tests\Feature;

use App\Models\FoodItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadDiskTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_disco_uploads_no_depende_de_ningun_symlink()
    {
        $this->assertSame(public_path('uploads'), config('filesystems.disks.uploads.root'));
    }

    public function test_la_imagen_de_un_alimento_se_guarda_en_el_disco_uploads()
    {
        Storage::fake('uploads');

        $user = User::factory()->create();
        $food = FoodItem::create([
            'name'              => 'Pollo',
            'calories_per_100g' => 165,
            'protein_per_100g'  => 31,
            'carbs_per_100g'    => 0,
            'fat_per_100g'      => 3.6,
            'user_id'           => $user->id,
        ]);

        $this->actingAs($user, 'sanctum')
            ->post("/api/foods/{$food->id}/image", ['image' => UploadedFile::fake()->image('pollo.jpg')])
            ->assertOk();

        $path = $food->fresh()->image_path;

        $this->assertNotNull($path);
        Storage::disk('uploads')->assertExists($path);
    }
}
