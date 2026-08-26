<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Deixa o usuário comum LISTAR e MARCAR qualquer pessoa do acervo.
            // Não amplia as fotos que ele vê: quem decide isso continua sendo
            // o pivô `user_people` (ver User::photoAccessPersonIds()).
            $table->boolean('all_people_access')->default(false)->after('active');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('all_people_access');
        });
    }
};
