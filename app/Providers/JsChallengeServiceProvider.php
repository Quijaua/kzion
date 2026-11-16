<?php

namespace App\Providers;

use App\Http\Controllers\JsChallengeController;
use App\Listeners\JsChallengeAuthSubscriber;
use App\Support\JsChallengeSettings;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class JsChallengeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // kzion: o que o admin salvou em /settings/jschallenge se sobrepõe ao
        // .env. Precisa vir antes de tudo abaixo — o subscriber só é registrado
        // se o dispositivo confiável estiver ligado.
        JsChallengeSettings::apply();

        // Os cookies do desafio já são assinados (HMAC / hash no banco) e são lidos
        // ANTES do EncryptCookies, então precisam ficar fora da criptografia do Laravel.
        // Laravel 11+ tem EncryptCookies::except() estático; no Laravel 10, veja o README.
        if (method_exists(EncryptCookies::class, 'except')) {
            EncryptCookies::except([
                config('jschallenge.cookie', '__jsc'),
                config('jschallenge.trusted.cookie', '__jsc_t'),
            ]);
        }

        // Login / logout / troca de senha → emite ou revoga o dispositivo confiável
        Event::subscribe(JsChallengeAuthSubscriber::class);

        // Rota de verificação FORA do grupo "web": sem sessão e sem CSRF
        if (! $this->app->routesAreCached()) {
            Route::post('_jsc/verify', [JsChallengeController::class, 'verify'])
                ->middleware('throttle:30,1')
                ->name('jschallenge.verify');
        }
    }
}
