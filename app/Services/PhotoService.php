<?php

namespace App\Services;

use App\Models\Photo;
use App\Models\PhotoExif;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class PhotoService
{
    protected ImageManager $manager;

    public function __construct()
    {
        $this->manager = new ImageManager(new Driver());
    }

    /** SHA-256 do arquivo: identifica a mesma imagem enviada de novo. */
    public static function checksum(UploadedFile|string $file): string
    {
        return hash_file('sha256', $file instanceof UploadedFile ? $file->getRealPath() : $file);
    }

    /**
     * Grava o original e cria o registro da foto.
     *
     * A miniatura e o EXIF ficam para DEPOIS da resposta (`defer()`): gerar a
     * miniatura de uma foto grande com GD leva segundos e muita memória, e era
     * nessa espera que o envio em lote ficava parado em "Enviando 100%" — ou a
     * conexão caía com a foto já salva. A rota de miniatura serve o original
     * enquanto a miniatura não existe, e a data do EXIF aparece logo em seguida.
     *
     * No console (seeder, testes) o trabalho roda na hora, para o resultado ser
     * determinístico.
     *
     * `$readExif = false` pula a leitura do EXIF (escolha do toggle nos
     * formulários de envio).
     */
    public function store(UploadedFile $file, array $attributes = [], bool $readExif = true): Photo
    {
        $now = now();
        $folder = $now->format('Y/m');
        $hash = md5($file->getClientOriginalName().$now->timestamp.random_bytes(8));
        $ext = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $checksum = static::checksum($file);

        $originalRel = "photos/{$folder}/{$hash}.{$ext}";
        $file->storeAs("photos/{$folder}", "{$hash}.{$ext}");

        [$width, $height] = $this->readDimensions(Storage::path($originalRel));

        $photo = Photo::create(array_merge([
            // O dono é carimbado aqui, e não nos controllers, para que nenhum
            // caminho de upload (foto única, lote, seeder) possa esquecer.
            // Fica nulo fora de uma requisição autenticada.
            'uploaded_by' => auth()->id(),
            'filename' => "{$hash}.{$ext}",
            'original_filename' => $file->getClientOriginalName(),
            'storage_path' => $originalRel,
            'mime_type' => $file->getClientMimeType(),
            'file_size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'checksum' => $checksum,
        ], $attributes));

        $finish = function () use ($photo, $originalRel, $readExif) {
            // A foto pode ter sido removida da fila (revert) nesse meio tempo.
            if (! Photo::whereKey($photo->id)->exists() || ! Storage::exists($originalRel)) {
                return;
            }

            try {
                $thumbRel = 'thumbnails/'.substr($originalRel, strlen('photos/'));
                $this->makeThumbnail(Storage::path($originalRel), Storage::path($thumbRel));
            } catch (\Throwable $e) {
                report($e); // sem miniatura a rota serve o original
            }

            // Desligado no formulário quando as imagens não têm EXIF (scans,
            // fotos de WhatsApp): pula a leitura e a gravação em `photo_exif`.
            if ($readExif) {
                try {
                    $this->extractExif($photo->fresh(), Storage::path($originalRel));
                } catch (\Throwable $e) {
                    report($e);
                }
            }
        };

        app()->runningInConsole() ? $finish() : \Illuminate\Support\defer($finish);

        return $photo;
    }

    protected function makeThumbnail(string $source, string $target): void
    {
        $dir = dirname($target);
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $image = $this->manager->decodePath($source);
        $image->scaleDown(width: 400);
        $image->save($target);
    }

    protected function readDimensions(string $path): array
    {
        $info = @getimagesize($path);
        if ($info === false) {
            return [null, null];
        }

        return [$info[0], $info[1]];
    }

    protected function extractExif(Photo $photo, string $path): void
    {
        $raw = @exif_read_data($path, null, true);
        if ($raw === false) {
            return;
        }

        $flattened = $this->flatten($raw);

        $map = [
            'Make' => 'make',
            'Model' => 'model',
            'LensModel' => 'lens',
            'ISOSpeedRatings' => 'iso',
            'FNumber' => 'aperture',
            'ExposureTime' => 'shutter_speed',
            'FocalLength' => 'focal_length',
            'Software' => 'software',
        ];

        $data = [];
        foreach ($map as $exifKey => $column) {
            if (isset($flattened[$exifKey])) {
                $data[$column] = $this->normalize($flattened[$exifKey]);
            }
        }

        // Só preenche a data se ninguém informou: o formulário de foto única
        // promete "se vazio, tentamos ler do EXIF".
        if ($photo->taken_at === null && isset($flattened['DateTimeOriginal'])) {
            $photo->taken_at = $this->parseDate($flattened['DateTimeOriginal']);
            $photo->taken_at_precision = $photo->taken_at ? Photo::PRECISION_DAY : null;
        }

        $lat = $this->gps($flattened, 'GPSLatitude', $flattened['GPSLatitudeRef'] ?? null);
        $lon = $this->gps($flattened, 'GPSLongitude', $flattened['GPSLongitudeRef'] ?? null);
        if ($lat !== null) {
            $data['gps_latitude'] = $lat;
        }
        if ($lon !== null) {
            $data['gps_longitude'] = $lon;
        }

        // Campos binários (MakerNote, miniatura embutida) não são UTF-8 válido e
        // derrubariam o json_encode do cast — e com ele o EXIF inteiro.
        array_walk_recursive($flattened, function (&$v) {
            if (is_string($v) && ! mb_check_encoding($v, 'UTF-8')) {
                $v = '[binário, '.strlen($v).' bytes]';
            }
        });

        $data['exif_data_json'] = $flattened;

        $photo->save();

        $photo->exif()->create($data);
    }

    protected function flatten(array $raw): array
    {
        $out = [];
        foreach ($raw as $section) {
            if (! is_array($section)) {
                continue;
            }
            foreach ($section as $key => $value) {
                $out[$key] = $value;
            }
        }

        return $out;
    }

    protected function normalize($value): string
    {
        if (is_array($value)) {
            return implode(',', array_map(fn ($v) => is_array($v) ? implode('/', $v) : $v, $value));
        }

        return (string) $value;
    }

    protected function parseDate(string $value): ?\DateTime
    {
        $formatted = preg_replace('/(\d{4}):(\d{2}):(\d{2})/', '$1-$2-$3', $value);

        return \DateTime::createFromFormat('Y-m-d H:i:s', $formatted) ?: null;
    }

    protected function gps(array $data, string $key, $ref): ?float
    {
        if (! isset($data[$key]) || ! is_array($data[$key])) {
            return null;
        }

        [$deg, $min, $sec] = array_map(function ($v) {
            $parts = explode('/', $v);
            if (count($parts) === 2 && $parts[1] != 0) {
                return $parts[0] / $parts[1];
            }

            return (float) $v;
        }, $data[$key]);

        $decimal = $deg + ($min / 60) + ($sec / 3600);

        if ($ref === 'S' || $ref === 'W') {
            $decimal *= -1;
        }

        return round($decimal, 8);
    }
}
