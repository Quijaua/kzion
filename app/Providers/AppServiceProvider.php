<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // O front-end é 100% Tabler (Bootstrap 5). Sem isto o Laravel renderiza
        // a paginação com as views padrão do Tailwind, que não existe mais aqui.
        Paginator::useBootstrapFive();

        // Se o site é publicado em https (APP_URL), todo link, asset e
        // formulário sai em https — mesmo quando o PHP-FPM não recebe o
        // `fastcgi_param HTTPS` do nginx (aconteceu com HTTP/3 no WordOps:
        // CSS e JS bloqueados como conteúdo misto e o login apontando para
        // http). Em dev, com APP_URL http, nada muda.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
