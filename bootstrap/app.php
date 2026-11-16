<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Desafio JS ("Verificando seu navegador…"): primeiro da pilha, para
        // barrar bots antes de sessão, cookies e banco. Ver config/jschallenge.php.
        $middleware->prepend(\App\Http\Middleware\JsChallengeGate::class);

        // O nginx fala direto com o PHP-FPM, então o IP do cliente já chega
        // certo em REMOTE_ADDR. Se um dia houver proxy reverso ou Cloudflare na
        // frente, descomente com a faixa do proxy — sem isso, o desafio e os
        // dispositivos confiáveis passam a enxergar o IP do proxy.
        // $middleware->trustProxies(at: ['10.0.0.0/8', '172.16.0.0/12', '192.168.0.0/16']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
