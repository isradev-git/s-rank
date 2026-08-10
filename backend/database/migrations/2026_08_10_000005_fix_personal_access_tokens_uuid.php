<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sanctum crea `tokenable_id` con morphs(), que es un bigint. Los usuarios de esta
 * aplicación tienen UUID, así que MySQL trunca el valor al emitir el token y el login
 * revienta con «Data truncated for column 'tokenable_id'». SQLite —el motor de los
 * tests— no tipa las columnas y lo tragaba sin rechistar, así que esto no salió hasta
 * probar el registro contra el servidor de verdad.
 *
 * Los tokens que hubiera apuntan a ids que no existen: se tiran. Cerrar la sesión de
 * todo el mundo una vez es más barato que arrastrar filas rotas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->char('tokenable_id', 36)->change();
        });

        DB::table('personal_access_tokens')->delete();
    }

    public function down(): void
    {
        DB::table('personal_access_tokens')->delete();

        Schema::table('personal_access_tokens', function (Blueprint $table) {
            $table->unsignedBigInteger('tokenable_id')->change();
        });
    }
};
