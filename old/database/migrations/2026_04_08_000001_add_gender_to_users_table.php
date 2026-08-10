<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 'male' = hombre, 'female' = mujer
            // Afecta al cálculo de BMR con Mifflin-St Jeor (+5 hombre, -161 mujer)
            $table->enum('gender', ['male', 'female'])->default('male')->after('main_goal');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('gender');
        });
    }
};
