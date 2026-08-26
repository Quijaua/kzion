<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\User;
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
}
