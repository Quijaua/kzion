<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            // Id gerado pelo navegador para cada arquivo do envio em lote. É o
            // que deixa o navegador perguntar "esta foto chegou?" quando a
            // resposta do upload se perde, e o que torna o reenvio idempotente.
            $table->string('upload_token', 36)->nullable()->unique()->after('uploaded_by');

            // SHA-256 do arquivo original, para avisar quando a mesma imagem é
            // enviada de novo. Fotos antigas: `php artisan photos:checksums`.
            $table->string('checksum', 64)->nullable()->index()->after('upload_token');
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropUnique(['upload_token']);
            $table->dropIndex(['checksum']);
            $table->dropColumn(['upload_token', 'checksum']);
        });
    }
};
