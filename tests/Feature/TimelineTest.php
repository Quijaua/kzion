<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Linha do tempo (anos → meses) pela data da foto, com precisão de ano,
 * ano+mês ou data completa.
 */
class TimelineTest extends TestCase
{
    use RefreshDatabase;

    private function photo(string $name, ?int $y = null, ?int $m = null, ?int $d = null, array $people = [], ?User $owner = null): Photo
    {
        [$date, $precision] = Photo::dateFromParts($y, $m, $d);

        $photo = Photo::create([
            'filename' => $name, 'original_filename' => $name, 'storage_path' => "photos/x/{$name}",
            'mime_type' => 'image/jpeg', 'file_size' => 1, 'title' => $name,
            'taken_at' => $date, 'taken_at_precision' => $precision, 'uploaded_by' => $owner?->id,
        ]);
        $photo->people()->sync($people);

        return $photo;
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    // ---------------------------------------------------------------- datas

    public function test_labels_follow_the_precision(): void
    {
        $this->assertSame('1987', $this->photo('a', 1987)->taken_at_label);
        $this->assertSame('março de 1987', $this->photo('b', 1987, 3)->taken_at_label);
        $this->assertSame('12/03/1987', $this->photo('c', 1987, 3, 12)->taken_at_label);
        $this->assertNull($this->photo('d')->taken_at_label);
    }

    public function test_photos_before_1970_are_accepted(): void
    {
        $photo = $this->photo('antiga', 1948, 5);

        $this->assertSame('1948-05-01', $photo->fresh()->taken_at->format('Y-m-d'));
    }

    public function test_edit_saves_year_only_and_year_month(): void
    {
        $admin = $this->admin();
        $photo = $this->photo('p');

        $this->actingAs($admin)->put(route('photos.update', $photo), ['taken_year' => '1987'])->assertRedirect();
        $this->assertSame(['1987-01-01', 'year'], [$photo->fresh()->taken_at->format('Y-m-d'), $photo->fresh()->taken_at_precision]);

        $this->actingAs($admin)->put(route('photos.update', $photo), ['taken_year' => '1987', 'taken_month' => '3'])->assertRedirect();
        $this->assertSame(['1987-03-01', 'month'], [$photo->fresh()->taken_at->format('Y-m-d'), $photo->fresh()->taken_at_precision]);

        $this->actingAs($admin)->put(route('photos.update', $photo), [])->assertRedirect();
        $this->assertNull($photo->fresh()->taken_at);
        $this->assertNull($photo->fresh()->taken_at_precision);
    }

    public function test_edit_rejects_incomplete_or_impossible_dates(): void
    {
        $admin = $this->admin();
        $photo = $this->photo('p');

        $this->actingAs($admin)->put(route('photos.update', $photo), ['taken_month' => '3'])
            ->assertSessionHasErrors('taken_year');
        $this->actingAs($admin)->put(route('photos.update', $photo), ['taken_year' => '1987', 'taken_day' => '5'])
            ->assertSessionHasErrors('taken_month');
        $this->actingAs($admin)->put(route('photos.update', $photo), ['taken_year' => '1987', 'taken_month' => '2', 'taken_day' => '30'])
            ->assertSessionHasErrors('taken_day');
        $this->actingAs($admin)->put(route('photos.update', $photo), ['taken_year' => '1700'])
            ->assertSessionHasErrors('taken_year');
    }

    public function test_saving_the_same_full_date_keeps_the_exif_time(): void
    {
        $admin = $this->admin();
        $photo = $this->photo('exif');
        $photo->update(['taken_at' => '1999-08-14 16:45:00', 'taken_at_precision' => 'day']);

        $this->actingAs($admin)->put(route('photos.update', $photo), [
            'taken_year' => '1999', 'taken_month' => '8', 'taken_day' => '14', 'title' => 'novo título',
        ]);

        $this->assertSame('1999-08-14 16:45:00', $photo->fresh()->taken_at->format('Y-m-d H:i:s'));
    }

    public function test_edit_form_prefills_the_known_parts(): void
    {
        $photo = $this->photo('p', 1987, 3);

        $html = $this->actingAs($this->admin())->get(route('photos.edit', $photo))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/name="taken_year"[^>]*value="1987"/s', $html);
        $this->assertMatchesRegularExpression('/<option value="3"\s+selected/', $html);
    }

    // ---------------------------------------------------------- linha do tempo

    public function test_timeline_lists_years_with_counts_and_undated(): void
    {
        $this->photo('a', 1987, 3);
        $this->photo('b', 1987);
        $this->photo('c', 1999, 8, 14);
        $this->photo('d');

        $this->actingAs($this->admin())
            ->get(route('timeline.index'))
            ->assertOk()
            ->assertSeeInOrder(['Anos 1980', '1987', '2 fotos', 'Anos 1990', '1999', '1 foto', 'Sem data'])
            ->assertSee(route('timeline.year', 1987), false)
            ->assertSee(route('photos.index', ['undated' => 1]), false);
    }

    public function test_year_page_splits_months_and_unknown_month(): void
    {
        $this->photo('marco1', 1987, 3);
        $this->photo('marco2', 1987, 3, 20);
        $this->photo('so-ano', 1987);          // guardada em 01/01, NÃO é de janeiro
        $this->photo('outro-ano', 1990, 1);

        $html = $this->actingAs($this->admin())
            ->get(route('timeline.year', 1987))
            ->assertOk()
            ->assertSee('Mês não informado')
            ->assertSee(route('photos.index', ['year' => 1987, 'month' => 3]))
            ->assertSee(route('timeline.year', 1990), false) // próximo ano
            ->getContent();

        // Janeiro de 1987 fica vazio: a foto "só do ano" não conta nele.
        $this->assertStringNotContainsString(e(route('photos.index', ['year' => 1987, 'month' => 1])), $html);
    }

    public function test_photo_list_filters_by_month_and_unknown_month(): void
    {
        $admin = $this->admin();
        $this->photo('janeiro-real', 1987, 1, 10);
        $this->photo('so-o-ano', 1987);
        $this->photo('marco', 1987, 3);
        $this->photo('sem-data');

        $this->actingAs($admin)->get(route('photos.index', ['year' => 1987, 'month' => 1]))
            ->assertSee('janeiro-real')->assertDontSee('so-o-ano')->assertDontSee('marco');

        $this->actingAs($admin)->get(route('photos.index', ['year' => 1987, 'month' => 'unknown']))
            ->assertSee('so-o-ano')->assertDontSee('janeiro-real');

        $this->actingAs($admin)->get(route('photos.index', ['undated' => 1]))
            ->assertSee('sem-data')->assertDontSee('marco');
    }

    public function test_counts_only_include_photos_the_user_can_see(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $pedro = Person::factory()->create();
        $ana = Person::factory()->create();
        $user->people()->attach($pedro->id);

        $this->photo('visivel', 1987, 3, null, [$pedro->id]);
        $this->photo('oculta', 1987, 3, null, [$ana->id]);
        $this->photo('oculta-2001', 2001, 5, null, [$ana->id]);

        $this->actingAs($user)->get(route('timeline.index'))
            ->assertOk()->assertSee('1987')->assertSee('1 foto')->assertDontSee('2001');

        $this->actingAs($user)->get(route('timeline.year', 2001))
            ->assertOk()->assertSee('Nenhuma foto de 2001');
    }
}
