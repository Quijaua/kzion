<?php

use App\Models\Photo;
use App\Services\PhotoService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Calcula o checksum das fotos enviadas antes da detecção de duplicadas existir.
// Pode rodar quantas vezes quiser: só mexe nas fotos que ainda não têm.
Artisan::command('photos:checksums', function () {
    $feitas = 0;
    $faltando = 0;

    Photo::whereNull('checksum')->chunkById(200, function ($photos) use (&$feitas, &$faltando) {
        foreach ($photos as $photo) {
            if (! Storage::exists($photo->storage_path)) {
                $faltando++;
                continue;
            }

            $photo->forceFill(['checksum' => PhotoService::checksum(Storage::path($photo->storage_path))])->saveQuietly();
            $feitas++;
        }
    });

    $this->info("{$feitas} foto(s) com checksum calculado.");
    if ($faltando) {
        $this->warn("{$faltando} foto(s) sem o arquivo original no disco foram puladas.");
    }
})->purpose('Calcula o checksum (SHA-256) das fotos que ainda não têm');
