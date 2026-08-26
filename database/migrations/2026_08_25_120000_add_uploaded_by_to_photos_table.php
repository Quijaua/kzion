<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            // Quem enviou a foto. Nulo nas fotos anteriores a esta coluna (não
            // há como descobrir o dono retroativamente) e nas fotos cujo dono
            // foi excluído — nesses casos só as pessoas marcadas e o papel de
            // administrador decidem quem vê.
            $table->foreignId('uploaded_by')
                ->nullable()
                ->after('location')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('photos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('uploaded_by');
        });
    }
};
