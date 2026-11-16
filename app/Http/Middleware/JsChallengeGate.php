<?php

namespace App\Http\Middleware;

use App\Support\JsChallenge;
use App\Support\TrustedDevices;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registre como middleware GLOBAL e no início da pilha (prepend), para rodar
 * antes de sessão, cookies criptografados e banco de dados: bots barrados
 * não custam quase nada ao servidor.
 */
class JsChallengeGate
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->shouldPass($request)) {
            return $next($request);
        }

        // POST/PUT/etc. sem passe: não dá para "reenviar" o formulário depois
        // do desafio, então só recusa.
        if (! $request->isMethodSafe() || $request->expectsJson()) {
            return response()
                ->json(['message' => 'JavaScript challenge required.'], 403)
                ->withHeaders($this->noStoreHeaders());
        }

        return response()
            ->view('jschallenge.challenge', [
                'token' => JsChallenge::issueToken($request),
                'difficulty' => (int) config('jschallenge.difficulty', 18),
                'minDelay' => (int) config('jschallenge.min_delay_ms', 0),
                'verifyUrl' => url('/_jsc/verify'),
            ], 503)
            ->withHeaders($this->noStoreHeaders() + [
                'Retry-After' => '30',
                'X-Robots-Tag' => 'noindex, nofollow',
            ]);
    }

    private function shouldPass(Request $request): bool
    {
        if (! config('jschallenge.enabled', true)) {
            return true;
        }

        if ($request->is(...config('jschallenge.except', []))) {
            return true;
        }

        $ip = (string) $request->ip();
        $ips = config('jschallenge.except_ips', []);

        if ($ips && IpUtils::checkIp($ip, $ips)) {
            return true;
        }

        if (JsChallenge::verifyPass($request, $request->cookies->get(config('jschallenge.cookie', '__jsc')))) {
            return true;
        }

        // 3ª camada: dispositivo confiável (só consulta o banco se o cookie existir).
        // Se a rede mudou, check() devolve NETWORK e o fluxo segue para o desafio.
        if (TrustedDevices::enabled()
            && $request->cookies->has(TrustedDevices::cookieName())
            && TrustedDevices::check($request) === TrustedDevices::OK) {
            return true;
        }

        $ua = (string) $request->userAgent();

        foreach (config('jschallenge.allow_user_agents', []) as $pattern) {
            if (preg_match($pattern, $ua)) {
                return true;
            }
        }

        foreach (config('jschallenge.verified_bots', []) as $pattern => $suffixes) {
            if (preg_match($pattern, $ua)) {
                return $this->isVerifiedCrawler($ip, $suffixes);
            }
        }

        return false;
    }

    /**
     * DNS reverso do IP -> hostname deve terminar num sufixo oficial,
     * e o DNS direto desse hostname deve voltar ao mesmo IP.
     * É o método documentado por Google e Bing.
     */
    private function isVerifiedCrawler(string $ip, array $suffixes): bool
    {
        return Cache::remember('jsc:bot:' . $ip, now()->addDay(), function () use ($ip, $suffixes) {
            $host = @gethostbyaddr($ip);

            if (! $host || $host === $ip) {
                return false;
            }

            $host = strtolower($host);
            $matches = false;

            foreach ($suffixes as $suffix) {
                if (str_ends_with($host, strtolower($suffix))) {
                    $matches = true;
                    break;
                }
            }

            if (! $matches) {
                return false;
            }

            $type = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? DNS_AAAA : DNS_A;
            $records = @dns_get_record($host, $type) ?: [];

            foreach ($records as $record) {
                $resolved = $record['ip'] ?? $record['ipv6'] ?? null;

                if ($resolved && inet_pton($resolved) === inet_pton($ip)) {
                    return true;
                }
            }

            return false;
        });
    }

    private function noStoreHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ];
    }
}
