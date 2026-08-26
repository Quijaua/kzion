<?php

namespace Tests\Feature;

use App\Models\Album;
use App\Models\Person;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * A regra de visibilidade é PERMISSIVA: basta o usuário ter acesso a uma das
 * pessoas marcadas na foto. Continuam valendo dois limites — a foto precisa ter
 * ao menos uma pessoa marcada, e "ver a foto" não é o mesmo que "poder abrir a
 * página da pessoa". Estes testes cobrem isso e os pontos onde a regra costuma
 * vazar: contagens que não passam pelo filtro e o álbum, que é camada paralela.
 */
class PhotoVisibilityTest extends TestCase
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

    public function test_the_uploader_always_sees_their_own_photo(): void
    {
        $dono = User::factory()->create(['role' => 'user']);
        $outro = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        // Sem ninguém marcado e sem nenhuma pessoa concedida ao dono:
        // só o vínculo de propriedade sustenta a visibilidade.
        $foto = $this->photo('minha-foto.jpg', [], $dono);

        $this->assertSame(['minha-foto.jpg'], Photo::accessibleTo($dono)->pluck('filename')->all());
        $this->assertSame([], Photo::accessibleTo($outro)->pluck('filename')->all());
        $this->assertSame(['minha-foto.jpg'], Photo::accessibleTo($admin)->pluck('filename')->all());

        $this->actingAs($dono)->get(route('photos.show', $foto))->assertOk();
        $this->actingAs($outro)->get(route('photos.show', $foto))->assertNotFound();
        $this->actingAs($admin)->get(route('photos.show', $foto))->assertOk();
    }

    public function test_upload_through_the_form_is_immediately_visible_to_the_uploader(): void
    {
        Storage::fake('local');

        $dono = User::factory()->create(['role' => 'user']); // sem nenhuma pessoa concedida

        $this->actingAs($dono)->post(route('photos.store'), [
            'photos' => [UploadedFile::fake()->image('teste.jpg', 600, 400)],
            'title' => 'Foto de teste',
        ])->assertRedirect(route('photos.index'));

        $foto = Photo::first();
        $this->assertNotNull($foto);
        $this->assertSame($dono->id, $foto->uploaded_by, 'o upload precisa carimbar o dono');

        $this->actingAs($dono)->get(route('photos.index'))
            ->assertOk()
            ->assertSee('Foto de teste');
    }

    public function test_bulk_upload_also_stamps_the_owner(): void
    {
        Storage::fake('local');

        $dono = User::factory()->create(['role' => 'user']);

        $resposta = $this->actingAs($dono)->post(route('photos.upload'), [
            'filepond' => UploadedFile::fake()->image('lote.jpg', 600, 400),
        ])->assertOk();

        $foto = Photo::find((int) $resposta->getContent());
        $this->assertSame($dono->id, $foto->uploaded_by);
        $this->actingAs($dono)->get(route('photos.show', $foto))->assertOk();
    }

    public function test_ownership_does_not_leak_past_search_filters(): void
    {
        // O vínculo de propriedade entra na regra como um OU. Este teste fixa
        // que ele continua subordinado aos filtros da busca — um OU mal
        // agrupado devolveria as fotos do dono ignorando o filtro. Hoje quem
        // garante isso é o próprio Eloquent, que aninha as condições de um
        // scope local; o teste é o que segura essa garantia se a regra sair
        // de dentro do scope algum dia.
        $maria = Person::factory()->create(['name' => 'Maria']);
        $user = User::factory()->create(['role' => 'user']);
        $user->people()->attach($maria->id);

        $this->photo('de-1998.jpg', [$maria->id], $user)->update(['taken_at' => '1998-07-01']);
        $this->photo('de-2005.jpg', [$maria->id], $user)->update(['taken_at' => '2005-03-02']);

        $this->actingAs($user)->get(route('photos.index', ['year' => '1998']))
            ->assertOk()
            ->assertSee('de-1998.jpg')
            ->assertDontSee('de-2005.jpg');

        // E o mesmo pela busca por título, que usa um LIKE.
        $this->actingAs($user)->get(route('photos.index', ['name' => 'nao-existe']))
            ->assertOk()
            ->assertDontSee('de-1998.jpg')
            ->assertDontSee('de-2005.jpg');
    }

    public function test_untagged_photos_are_hidden_from_regular_users(): void
    {
        $maria = Person::factory()->create(['name' => 'Maria']);
        $tagged = $this->photo('com-pessoa.jpg', [$maria->id]);
        $untagged = $this->photo('sem-pessoa.jpg');

        $user = User::factory()->create(['role' => 'user']);
        $user->people()->attach($maria->id);

        $this->assertSame(
            ['com-pessoa.jpg'],
            Photo::accessibleTo($user)->pluck('filename')->all()
        );

        // A foto sem ninguém marcado não tem pessoa "proibida" para barrá-la;
        // sem exigir ao menos uma pessoa acessível ela vazaria para todo mundo.
        $this->actingAs($user)->get(route('photos.show', $untagged))->assertNotFound();
    }

    public function test_user_without_granted_people_sees_nothing(): void
    {
        $maria = Person::factory()->create();
        $this->photo('com-pessoa.jpg', [$maria->id]);
        $this->photo('sem-pessoa.jpg');

        $user = User::factory()->create(['role' => 'user']);

        $this->assertSame(0, Photo::accessibleTo($user)->count());
    }

    public function test_person_card_count_matches_what_the_person_page_lists(): void
    {
        $maria = Person::factory()->create(['name' => 'Maria']);
        $joao = Person::factory()->create(['name' => 'Joao']);

        $this->photo('so-maria.jpg', [$maria->id]);
        $this->photo('maria-e-joao.jpg', [$maria->id, $joao->id]);
        $this->photo('so-joao.jpg', [$joao->id]);

        $user = User::factory()->create(['role' => 'user']);
        $user->people()->attach($maria->id);

        // As duas fotos com a Maria contam; a do Joao sozinho não.
        $this->actingAs($user)->get(route('people.index'))
            ->assertOk()
            ->assertSee('2 fotos');

        $this->actingAs($user)->get(route('people.show', $maria))
            ->assertOk()
            ->assertSee('so-maria.jpg')
            ->assertSee('maria-e-joao.jpg')
            ->assertDontSee('so-joao.jpg');
    }

    public function test_seeing_a_photo_does_not_grant_access_to_the_other_people_in_it(): void
    {
        $maria = Person::factory()->create(['name' => 'Maria']);
        $joao = Person::factory()->create(['name' => 'Joao']);
        $foto = $this->photo('maria-e-joao.jpg', [$maria->id, $joao->id]);

        $user = User::factory()->create(['role' => 'user']);
        $user->people()->attach($maria->id);

        // A foto abre e mostra os dois nomes...
        $resposta = $this->actingAs($user)->get(route('photos.show', $foto))
            ->assertOk()
            ->assertSee('Maria')
            ->assertSee('Joao');

        // ...mas só a Maria é um link; o Joao é texto.
        $html = $resposta->getContent();
        $this->assertStringContainsString(route('people.show', $maria), $html);
        $this->assertStringNotContainsString(route('people.show', $joao), $html);

        // E a página do Joao continua bloqueada.
        $this->actingAs($user)->get(route('people.show', $joao))->assertForbidden();
    }

    public function test_editing_a_photo_keeps_the_people_the_editor_cannot_access(): void
    {
        $maria = Person::factory()->create(['name' => 'Maria']);
        $joao = Person::factory()->create(['name' => 'Joao']);
        $foto = $this->photo('maria-e-joao.jpg', [$maria->id, $joao->id]);

        $user = User::factory()->create(['role' => 'user']);
        $user->people()->attach($maria->id);

        // O seletor não oferece o Joao; salvar não pode desmarcá-lo.
        $this->actingAs($user)->put(route('photos.update', $foto), [
            'title' => 'Titulo novo',
            'person_ids' => [$maria->id],
        ])->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$maria->id, $joao->id],
            $foto->fresh()->people()->pluck('people.id')->all()
        );
    }

    public function test_album_does_not_bypass_photo_permissions(): void
    {
        $maria = Person::factory()->create(['name' => 'Maria']);
        $joao = Person::factory()->create(['name' => 'Joao']);

        $visivel = $this->photo('so-maria.jpg', [$maria->id]);
        $doJoao = $this->photo('so-joao.jpg', [$joao->id]);
        $semNinguem = $this->photo('sem-pessoa.jpg');

        $album = Album::create(['name' => 'Familia', 'cover_photo_id' => $doJoao->id]);
        $album->photos()->sync([$visivel->id, $doJoao->id, $semNinguem->id]);

        $user = User::factory()->create(['role' => 'user']);
        $user->people()->attach($maria->id);

        $this->actingAs($user)->get(route('albums.show', $album))
            ->assertOk()
            ->assertSee('so-maria.jpg')
            ->assertDontSee('so-joao.jpg')
            ->assertDontSee('sem-pessoa.jpg');

        $this->actingAs($user)->get(route('albums.index'))
            ->assertOk()
            ->assertSee('1 foto')
            ->assertDontSee('3 fotos');
    }

    public function test_saving_an_album_keeps_photos_the_editor_cannot_see(): void
    {
        $maria = Person::factory()->create();
        $joao = Person::factory()->create();

        $visivel = $this->photo('so-maria.jpg', [$maria->id]);
        $doJoao = $this->photo('so-joao.jpg', [$joao->id]);

        $album = Album::create(['name' => 'Familia', 'cover_photo_id' => $doJoao->id]);
        $album->photos()->sync([$visivel->id, $doJoao->id]);

        $user = User::factory()->create(['role' => 'user']);
        $user->people()->attach($maria->id);

        // O formulário só ofereceu a foto visível; salvar não pode apagar a outra.
        $this->actingAs($user)->put(route('albums.update', $album), [
            'name' => 'Familia',
            'photo_ids' => [$visivel->id],
        ])->assertRedirect();

        $this->assertEqualsCanonicalizing(
            [$visivel->id, $doJoao->id],
            $album->fresh()->photos()->pluck('photos.id')->all()
        );
        $this->assertSame($doJoao->id, $album->fresh()->cover_photo_id);
    }

    public function test_photo_form_does_not_leak_inaccessible_people(): void
    {
        $maria = Person::factory()->create(['name' => 'Maria']);
        Person::factory()->create(['name' => 'Joao']);

        $user = User::factory()->create(['role' => 'user']);
        $user->people()->attach($maria->id);

        $this->actingAs($user)->get(route('photos.create', ['mode' => 'single']))
            ->assertOk()
            ->assertSee('Maria')
            ->assertDontSee('>Joao<', false);
    }

    public function test_admin_still_sees_everything(): void
    {
        $maria = Person::factory()->create();
        $this->photo('com-pessoa.jpg', [$maria->id]);
        $this->photo('sem-pessoa.jpg');

        $admin = User::factory()->create(['role' => 'admin']);

        $this->assertSame(2, Photo::accessibleTo($admin)->count());
        $this->actingAs($admin)->get(route('photos.index'))
            ->assertOk()
            ->assertSee('sem-pessoa.jpg');
    }
}
