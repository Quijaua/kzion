<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
use App\Support\JsChallengeSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            abort_unless($request->user()?->isAdmin(), 403);

            return $next($request);
        });
    }

    public function edit(): View
    {
        return view('settings.edit', [
            'registrationEnabled' => Setting::registrationEnabled(),
            // Admins não entram na lista: eles já veem tudo, e uma chave que
            // não faz nada só confunde quem está olhando a tela.
            'commonUsers' => User::where('role', '!=', 'admin')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'registration_enabled' => ['required', 'boolean'],
            'all_people_access' => ['array'],
            'all_people_access.*' => ['boolean'],
        ]);

        Setting::put(Setting::REGISTRATION_ENABLED, (bool) $data['registration_enabled']);

        // As chaves vêm do formulário, então nunca são usadas direto: o
        // whereIn + o filtro de papel garantem que só usuários comuns
        // existentes sejam tocados, mesmo com ids inventados no POST.
        $flags = $data['all_people_access'] ?? [];

        if ($flags) {
            User::where('role', '!=', 'admin')
                ->whereIn('id', array_keys($flags))
                ->get()
                ->each(fn (User $u) => $u->update([
                    'all_people_access' => (bool) $flags[$u->id],
                ]));
        }

        return redirect()->route('settings.edit')->with('status', 'Configurações salvas.');
    }

    /** Aba "Desafio JS": valores efetivos, com o padrão do .env ao lado. */
    public function jsChallenge(Request $request): View
    {
        return view('settings.jschallenge', [
            'values' => JsChallengeSettings::values(),
            'defaults' => JsChallengeSettings::defaults(),
            'customized' => JsChallengeSettings::saved() !== [],
            'asnAvailable' => JsChallengeSettings::asnAvailable(),
            'myIp' => $request->ip(),
        ]);
    }

    public function updateJsChallenge(Request $request): RedirectResponse
    {
        if ($request->input('action') === 'reset') {
            JsChallengeSettings::reset();

            return redirect()->route('settings.jschallenge')->with('status', 'Desafio JS de volta aos padrões do .env.');
        }

        $data = $request->validate([
            'enabled' => ['required', 'boolean'],
            // Acima de 22 o desafio passa de ~15 s num celular: na prática trava
            // o site. Abaixo de 8 não segura bot nenhum.
            'difficulty' => ['required', 'integer', 'between:8,22'],
            'pass_ttl_hours' => ['required', 'integer', 'between:1,720'],
            'except_ips' => ['nullable', 'string', 'max:5000', function ($attribute, $value, $fail) {
                $invalid = array_filter(
                    JsChallengeSettings::parseIps($value),
                    fn ($ip) => ! JsChallengeSettings::isIpOrCidr($ip)
                );

                if ($invalid) {
                    $fail('Endereço inválido: '.implode(', ', array_slice($invalid, 0, 5)).'.');
                }
            }],
            'trusted_enabled' => ['required', 'boolean'],
            'trusted_days' => ['required', 'integer', 'between:1,365'],
            'trusted_network' => ['required', 'in:prefix,asn,none'],
            'trusted_max_ips' => ['required', 'integer', 'between:0,100'],
        ], [
            'difficulty.between' => 'A dificuldade vai de 8 a 22.',
            'pass_ttl_hours.between' => 'A validade vai de 1 a 720 horas (30 dias).',
            'trusted_days.between' => 'De 1 a 365 dias.',
            'trusted_max_ips.between' => 'De 0 (desliga) a 100.',
        ]);

        JsChallengeSettings::save([
            'enabled' => (bool) $data['enabled'],
            'difficulty' => (int) $data['difficulty'],
            'pass_ttl' => (int) $data['pass_ttl_hours'] * 3600,
            'except_ips' => JsChallengeSettings::parseIps($data['except_ips'] ?? ''),
            'trusted_enabled' => (bool) $data['trusted_enabled'],
            'trusted_days' => (int) $data['trusted_days'],
            'trusted_network' => $data['trusted_network'],
            'trusted_max_ips' => (int) $data['trusted_max_ips'],
        ]);

        return redirect()->route('settings.jschallenge')->with('status', 'Configurações do desafio JS salvas.');
    }
}
