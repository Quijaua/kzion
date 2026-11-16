<?php

namespace Database\Seeders;

use App\Models\Album;
use App\Models\Location;
use App\Models\Person;
use App\Models\User;
use App\Services\PhotoService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrador',
                'password' => 'password',
                'role' => 'admin',
                'active' => true,
            ]
        );

        $people = collect([
            'Maria da Silva',
            'João da Silva',
            'Ana Pereira',
            'Pedro Gomes',
        ])->map(fn ($name) => Person::firstOrCreate(['name' => $name], ['name' => $name]));

        $campinas = Location::firstOrCreate(['name' => 'Campinas']);

        $manager = new ImageManager(new Driver());
        $colors = ['#c0392b', '#27ae60', '#2980b9', '#8e44ad', '#d35400'];

        $photos = [];
        foreach ($colors as $i => $color) {
            $path = sys_get_temp_dir().'/seed_'.$i.'.jpg';
            $image = $manager->createImage(600, 400);
            $image->fill($color);
            $image->save($path);

            $upload = new UploadedFile($path, 'foto_'.$i.'.jpg', 'image/jpeg', null, true);

            $photo = (new PhotoService())->store($upload, [
                'title' => 'Foto de exemplo '.$i,
                'description' => 'Imagem de seed para o acervo familiar.',
                // Mistura as três precisões, como num acervo de verdade.
                ...array_combine(['taken_at', 'taken_at_precision'], \App\Models\Photo::dateFromParts(
                    now()->year - 40 + $i,
                    $i % 3 === 0 ? null : ($i % 12) + 1,
                    $i % 3 === 2 ? 15 : null,
                )),
                'location_id' => $campinas->id,
            ]);

            $photo->people()->sync([
                $people[$i % $people->count()]->id,
                $people[($i + 1) % $people->count()]->id,
            ]);

            $photos[] = $photo;
        }

        $album = Album::firstOrCreate(
            ['name' => 'Natal 1987'],
            ['name' => 'Natal 1987', 'description' => 'Fotos das festas de Natal.']
        );
        $album->photos()->sync(collect($photos)->pluck('id')->all());
    }
}
