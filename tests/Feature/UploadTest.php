<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Envio em lote sem "limbo": token por arquivo, consulta de status, reenvio
 * idempotente, aviso de imagem repetida e o revert restrito ao dono.
 */
class UploadTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    private function image(int $w = 600, int $h = 400): UploadedFile
    {
        return UploadedFile::fake()->image('foto.jpg', $w, $h);
    }

    public function test_upload_stores_token_and_checksum(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = (string) Str::uuid();

        $id = (int) $this->actingAs($user)
            ->post(route('photos.upload'), ['filepond' => $this->image(), 'upload_token' => $token])
            ->assertOk()
            ->getContent();

        $photo = Photo::findOrFail($id);
        $this->assertSame($token, $photo->upload_token);
        $this->assertSame(64, strlen($photo->checksum));
        $this->assertSame(hash_file('sha256', Storage::path($photo->storage_path)), $photo->checksum);
    }

    public function test_status_answers_only_for_the_uploader(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);
        $token = (string) Str::uuid();

        $id = $this->actingAs($user)
            ->post(route('photos.upload'), ['filepond' => $this->image(), 'upload_token' => $token])
            ->getContent();

        $this->actingAs($user)
            ->get(route('photos.upload.status', $token))
            ->assertOk()
            ->assertSee($id);

        $this->actingAs($other)->get(route('photos.upload.status', $token))->assertNotFound();
        $this->actingAs($user)->get(route('photos.upload.status', (string) Str::uuid()))->assertNotFound();
    }

    public function test_resending_with_the_same_token_returns_the_same_photo(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = (string) Str::uuid();

        $first = $this->actingAs($user)
            ->post(route('photos.upload'), ['filepond' => $this->image(), 'upload_token' => $token])
            ->getContent();
        $second = $this->actingAs($user)
            ->post(route('photos.upload'), ['filepond' => $this->image(), 'upload_token' => $token])
            ->assertOk()
            ->getContent();

        $this->assertSame($first, $second);
        $this->assertSame(1, Photo::count());
    }

    public function test_same_image_again_is_reported_as_duplicate(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $id = $this->actingAs($user)
            ->post(route('photos.upload'), ['filepond' => $this->image(), 'upload_token' => (string) Str::uuid()])
            ->assertOk()
            ->getContent();

        $this->actingAs($user)
            ->post(route('photos.upload'), ['filepond' => $this->image(), 'upload_token' => (string) Str::uuid()])
            ->assertStatus(409)
            ->assertJson(['id' => (int) $id]);

        $this->assertSame(1, Photo::count());
    }

    public function test_duplicate_the_user_cannot_see_is_not_revealed(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);

        // Foto sem ninguém marcado: só o dono (e admins) a veem.
        $this->actingAs($owner)
            ->post(route('photos.upload'), ['filepond' => $this->image()])
            ->assertOk();

        // O outro usuário envia a mesma imagem: não pode descobrir que ela
        // existe, então ganha a própria cópia.
        $this->actingAs($other)
            ->post(route('photos.upload'), ['filepond' => $this->image()])
            ->assertOk();

        $this->assertSame(2, Photo::count());
    }

    public function test_single_upload_skips_images_already_in_the_archive(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->post('/photos', ['photos' => [$this->image()]]);

        $this->actingAs($user)
            ->post('/photos', ['photos' => [$this->image(), $this->image(800, 600)]])
            ->assertRedirect(route('photos.index'))
            ->assertSessionHas('status', fn ($msg) => str_contains($msg, 'já estava no acervo'));

        $this->assertSame(2, Photo::count());

        $this->actingAs($user)
            ->from(route('photos.create', ['mode' => 'single']))
            ->post('/photos', ['photos' => [$this->image()]])
            ->assertSessionHasErrors('photos');

        $this->assertSame(2, Photo::count());
    }

    public function test_only_the_uploader_or_an_admin_can_revert(): void
    {
        $owner = User::factory()->create(['role' => 'user']);
        $intruder = User::factory()->create(['role' => 'user']);
        $person = Person::factory()->create();
        $intruder->people()->attach($person->id);

        $id = $this->actingAs($owner)
            ->post(route('photos.upload'), ['filepond' => $this->image()])
            ->getContent();
        Photo::find($id)->people()->attach($person->id); // o intruso até VÊ a foto

        $this->actingAs($intruder)->delete(route('photos.upload.revert', $id))->assertForbidden();
        $this->assertNotNull(Photo::find($id));

        $this->actingAs($owner)->delete(route('photos.upload.revert', $id))->assertOk();
        $this->assertNull(Photo::find($id));
    }

    public function test_checksum_command_fills_older_photos(): void
    {
        Storage::put('photos/2026/01/antiga.jpg', 'conteudo');
        $photo = Photo::create([
            'filename' => 'antiga.jpg',
            'original_filename' => 'antiga.jpg',
            'storage_path' => 'photos/2026/01/antiga.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => 8,
        ]);

        $this->artisan('photos:checksums')->assertSuccessful();

        $this->assertSame(hash('sha256', 'conteudo'), $photo->fresh()->checksum);
    }

    public function test_exif_is_read_by_default_and_skipped_when_switched_off(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $comExif = $this->actingAs($user)
            ->post(route('photos.upload'), ['filepond' => $this->image(600, 400)])
            ->assertOk()->getContent();
        $semExif = $this->actingAs($user)
            ->post(route('photos.upload'), ['filepond' => $this->image(640, 480), 'read_exif' => '0'])
            ->assertOk()->getContent();

        $this->assertNotNull(Photo::find($comExif)->exif);
        $this->assertNull(Photo::find($semExif)->exif);

        // O resto do processamento continua: miniatura gerada normalmente.
        $photo = Photo::find($semExif);
        $this->assertTrue(Storage::exists('thumbnails/'.substr($photo->storage_path, strlen('photos/'))));
    }

    public function test_single_upload_honours_the_exif_switch(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // Formulário com o switch desligado: só o hidden "0" é enviado.
        $this->actingAs($user)
            ->post('/photos', ['photos' => [$this->image()], 'read_exif' => '0'])
            ->assertRedirect(route('photos.index'));

        $this->assertNull(Photo::first()->exif);
    }

    public function test_bulk_page_passes_the_parallel_upload_setting(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)
            ->get(route('photos.create', ['mode' => 'bulk']))
            ->assertOk()
            ->assertSee('data-parallel="3"', false);

        config(['kzion.upload.parallel' => 5]);

        $this->actingAs($user)
            ->get(route('photos.create', ['mode' => 'bulk']))
            ->assertSee('data-parallel="5"', false);
    }
}
