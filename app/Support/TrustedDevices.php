<?php

namespace App\Support;

use App\Models\JsChallengeDevice;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * 3ª camada: passe de longa duração para usuários autenticados.
 *
 * Cookie: "{id}.{segredo}" (padrão seletor/validador).
 * O banco guarda só sha256(segredo); vazar a tabela não permite forjar cookies.
 */
class TrustedDevices
{
    public const OK = 'ok';            // dispositivo válido, mesma rede
    public const NETWORK = 'network';  // dispositivo válido, rede diferente → refazer desafio
    public const INVALID = 'invalid';  // sem cookie, adulterado, vencido ou revogado

    public static function enabled(): bool
    {
        return (bool) config('jschallenge.trusted.enabled', false);
    }

    public static function cookieName(): string
    {
        return (string) config('jschallenge.trusted.cookie', '__jsc_t');
    }

    // ------------------------------------------------------------------
    // Emissão
    // ------------------------------------------------------------------

    public static function issue(Request $request, Authenticatable $user): Cookie
    {
        $id = bin2hex(random_bytes(12));
        $secret = bin2hex(random_bytes(32));
        $expires = now()->addDays((int) config('jschallenge.trusted.ttl_days', 30));

        JsChallengeDevice::create([
            'id' => $id,
            'user_id' => (string) $user->getAuthIdentifier(),
            'secret_hash' => hash('sha256', $secret),
            'network' => self::networkKey($request),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'last_ip' => $request->ip(),
            'last_seen_at' => now(),
            'expires_at' => $expires,
        ]);

        return self::makeCookie($request, "{$id}.{$secret}", $expires->getTimestamp());
    }

    public static function forgetCookie(Request $request): Cookie
    {
        return self::makeCookie($request, '', 1);
    }

    private static function makeCookie(Request $request, string $value, int $expire): Cookie
    {
        return Cookie::create(
            name: self::cookieName(),
            value: $value,
            expire: $expire,
            path: '/',
            domain: null,
            secure: $request->isSecure(),
            httpOnly: true,
            raw: true,
            sameSite: Cookie::SAMESITE_LAX,
        );
    }

    // ------------------------------------------------------------------
    // Verificação (chamada pelo middleware)
    // ------------------------------------------------------------------

    /** @return string self::OK | self::NETWORK | self::INVALID */
    public static function check(Request $request): string
    {
        [$status, $device] = self::resolve($request);

        if ($status === self::INVALID) {
            return $status;
        }

        if (self::tooManyIps($device['id'], (string) $request->ip())) {
            self::revoke($device['id']);

            return self::INVALID;
        }

        if ($status === self::OK) {
            self::touch($device, $request);
        }

        return $status;
    }

    /**
     * Depois que o usuário resolve o desafio numa rede nova, passa a aceitar
     * o dispositivo nessa rede (chamado pelo JsChallengeController).
     */
    public static function rebind(Request $request): void
    {
        [$status, $device] = self::resolve($request);

        if ($status !== self::NETWORK) {
            return;
        }

        JsChallengeDevice::whereKey($device['id'])->update([
            'network' => self::networkKey($request),
            'last_ip' => $request->ip(),
            'last_seen_at' => now(),
        ]);

        Cache::forget(self::cacheKey($device['id']));
    }

    /**
     * Dispositivo do cookie atual, se for válido (ignora a rede).
     *
     * @return array{id:string,user_id:string,network:?string,expires_at:int,last_seen_at:int}|null
     */
    public static function current(Request $request): ?array
    {
        [$status, $device] = self::resolve($request);

        return $status === self::INVALID ? null : $device;
    }

    /** @return array{0:string,1:?array} */
    private static function resolve(Request $request): array
    {
        $raw = $request->cookies->get(self::cookieName());

        if (! is_string($raw) || ! preg_match('/^([0-9a-f]{24})\.([0-9a-f]{64})$/', $raw, $m)) {
            return [self::INVALID, null];
        }

        [, $id, $secret] = $m;

        $device = Cache::remember(
            self::cacheKey($id),
            (int) config('jschallenge.trusted.cache_seconds', 60),
            function () use ($id) {
                $d = JsChallengeDevice::find($id);

                // false (e não null) para que "não existe" também fique em cache
                return $d ? [
                    'id' => $d->id,
                    'user_id' => $d->user_id,
                    'secret_hash' => $d->secret_hash,
                    'network' => $d->network,
                    'expires_at' => $d->expires_at->getTimestamp(),
                    'last_seen_at' => $d->last_seen_at?->getTimestamp() ?? 0,
                ] : false;
            }
        );

        if (! $device
            || $device['expires_at'] < time()
            || ! hash_equals($device['secret_hash'], hash('sha256', $secret))) {
            return [self::INVALID, null];
        }

        unset($device['secret_hash']);

        $mode = config('jschallenge.trusted.network', 'prefix');

        if ($mode !== 'none' && $device['network'] !== self::networkKey($request)) {
            return [self::NETWORK, $device];
        }

        return [self::OK, $device];
    }

