<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Person;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Locais: o link na página da foto, a página do local filtrando pela mesma
 * regra de visibilidade do acervo, e o CRUD.
 */
class LocationTest extends TestCase
{
    use RefreshDatabase;

    private function photo(string $name, ?Location $location = null, array $peopleIds = [], ?User $owner = null): Photo
    {
        $photo = Photo::create([
            'filename' => $name,
            'original_filename' => $name,
            'storage_path' => "photos/2026/11/{$name}",
            'mime_type' => 'image/jpeg',
            'file_size' => 10,
            'title' => $name,
            'location_id' => $location?->id,
            'uploaded_by' => $owner?->id,
        ]);

        if ($peopleIds) {
            $photo->people()->sync($peopleIds);
        }

        return $photo;
    }

    public function test_photo_page_links_to_its_location(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $campinas = Location::create(['name' => 'Campinas']);
        $photo = $this->photo('a.jpg', $campinas);

        $this->actingAs($admin)
            ->get(route('photos.show', $photo))
            ->assertOk()
            ->assertSee(route('locations.show', $campinas), false)
            ->assertSee('Campinas');
    }

    public function test_location_page_shows_only_photos_the_user_can_see(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $pedro = Person::factory()->create();
        $ana = Person::factory()->create();
        $user->people()->attach($pedro->id);

        $praia = Location::create(['name' => 'Praia Grande']);
        $this->photo('visivel.jpg', $praia, [$pedro->id]);
        $this->photo('oculta.jpg', $praia, [$ana->id]);
        $this->photo('outro-local.jpg', null, [$pedro->id]);

        $this->actingAs($user)
            ->get(route('locations.show', $praia))
            ->assertOk()
            ->assertSee('visivel.jpg')
            ->assertDontSee('oculta.jpg')
            ->assertDontSee('outro-local.jpg');

        // A contagem do card segue a mesma regra da página.
        $this->actingAs($user)
            ->get(route('locations.index'))
            ->assertOk()
            ->assertSee('Praia Grande')
            ->assertSee('1 foto');
    }

    public function test_any_user_can_create_a_location(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $response = $this->actingAs($user)->post(route('locations.store'), [
            'name' => '  Sítio   do vô ',
            'description' => 'Interior de SP',
        ]);

        $location = Location::first();
        $this->assertSame('Sítio do vô', $location->name);
        $response->assertRedirect(route('locations.show', $location));
    }

    public function test_location_names_are_unique_ignoring_case(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        Location::create(['name' => 'Campinas']);

        $this->actingAs($user)
            ->post(route('locations.store'), ['name' => 'campinas'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Location::count());
    }

    public function test_photo_can_be_assigned_a_location_on_update(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $campinas = Location::create(['name' => 'Campinas']);
        $photo = $this->photo('a.jpg');

        $this->actingAs($admin)
            ->put(route('photos.update', $photo), ['location_id' => $campinas->id])
            ->assertRedirect(route('photos.show', $photo));

        $this->assertSame($campinas->id, $photo->fresh()->location_id);
    }

    public function test_edit_form_offers_locations(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Location::create(['name' => 'Ubatuba']);
        $photo = $this->photo('a.jpg');

        $this->actingAs($admin)
            ->get(route('photos.edit', $photo))
            ->assertOk()
            ->assertSee('name="location_id"', false)
            ->assertSee('Ubatuba');
    }

    public function test_deleting_a_location_keeps_its_photos(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $campinas = Location::create(['name' => 'Campinas']);
        $photo = $this->photo('a.jpg', $campinas);

        $this->actingAs($admin)
            ->delete(route('locations.destroy', $campinas))
            ->assertRedirect(route('locations.index'));

        $this->assertNull(Location::find($campinas->id));
        $this->assertNotNull($photo->fresh());
        $this->assertNull($photo->fresh()->location_id);
    }

    public function test_user_cannot_change_a_location_holding_photos_they_cannot_see(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $ana = Person::factory()->create();
        $campinas = Location::create(['name' => 'Campinas']);
        $this->photo('alheia.jpg', $campinas, [$ana->id]);

        $this->actingAs($user)->get(route('locations.edit', $campinas))->assertForbidden();
        $this->actingAs($user)->put(route('locations.update', $campinas), ['name' => 'Paris'])->assertForbidden();
        $this->actingAs($user)->delete(route('locations.destroy', $campinas))->assertForbidden();

        $this->assertSame('Campinas', $campinas->fresh()->name);

        // Mas pode abrir a página do local (vazia para ele) e não vê os botões.
        $this->actingAs($user)
            ->get(route('locations.show', $campinas))
            ->assertOk()
            ->assertDontSee(route('locations.edit', $campinas), false);
    }

    public function test_user_can_manage_a_location_whose_photos_are_all_visible_to_them(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $campinas = Location::create(['name' => 'Campinas']);
        $this->photo('minha.jpg', $campinas, [], $user);

        $this->actingAs($user)
            ->put(route('locations.update', $campinas), ['name' => 'Campinas/SP'])
            ->assertRedirect(route('locations.show', $campinas));

        $this->assertSame('Campinas/SP', $campinas->fresh()->name);
    }

    public function test_photo_search_by_location_text_matches_location_name(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->photo('na-praia.jpg', Location::create(['name' => 'Praia Grande']));
        $this->photo('na-cidade.jpg', Location::create(['name' => 'Campinas']));

        $this->actingAs($admin)
            ->get(route('photos.index', ['location' => 'praia']))
            ->assertOk()
            ->assertSee('na-praia.jpg')
            ->assertDontSee('na-cidade.jpg');
    }
}
