<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Barra /register quando o administrador desliga o cadastro em Configurações.
 *
 * Esconder o link em /login não basta: sem barrar a rota, qualquer um que
 * saiba o endereço continua criando conta. Devolve 404 em vez de 403 para não
 * anunciar que existe um cadastro desligado ali.
 */
class EnsureRegistrationIsEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(Setting::registrationEnabled(), 404);

        return $next($request);
    }
}