    /** Atualiza "visto por último" no máximo uma vez por hora (evita escrita a cada request). */
    private static function touch(array $device, Request $request): void
    {
        if (time() - $device['last_seen_at'] < 3600) {
            return;
        }

        JsChallengeDevice::whereKey($device['id'])->update([
            'last_ip' => $request->ip(),
            'last_seen_at' => now(),
        ]);

        Cache::forget(self::cacheKey($device['id']));
    }

    /**
     * Conta redes distintas (IPv4 exato / IPv6 /64) que usaram o cookie na hora corrente.
     */
    private static function tooManyIps(string $id, string $ip): bool
    {
        $max = (int) config('jschallenge.trusted.max_ips_per_hour', 0);

        if ($max <= 0 || $ip === '') {
            return false;
        }

        $key = 'jsc:dev:' . $id . ':ips:' . date('YmdH');
        $seen = Cache::get($key, []);
        $net = self::maskIp($ip, 32, 64);

        if (! in_array($net, $seen, true)) {
            $seen[] = $net;
            Cache::put($key, $seen, 3700);
        }

        return count($seen) > $max;
    }

    // ------------------------------------------------------------------
    // Revogação e consulta
    // ------------------------------------------------------------------

    public static function revoke(string $id): void
    {
        JsChallengeDevice::whereKey($id)->delete();
        Cache::forget(self::cacheKey($id));
    }

    public static function revokeCurrent(Request $request): void
    {
        if ($device = self::current($request)) {
            self::revoke($device['id']);
        }
    }

    public static function revokeAllFor(Authenticatable|string|int $user, ?string $exceptId = null): int
    {
        $userId = $user instanceof Authenticatable ? (string) $user->getAuthIdentifier() : (string) $user;

        $ids = JsChallengeDevice::where('user_id', $userId)
            ->when($exceptId, fn ($q) => $q->whereKeyNot($exceptId))
            ->pluck('id');

        foreach ($ids as $id) {
            self::revoke($id);
        }

        return $ids->count();
    }

    /** Para montar uma tela "Meus dispositivos confiáveis". */
    public static function listFor(Authenticatable $user): Collection
    {
        return JsChallengeDevice::where('user_id', (string) $user->getAuthIdentifier())
            ->where('expires_at', '>', now())
            ->orderByDesc('last_seen_at')
            ->get(['id', 'user_agent', 'last_ip', 'last_seen_at', 'expires_at', 'created_at']);
    }

    // ------------------------------------------------------------------
    // Rede
    // ------------------------------------------------------------------

    public static function networkKey(Request $request): string
    {
        $ip = (string) $request->ip();
        $mode = config('jschallenge.trusted.network', 'prefix');

        if ($mode === 'asn' && ($asn = self::asn($ip)) !== null) {
            return 'asn:' . $asn;
        }

        return 'net:' . self::maskIp(
            $ip,
            (int) config('jschallenge.trusted.ipv4_prefix', 16),
            (int) config('jschallenge.trusted.ipv6_prefix', 48),
        );
    }

    /** "200.150.12.34" com /16 → "200.150.0.0/16"; IPv6 com /48 → "2804:14c:5b::/48". */
    public static function maskIp(string $ip, int $v4bits, int $v6bits): string
    {
        $bin = @inet_pton($ip);

        if ($bin === false) {
            return 'invalid';
        }

        $bits = strlen($bin) === 4 ? $v4bits : $v6bits;
        $bits = max(0, min($bits, strlen($bin) * 8));
        $out = '';

        for ($i = 0, $n = strlen($bin); $i < $n; $i++) {
            $keep = max(0, min(8, $bits - $i * 8));
            $mask = $keep === 0 ? 0 : (0xFF << (8 - $keep)) & 0xFF;
            $out .= chr(ord($bin[$i]) & $mask);
        }

        return inet_ntop($out) . '/' . $bits;
    }

    private static function asn(string $ip): ?int
    {
        static $reader = null;

        $path = (string) config('jschallenge.trusted.asn_db');

        if ($reader === null) {
            if (! class_exists(\MaxMind\Db\Reader::class) || ! is_file($path)) {
                $reader = false;
            } else {
                try {
                    $reader = new \MaxMind\Db\Reader($path);
                } catch (\Throwable) {
                    $reader = false;
                }
            }
        }

        if ($reader === false) {
            return null;
        }

        try {
            $record = $reader->get($ip);
        } catch (\Throwable) {
            return null;
        }

        return isset($record['autonomous_system_number']) ? (int) $record['autonomous_system_number'] : null;
    }

    private static function cacheKey(string $id): string
    {
        return 'jsc:dev:' . $id;
    }
}
