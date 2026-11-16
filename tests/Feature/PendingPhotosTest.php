<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Location;
use App\Models\Person;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Fotos sem dados": fila de preenchimento a partir do painel. Conta como sem
 * dados a foto sem título, descrição, local, pessoas e álbum (a data não
 * conta, porque o EXIF a preenche sozinho).
 */
class PendingPhotosTest extends TestCase
{
    use RefreshDatabase;

    private function photo(string $file, array $attrs = [], ?User $owner = null): Photo
    {
        return Photo::create(array_merge([
            'filename' => $file, 'original_filename' => $file, 'storage_path' => "photos/x/{$file}",
            'mime_type' => 'image/jpeg', 'file_size' => 1, 'uploaded_by' => $owner?->id,
        ], $attrs));
    }

    public function test_lists_only_photos_without_any_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->photo('vazia.jpg');
        $this->photo('so-com-data-do-exif.jpg', ['taken_at' => '1999-08-14 16:45:00', 'taken_at_precision' => 'day']);
        $this->photo('com-titulo.jpg', ['title' => 'Natal']);
        $this->photo('com-descricao.jpg', ['description' => 'Na casa da vó']);
        $this->photo('com-local.jpg', ['location_id' => Location::create(['name' => 'Campinas'])->id]);
        $this->photo('com-pessoa.jpg')->people()->attach(Person::factory()->create()->id);
        $this->photo('em-album.jpg')->albums()->attach(Album::create(['name' => 'Natal 1987'])->id);

        $this->actingAs($admin)
            ->get(route('photos.pending'))
            ->assertOk()
            ->assertSee('vazia.jpg')
            ->assertSee('so-com-data-do-exif.jpg')
            ->assertDontSee('com-titulo.jpg')
            ->assertDontSee('com-descricao.jpg')
            ->assertDontSee('com-local.jpg')
            ->assertDontSee('com-pessoa.jpg')
            ->assertDontSee('em-album.jpg');
    }

    public function test_each_item_links_to_the_photo_page(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $photo = $this->photo('vazia.jpg');

        $this->actingAs($admin)
            ->get(route('photos.pending'))
            ->assertSee(route('photos.show', $photo), false);
    }

    public function test_users_only_see_their_own_pending_photos(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $other = User::factory()->create(['role' => 'user']);

        $this->photo('minha.jpg', [], $user);
        $this->photo('do-outro.jpg', [], $other);

        $this->actingAs($user)
            ->get(route('photos.pending'))
            ->assertSee('minha.jpg')
            ->assertDontSee('do-outro.jpg');
    }

    public function test_dashboard_shows_the_button_with_the_count(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->photo('a.jpg');
        $this->photo('b.jpg');
        $this->photo('c.jpg', ['title' => 'preenchida']);

        $html = $this->actingAs($admin)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString(route('photos.pending'), $html);
        $this->assertMatchesRegularExpression('/Fotos sem dados\s*<span[^>]*>2<\/span>/', $html);
    }

    public function test_empty_list_says_everything_is_filled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->get(route('photos.pending'))->assertOk()->assertSee('Tudo preenchido');
    }
}
