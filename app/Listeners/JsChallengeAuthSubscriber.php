<?php

namespace App\Listeners;

use App\Support\TrustedDevices;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\OtherDeviceLogout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Cookie;

/**
 * Liga o "dispositivo confiável" ao ciclo de autenticação do Laravel:
 *
 *  - Login                 → emite o passe de 30 dias (se a caixa foi marcada)
 *  - Logout                → revoga o dispositivo atual e apaga o cookie
 *  - logoutOtherDevices()  → revoga todos os outros dispositivos do usuário
 *  - Reset de senha        → revoga todos
 *  - Troca de senha        → revoga todos, menos o dispositivo de quem trocou
 *  - Usuário excluído      → revoga todos
 */
class JsChallengeAuthSubscriber
{
    public function onLogin(Login $event): void
    {
        $request = request();
        $input = (string) config('jschallenge.trusted.input', 'trust_device');

        if (! config('jschallenge.trusted.always') && ! $request->boolean($input)) {
            return;
        }

        // Já tem passe deste mesmo usuário (ex.: login via "remember me"): não duplica
        $current = TrustedDevices::current($request);

        if ($current && $current['user_id'] === (string) $event->user->getAuthIdentifier()) {
            return;
        }

        // Passe de outro usuário no mesmo navegador: descarta
        if ($current) {
            TrustedDevices::revoke($current['id']);
        }

        Cookie::queue(TrustedDevices::issue($request, $event->user));
    }

    public function onLogout(Logout|CurrentDeviceLogout $event): void
    {
        $request = request();

        TrustedDevices::revokeCurrent($request);

        if ($request->cookies->has(TrustedDevices::cookieName())) {
            Cookie::queue(TrustedDevices::forgetCookie($request));
        }
    }

    public function onOtherDeviceLogout(OtherDeviceLogout $event): void
    {
        $current = TrustedDevices::current(request());

        TrustedDevices::revokeAllFor($event->user, $current['id'] ?? null);
    }

    public function onPasswordReset(PasswordReset $event): void
    {
        TrustedDevices::revokeAllFor($event->user);
    }

    public function onUserUpdated(Authenticatable $user): void
    {
        $passwordColumn = method_exists($user, 'getAuthPasswordName') ? $user->getAuthPasswordName() : 'password';

        if (! method_exists($user, 'wasChanged') || ! $user->wasChanged($passwordColumn)) {
            return;
        }

        // Mantém o dispositivo de quem está trocando a própria senha
        $current = TrustedDevices::current(request());
        $keep = $current && $current['user_id'] === (string) $user->getAuthIdentifier() ? $current['id'] : null;

        TrustedDevices::revokeAllFor($user, $keep);
    }

    public function onUserDeleted(Authenticatable $user): void
    {
        TrustedDevices::revokeAllFor($user);
    }

    public function subscribe(Dispatcher $events): array
    {
        if (! TrustedDevices::enabled()) {
            return [];
        }

        $model = config('auth.providers.users.model', \App\Models\User::class);

        return [
            Login::class => 'onLogin',
            Logout::class => 'onLogout',
            CurrentDeviceLogout::class => 'onLogout',
            OtherDeviceLogout::class => 'onOtherDeviceLogout',
            PasswordReset::class => 'onPasswordReset',
            "eloquent.updated: {$model}" => 'onUserUpdated',
            "eloquent.deleted: {$model}" => 'onUserDeleted',
        ];
    }
}
