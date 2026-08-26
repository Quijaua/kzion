<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function comum(): User
    {
        return User::factory()->create(['role' => 'user']);
    }

    public function test_settings_are_admin_only(): void
    {
        $this->actingAs($this->admin())->get(route('settings.edit'))->assertOk();

        $this->actingAs($this->comum())->get(route('settings.edit'))->assertForbidden();
        $this->actingAs($this->comum())->put(route('settings.update'), [
            'registration_enabled' => '0',
        ])->assertForbidden();

        Auth::logout();
        $this->get(route('settings.edit'))->assertRedirect(route('login'));
    }

    public function test_only_admins_get_the_sidebar_entry(): void
    {
        $this->actingAs($this->admin())->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('settings.edit'));

        $this->actingAs($this->comum())->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee(route('settings.edit'));
    }

    public function test_registration_is_open_by_default(): void
    {
        // Sem nenhuma linha em `settings`, o comportamento continua o de antes
        // desta tela existir.
        $this->assertTrue(Setting::registrationEnabled());

        $this->get(route('login'))->assertOk()->assertSee('Criar conta');
        $this->get(route('register'))->assertOk();
    }

    public function test_disabling_registration_hides_the_link_and_closes_the_route(): void
    {
        $this->actingAs($this->admin())->put(route('settings.update'), [
            'registration_enabled' => '0',
        ])->assertRedirect(route('settings.edit'));

        $this->assertFalse(Setting::registrationEnabled());

        Auth::logout();

        // O link some do login...
        $this->get(route('login'))->assertOk()->assertDontSee('Criar conta');

        // ...e a rota fecha de verdade, para quem souber o endereço de cor.
        $this->get(route('register'))->assertNotFound();
        $this->post(route('register'), [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => 'senha12345',
            'password_confirmation' => 'senha12345',
        ])->assertNotFound();

        $this->assertDatabaseMissing('users', ['email' => 'intruso@example.com']);
    }

    public function test_registration_can_be_switched_back_on(): void
    {
        Setting::put(Setting::REGISTRATION_ENABLED, false);
        $this->get(route('register'))->assertNotFound();

        $this->actingAs($this->admin())->put(route('settings.update'), [
            'registration_enabled' => '1',
        ])->assertRedirect();

        Auth::logout();

        $this->get(route('register'))->assertOk();
        $this->get(route('login'))->assertOk()->assertSee('Criar conta');
    }

    public function test_the_switch_reflects_the_stored_value(): void
    {
        Setting::put(Setting::REGISTRATION_ENABLED, true);
        $this->actingAs($this->admin())->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('checked', false)
            ->assertSee('ligado');

        Setting::put(Setting::REGISTRATION_ENABLED, false);
        $this->actingAs($this->admin())->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('desligado');
    }

    public function test_admin_toggles_the_full_people_list_per_user(): void
    {
        $paulo = User::factory()->create(['role' => 'user', 'name' => 'Paulo']);
        $maria = User::factory()->create(['role' => 'user', 'name' => 'Maria']);

        $this->actingAs($this->admin())->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Paulo')
            ->assertSee('Maria')
            ->assertSee('all_people_access['.$paulo->id.']', false);

        $this->actingAs($this->admin())->put(route('settings.update'), [
            'registration_enabled' => '1',
            'all_people_access' => [$paulo->id => '1', $maria->id => '0'],
        ])->assertRedirect(route('settings.edit'));

        $this->assertTrue($paulo->fresh()->seesAllPeople());
        $this->assertFalse($maria->fresh()->seesAllPeople());

        // e desligar de volta
        $this->actingAs($this->admin())->put(route('settings.update'), [
            'registration_enabled' => '1',
            'all_people_access' => [$paulo->id => '0'],
        ])->assertRedirect();

        $this->assertFalse($paulo->fresh()->seesAllPeople());
    }

    public function test_admins_are_not_listed_and_cannot_be_targeted_through_the_form(): void
    {
        $outroAdmin = User::factory()->create(['role' => 'admin', 'name' => 'Outro Admin']);

        $this->actingAs($this->admin())->get(route('settings.edit'))
            ->assertOk()
            ->assertDontSee('all_people_access['.$outroAdmin->id.']', false);

        // Um POST forjado com o id de um admin não pode gravar a coluna nele.
        $this->actingAs($this->admin())->put(route('settings.update'), [
            'registration_enabled' => '1',
            'all_people_access' => [$outroAdmin->id => '1'],
        ])->assertRedirect();

        $this->assertFalse((bool) $outroAdmin->fresh()->all_people_access);
    }

    public function test_update_rejects_a_missing_value(): void
    {
        $this->actingAs($this->admin())
            ->from(route('settings.edit'))
            ->put(route('settings.update'), [])
            ->assertSessionHasErrors('registration_enabled');
    }
}
