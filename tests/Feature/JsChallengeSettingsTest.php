<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Support\JsChallengeSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Aba "Desafio JS" das Configurações: o que é salvo na tela se sobrepõe ao
 * .env, menos o JSCHALLENGE_ENABLED=false, que é a chave de emergência.
 * (O phpunit.xml define JSCHALLENGE_ENABLED=false — o .env "desligado".)
 */
class JsChallengeSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'enabled' => '1',
            'difficulty' => '18',
            'pass_ttl_hours' => '24',
            'bind_ip' => '0',
            'except_ips' => "200.1.2.3\n10.0.0.0/8",
            'trusted_enabled' => '1',
            'trusted_days' => '60',
            'trusted_network' => 'none',
            'trusted_max_ips' => '5',
        ], $override);
    }

    public function test_only_admins_reach_the_tab(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('settings.jschallenge'))->assertForbidden();
        $this->actingAs($user)->put(route('settings.jschallenge.update'), $this->payload())->assertForbidden();
    }

    public function test_tab_shows_tabler_settings_layout_with_both_tabs(): void
    {
        $this->actingAs($this->admin())
            ->get(route('settings.jschallenge'))
            ->assertOk()
            ->assertSee('list-group-transparent', false)
            ->assertSee(route('settings.edit'), false)
            ->assertSee('Dificuldade')
            ->assertSee('Desligado no .env'); // phpunit.xml: JSCHALLENGE_ENABLED=false
    }

    public function test_general_tab_keeps_its_content(): void
    {
        $this->actingAs($this->admin())
            ->get(route('settings.edit'))
            ->assertOk()
            ->assertSee('Permitir que visitantes criem a própria conta')
            ->assertSee(route('settings.jschallenge'), false);
    }

    public function test_saving_applies_values_right_away_and_persists_them(): void
    {
        $this->actingAs($this->admin())
            ->put(route('settings.jschallenge.update'), $this->payload())
            ->assertRedirect(route('settings.jschallenge'));

        $this->assertSame(18, config('jschallenge.difficulty'));
        $this->assertSame(24 * 3600, config('jschallenge.pass_ttl'));
        $this->assertSame(['200.1.2.3', '10.0.0.0/8'], config('jschallenge.except_ips'));
        $this->assertSame(60, config('jschallenge.trusted.ttl_days'));
        $this->assertSame('none', config('jschallenge.trusted.network'));
        $this->assertSame(5, config('jschallenge.trusted.max_ips_per_hour'));
        $this->assertSame('18', Setting::get('jschallenge.difficulty'));
    }

    public function test_env_kill_switch_wins_over_the_screen(): void
    {
        $this->actingAs($this->admin())
            ->put(route('settings.jschallenge.update'), $this->payload(['enabled' => '1']));

        // .env (phpunit.xml) diz false: continua desligado.
        $this->assertFalse(config('jschallenge.enabled'));
    }

    public function test_screen_can_turn_it_on_when_env_allows(): void
    {
        config(['jschallenge._env.enabled' => true]); // .env "ligado"

        $this->actingAs($this->admin())
            ->put(route('settings.jschallenge.update'), $this->payload(['enabled' => '1']));
        $this->assertTrue(config('jschallenge.enabled'));

        // Agora o desafio está ligado de verdade: um envio sem passe seria
        // barrado (403). No navegador o admin já tem o passe; aqui, dispensa.
        $this->withoutMiddleware(\App\Http\Middleware\JsChallengeGate::class)
            ->actingAs($this->admin())
            ->put(route('settings.jschallenge.update'), $this->payload(['enabled' => '0']))
            ->assertRedirect(route('settings.jschallenge'));
        $this->assertFalse(config('jschallenge.enabled'));
    }

    public function test_out_of_range_and_bad_ips_are_rejected(): void
    {
        $this->actingAs($this->admin())
            ->put(route('settings.jschallenge.update'), $this->payload([
                'difficulty' => '30',
                'except_ips' => "200.1.2.3\nnão-é-ip\n10.0.0.0/99",
            ]))
            ->assertSessionHasErrors(['difficulty', 'except_ips']);

        $this->assertNull(Setting::get('jschallenge.difficulty'));
    }

    public function test_reset_goes_back_to_env_defaults(): void
    {
        $envDifficulty = JsChallengeSettings::defaults()['difficulty'];

        $this->actingAs($this->admin())->put(route('settings.jschallenge.update'), $this->payload(['difficulty' => '20']));
        $this->assertSame(20, config('jschallenge.difficulty'));

        $this->actingAs($this->admin())
            ->put(route('settings.jschallenge.update'), ['action' => 'reset'])
            ->assertRedirect(route('settings.jschallenge'));

        $this->assertSame($envDifficulty, config('jschallenge.difficulty'));
        $this->assertSame(0, Setting::where('key', 'like', 'jschallenge.%')->count());
    }

    public function test_saved_values_are_applied_on_boot(): void
    {
        Setting::put('jschallenge.difficulty', '21');
        Setting::put('jschallenge.trusted_network', 'asn');
        Cache::flush();

        JsChallengeSettings::apply(); // o que o provider faz a cada requisição

        $this->assertSame(21, config('jschallenge.difficulty'));
        $this->assertSame('asn', config('jschallenge.trusted.network'));
    }
}
