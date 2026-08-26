<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
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
    }
}
