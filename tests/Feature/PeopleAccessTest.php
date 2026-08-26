<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Duas listas de pessoas convivem, e o ponto destes testes é que elas NÃO
 * podem se misturar: `all_people_access` amplia o que o usuário lista e marca,
 * e não pode ampliar as fotos que ele vê.
 */
class PeopleAccessTest extends TestCase
{
    use RefreshDatabase;

    private function photo(string $name, array $peopleIds = [], ?User $owner = null): Photo
    {
        $photo = Photo::create([
            'filename' => $name,
            'original_filename' => $name,
            'storage_path' => "photos/2026/08/{$name}",
            'mime_type' => 'image/jpeg',
            'file_size' => 10,
            'uploaded_by' => $owner?->id,
        ]);

        if ($peopleIds) {
            $photo->people()->sync($peopleIds);
        }

        return $photo;
    }

    public function test_a_person_created_by_a_user_shows_up_for_that_user(): void
    {
        $user = User::factory()->create(['role' => 'user']); // sem nenhuma pessoa

        $this->actingAs($user)->post(route('people.store'), [
            'name' => 'Vovó Carmen',
        ])->assertRedirect();

        $pessoa = Person::where('name', 'Vovó Carmen')->firstOrFail();

        // O vínculo é criado na hora, senão a pessoa sumiria de quem a criou.
        $this->assertTrue($user->fresh()->canSeePerson($pessoa));

        $this->actingAs($user)->get(route('people.index'))->assertOk()->assertSee('Vovó Carmen');
        $this->actingAs($user)->get(route('people.show', $pessoa))->assertOk();
    }

    public function test_a_person_created_by_a_user_is_selectable_when_editing_a_photo(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $foto = $this->photo('minha.jpg', [], $user); // vê por ser o dono

        $this->actingAs($user)->post(route('people.store'), ['name' => 'Tio Beto']);
        $pessoa = Person::where('name', 'Tio Beto')->firstOrFail();

        $this->actingAs($user)->get(route('photos.edit', $foto))
            ->assertOk()
            ->assertSee('Tio Beto');

        // E marcá-la de fato passa pela validação.
        $this->actingAs($user)->put(route('photos.update', $foto), [
            'person_ids' => [$pessoa->id],
        ])->assertRedirect();

        $this->assertSame([$pessoa->id], $foto->fresh()->people()->pluck('people.id')->all());
    }

    public function test_a_person_created_by_a_user_also_shows_up_for_admins(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($user)->post(route('people.store'), ['name' => 'Prima Clara']);

        $this->actingAs($admin)->get(route('people.index'))->assertOk()->assertSee('Prima Clara');
    }

    public function test_full_people_list_widens_the_list_but_not_the_photos(): void
    {
        $minha = Person::factory()->create(['name' => 'Minha Pessoa']);
        $alheia = Person::factory()->create(['name' => 'Pessoa Alheia']);

        $this->photo('minha.jpg', [$minha->id]);
        $this->photo('alheia.jpg', [$alheia->id]);

        $user = User::factory()->create(['role' => 'user', 'all_people_access' => true]);
        $user->people()->attach($minha->id);

        // Lista: vê os dois nomes.
        $this->actingAs($user)->get(route('people.index'))
            ->assertOk()
            ->assertSee('Minha Pessoa')
            ->assertSee('Pessoa Alheia');

        // Fotos: continua vendo só a da pessoa concedida.
        $this->assertSame(['minha.jpg'], Photo::accessibleTo($user)->pluck('filename')->all());
        $this->actingAs($user)->get(route('photos.index'))
            ->assertOk()
            ->assertSee('minha.jpg')
            ->assertDontSee('alheia.jpg');

        // Abrir a página da pessoa alheia é permitido, mas sem fotos dela.
        $this->actingAs($user)->get(route('people.show', $alheia))
            ->assertOk()
            ->assertDontSee('alheia.jpg');
    }

    public function test_without_the_flag_the_list_stays_restricted(): void
    {
        $minha = Person::factory()->create(['name' => 'Minha Pessoa']);
        Person::factory()->create(['name' => 'Pessoa Alheia']);

        $user = User::factory()->create(['role' => 'user', 'all_people_access' => false]);
        $user->people()->attach($minha->id);

        $this->actingAs($user)->get(route('people.index'))
            ->assertOk()
            ->assertSee('Minha Pessoa')
            ->assertDontSee('Pessoa Alheia');

        $this->actingAs($user)->get(route('photos.create', ['mode' => 'single']))
            ->assertOk()
            ->assertDontSee('>Pessoa Alheia<', false);
    }

    public function test_a_user_cannot_open_or_edit_a_person_outside_their_list(): void
    {
        $alheia = Person::factory()->create(['name' => 'Pessoa Alheia']);
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('people.show', $alheia))->assertForbidden();
        $this->actingAs($user)->get(route('people.edit', $alheia))->assertForbidden();
        $this->actingAs($user)->put(route('people.update', $alheia), ['name' => 'Sequestrada'])
            ->assertForbidden();
        $this->actingAs($user)->delete(route('people.destroy', $alheia))->assertForbidden();

        $this->assertDatabaseHas('people', ['name' => 'Pessoa Alheia']);
    }
}
