<?php

namespace Tests\Feature;

use App\Models\JsChallengeDevice;
use App\Models\User;
use App\Support\JsChallenge;
use App\Support\TrustedDevices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Desafio JS integrado ao kzion. O phpunit.xml desliga o desafio para o resto
 * da suíte; aqui ele é ligado teste a teste, com dificuldade baixa.
 */
class JsChallengeTest extends TestCase
{
    use RefreshDatabase;

    private function enable(): void
    {
        config(['jschallenge.enabled' => true, 'jschallenge.difficulty' => 4]);
    }

    /** Resolve o proof-of-work como o navegador faria. */
    private function solve(string $token): string
    {
        for ($n = 0; ; $n++) {
            if (JsChallenge::verifyProof($token, (string) $n)) {
                return (string) $n;
            }
        }
    }

    private function tokenFromPage(string $html): string
    {
        preg_match('/token:\s*"([^"]+)"/', $html, $m);

        return $m[1];
    }

    public function test_visitor_without_pass_sees_the_challenge(): void
    {
        $this->enable();

        $this->get('/login')
            ->assertStatus(503)
            ->assertSee('Verificando seu navegador', false)
            ->assertHeader('Retry-After');
    }

    public function test_post_or_json_without_pass_is_refused(): void
    {
        $this->enable();

        $this->post('/login', ['email' => 'a@b.c', 'password' => 'x'])->assertForbidden();
        $this->getJson('/dashboard')->assertForbidden();
    }

    public function test_health_check_is_never_challenged(): void
    {
        $this->enable();

        $this->get('/up')->assertOk();
    }

    public function test_solving_the_challenge_gives_a_pass_that_lets_the_visitor_in(): void
    {
        $this->enable();

        $token = $this->tokenFromPage($this->get('/login')->getContent());

        $response = $this->post('/_jsc/verify', ['token' => $token, 'nonce' => $this->solve($token)])
            ->assertOk()
            ->assertJson(['ok' => true])
            ->assertCookie('__jsc', null, false);

        $pass = $response->getCookie('__jsc', false)->getValue();

        $this->withUnencryptedCookie('__jsc', $pass)
            ->get('/login')
            ->assertOk()
            ->assertSee('Confiar neste dispositivo');
    }

    public function test_wrong_nonce_is_rejected(): void
    {
        $this->enable();

        $token = $this->tokenFromPage($this->get('/login')->getContent());

        // Acha um nonce que NÃO resolve o desafio.
        $wrong = '0';
        while (JsChallenge::verifyProof($token, $wrong)) {
            $wrong = (string) ((int) $wrong + 1);
        }

        $this->post('/_jsc/verify', ['token' => $token, 'nonce' => $wrong])
            ->assertForbidden()
            ->assertCookieMissing('__jsc');
    }

    public function test_trusted_device_skips_the_challenge_until_logout(): void
    {
        $user = User::factory()->create(['email' => 'rafa@example.com']);

        // Login com a caixa marcada (desafio desligado só para o login).
        $login = $this->post('/login', [
            'email' => 'rafa@example.com',
            'password' => 'password',
            'trust_device' => '1',
        ])->assertRedirect();

        $this->assertSame(1, JsChallengeDevice::where('user_id', (string) $user->id)->count());
        $trusted = $login->getCookie(TrustedDevices::cookieName(), false)->getValue();

        // Sem o passe de 12h, só com o cookie do dispositivo: entra direto.
        $this->enable();
        // (Já logado, /login redireciona ao painel: o que importa é não ser 503.)
        $this->withUnencryptedCookie(TrustedDevices::cookieName(), $trusted)
            ->get('/login')
            ->assertRedirect(route('dashboard'));

        // Logout revoga o dispositivo.
        $this->actingAs($user)
            ->withUnencryptedCookie(TrustedDevices::cookieName(), $trusted)
            ->post('/logout');

        $this->assertSame(0, JsChallengeDevice::count());

        $this->flushHeaders();
        $this->withUnencryptedCookie(TrustedDevices::cookieName(), $trusted)
            ->get('/login')
            ->assertStatus(503);
    }

    public function test_login_without_the_box_does_not_trust_the_device(): void
    {
        User::factory()->create(['email' => 'rafa@example.com']);

        $this->post('/login', ['email' => 'rafa@example.com', 'password' => 'password'])
            ->assertRedirect();

        $this->assertSame(0, JsChallengeDevice::count());
    }

    public function test_password_change_by_admin_revokes_the_users_devices(): void
    {
        $user = User::factory()->create(['email' => 'rafa@example.com']);

        $this->post('/login', [
            'email' => 'rafa@example.com',
            'password' => 'password',
            'trust_device' => '1',
        ]);
        $this->assertSame(1, JsChallengeDevice::count());

        $user->update(['password' => 'outra-senha-forte']);

        $this->assertSame(0, JsChallengeDevice::count());
    }
}
