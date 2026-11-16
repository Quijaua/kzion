<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Data da foto pronta para acervo de família.
 *
 * 1. `taken_at` deixa de ser TIMESTAMP: no MySQL/MariaDB esse tipo só aceita
 *    datas a partir de 1970 (uma foto de 1965 dava "Incorrect datetime value")
 *    e ainda converte fuso. DATETIME vai de 1000 a 9999, sem fuso, e mantém a
 *    hora que vem do EXIF.
 *
 * 2. `taken_at_precision` diz o quanto da data se sabe:
 *      'year'  → só o ano    (guardado como AAAA-01-01)
 *      'month' → ano e mês   (guardado como AAAA-MM-01)
 *      'day'   → data completa
 *    Sem isso, "só sei que é de 1987" virava 01/01/1987 e a foto aparecia em
 *    janeiro na linha do tempo. As datas que já existem viram 'day'.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dateTime('taken_at')->nullable()->change();
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->string('taken_at_precision', 5)->nullable()->after('taken_at');
        });

        DB::table('photos')->whereNotNull('taken_at')->update(['taken_at_precision' => 'day']);
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('taken_at_precision');
        });

        // TIMESTAMP não aceita datas antes de 1970: no rollback elas se perdem.
        if (in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::table('photos')->where('taken_at', '<', '1970-01-02')->update(['taken_at' => null]);
        }

        Schema::table('photos', function (Blueprint $table) {
            $table->timestamp('taken_at')->nullable()->change();
        });
    }
};
