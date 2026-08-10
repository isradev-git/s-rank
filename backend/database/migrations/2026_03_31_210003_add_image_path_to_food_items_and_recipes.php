<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade image_path a food_items y recipes para fotos subidas localmente.
 * Se distingue de image_url (URL externa) — image_path es una ruta relativa
 * dentro de storage/app/public/nutrition/.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('food_items', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('category');
        });

        Schema::table('recipes', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('image_url');
        });
    }

    public function down(): void
    {
        Schema::table('food_items', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
        Schema::table('recipes', function (Blueprint $table) {
            $table->dropColumn('image_path');
        });
    }
};
