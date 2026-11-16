<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('js_challenge_devices', function (Blueprint $table) {
            // "Seletor" público que vai no cookie; o segredo só existe como hash
            $table->string('id', 32)->primary();

            // string para servir tanto a id inteiro quanto a UUID/ULID
            $table->string('user_id', 64)->index();

            $table->char('secret_hash', 64);
            $table->string('network', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('last_ip', 45)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('expires_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('js_challenge_devices');
    }
};
