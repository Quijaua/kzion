<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AcervoTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_login_and_see_dashboard(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get('/dashboard')
            ->assertOk();
    }

    public function test_upload_creates_photo_thumbnail_and_exif_row(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post('/photos', [
            'photos' => [UploadedFile::fake()->image('foto.jpg', 600, 400)],
            'title' => 'Teste',
        ]);

        $response->assertRedirect(route('photos.index'));

        $photo = Photo::first();
        $this->assertNotNull($photo);
        $this->assertTrue(Storage::disk('local')->exists($photo->storage_path));
        // thumbnail path mirrors the original under the thumbnails/ directory
        $thumb = 'thumbnails/'.substr($photo->storage_path, strlen('photos/'));
        $this->assertTrue(Storage::disk('local')->exists($thumb));
    }

    public function test_bulk_upload_endpoint_creates_photo_and_revert_deletes_it(): void
    {
        Storage::fake('local');
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)
            ->post(route('photos.upload'), [
                'filepond' => UploadedFile::fake()->image('lote.jpg', 600, 400),
            ]);

        $response->assertOk();
        $id = (int) $response->getContent();
        $this->assertGreaterThan(0, $id);

        $photo = Photo::find($id);
        $this->assertNotNull($photo);
        $this->assertTrue(Storage::disk('local')->exists($photo->storage_path));

        // Reverting (removing from the pond before finishing) deletes the photo.
        $this->actingAs($admin)
            ->delete(route('photos.upload.revert', $photo))
            ->assertOk();

        $this->assertNull(Photo::find($id));
    }

    public function test_photo_is_visible_when_the_user_can_access_any_tagged_person(): void
    {
        $a = Person::factory()->create();
        $b = Person::factory()->create();

        $comAmbos = Photo::create([
            'filename' => 'dummy.jpg',
            'original_filename' => 'dummy.jpg',
            'storage_path' => 'photos/2026/08/dummy.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 0,
        ]);
        $comAmbos->people()->sync([$a->id, $b->id]);

        $soB = Photo::create([
            'filename' => 'so-b.jpg',
            'original_filename' => 'so-b.jpg',
            'storage_path' => 'photos/2026/08/so-b.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 0,
        ]);
        $soB->people()->sync([$b->id]);

        $user = User::factory()->create(['role' => 'user']);
        $user->people()->attach($a->id); // acesso só a A

        // Regra permissiva: basta acessar UMA das pessoas marcadas.
        $this->actingAs($user)
            ->get(route('photos.show', $comAmbos))
            ->assertOk();

        // Mas uma foto onde ele não acessa ninguém continua fora.
        $this->actingAs($user)
            ->get(route('photos.show', $soB))
            ->assertNotFound();

        // Admin vê tudo.
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)
            ->get(route('photos.show', $comAmbos))
            ->assertOk();
    }

    public function test_search_requires_all_selected_people_present(): void
    {
        $a = Person::factory()->create();
        $b = Person::factory()->create();

        $onlyA = Photo::create([
            'filename' => 'onlyA.jpg',
            'original_filename' => 'onlyA.jpg',
            'storage_path' => 'photos/2026/08/onlyA.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 0,
        ]);
        $onlyA->people()->attach($a->id);

        $both = Photo::create([
            'filename' => 'both.jpg',
            'original_filename' => 'both.jpg',
            'storage_path' => 'photos/2026/08/both.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 0,
        ]);
        $both->people()->attach([$a->id, $b->id]);

        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)
            ->get(route('photos.index', ['people' => [$a->id, $b->id]]))
            ->assertOk()
            ->assertSee('both.jpg')
            ->assertDontSee('onlyA.jpg');
    }

    public function test_admin_pages_render_without_errors(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach ([
            route('dashboard'),
            route('photos.index'),
            route('photos.create'),
            route('people.index'),
            route('people.create'),
            route('albums.index'),
            route('albums.create'),
            route('users.index'),
            route('users.create'),
        ] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_person_show_lists_accessible_photos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $person = Person::factory()->create();

        $photo = Photo::create([
            'filename' => 'p.jpg',
            'original_filename' => 'p.jpg',
            'storage_path' => 'photos/2026/08/p.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 0,
        ]);
        $photo->people()->attach($person->id);

        $this->actingAs($admin)
            ->get(route('people.show', $person))
            ->assertOk()
            ->assertSee('thumbnail');
    }
}
