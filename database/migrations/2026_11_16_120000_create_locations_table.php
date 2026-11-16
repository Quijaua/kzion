<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Locais deixam de ser texto livre em `photos.location` e viram cadastro
 * próprio (`locations`), ligado à foto por `photos.location_id`.
 *
 * Os textos que já existiam são convertidos em locais: grafias que só diferem
 * em maiúsculas/minúsculas ou espaços nas pontas viram UM local (a primeira
 * grafia encontrada dá o nome), para não nascer "Campinas" e "campinas"
 * separados.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::table('photos', function (Blueprint $table) {
            $table->foreignId('location_id')
                ->nullable()
                ->after('taken_at')
                ->constrained('locations')
                ->nullOnDelete();
        });

        $now = now();
        $ids = []; // chave normalizada => id do local

        DB::table('photos')
            ->whereNotNull('location')
            ->orderBy('id')
            ->select(['id', 'location'])
            ->chunkById(500, function ($photos) use (&$ids, $now) {
                foreach ($photos as $photo) {
                    $name = trim(preg_replace('/\s+/u', ' ', $photo->location));
                    if ($name === '') {
                        continue;
                    }

                    $key = mb_strtolower($name);

                    if (! isset($ids[$key])) {
                        $ids[$key] = DB::table('locations')->insertGetId([
                            'name' => $name,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }

                    DB::table('photos')->where('id', $photo->id)->update(['location_id' => $ids[$key]]);
                }
            });

        Schema::table('photos', function (Blueprint $table) {
            $table->dropColumn('location');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->string('location')->nullable()->after('taken_at');
        });

        foreach (DB::table('locations')->get(['id', 'name']) as $location) {
            DB::table('photos')->where('location_id', $location->id)->update(['location' => $location->name]);
        }

        Schema::table('photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('location_id');
        });

        Schema::dropIfExists('locations');
    }
};
