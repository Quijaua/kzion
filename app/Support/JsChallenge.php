<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Núcleo do desafio: tokens assinados por HMAC (sem estado no servidor)
 * e verificação do proof-of-work.
 *
 *   Token do desafio: "{timestamp}.{aleatório}.{hmac}"
 *   Cookie de passe:  "{expira_em}.{hmac}"
 *
 * Os dois HMAC incluem o fingerprint do cliente (User-Agent e, opcionalmente, IP),
 * então copiar o cookie para outro cliente não funciona.
 *
 * O navegador precisa achar um "nonce" numérico tal que
 *   sha256("{token}:{nonce}") comece com N bits zero (N = jschallenge.difficulty).
 */
class JsChallenge
{
    /** Chave derivada da APP_KEY, exclusiva para este uso. */
    public static function key(): string
    {
        $appKey = (string) config('app.key');

        if (str_starts_with($appKey, 'base64:')) {
            $appKey = base64_decode(substr($appKey, 7));
        }

        return hash_hmac('sha256', 'jschallenge-v1', $appKey, true);
    }

    public static function fingerprint(Request $request): string
    {
        $parts = [(string) $request->userAgent()];

        if (config('jschallenge.bind_ip')) {
            $parts[] = (string) $request->ip();
        }

        return implode('|', $parts);
    }

    // ---------- Token do desafio ----------

    public static function issueToken(Request $request): string
    {
        $ts = (string) time();
        $rand = bin2hex(random_bytes(12));

        return "{$ts}.{$rand}." . self::signToken($request, $ts, $rand);
    }

    public static function verifyToken(Request $request, string $token): bool
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return false;
        }

        [$ts, $rand, $sig] = $parts;

        if (! ctype_digit($ts) || $rand === '' || ! ctype_xdigit($rand)) {
            return false;
        }

        $age = time() - (int) $ts;

        if ($age < 0 || $age > (int) config('jschallenge.token_ttl', 300)) {
            return false;
        }

        return hash_equals(self::signToken($request, $ts, $rand), $sig);
    }

    private static function signToken(Request $request, string $ts, string $rand): string
    {
        return hash_hmac('sha256', "token|{$ts}|{$rand}|" . self::fingerprint($request), self::key());
    }

    // ---------- Proof-of-work ----------

    public static function verifyProof(string $token, string $nonce): bool
    {
        if ($nonce === '' || strlen($nonce) > 12 || ! ctype_digit($nonce)) {
            return false;
        }

        $hash = hash('sha256', "{$token}:{$nonce}", true);

        return self::leadingZeroBits($hash) >= (int) config('jschallenge.difficulty', 18);
    }

    public static function leadingZeroBits(string $binary): int
    {
        $bits = 0;
        $len = strlen($binary);

        for ($i = 0; $i < $len; $i++) {
            $byte = ord($binary[$i]);

            if ($byte === 0) {
                $bits += 8;
                continue;
            }

            for ($mask = 0x80; ($byte & $mask) === 0; $mask >>= 1) {
                $bits++;
            }

            break;
        }

        return $bits;
    }

    // ---------- Cookie de passe ----------

    public static function issuePass(Request $request): string
    {
        $exp = (string) (time() + (int) config('jschallenge.pass_ttl', 43200));

        return "{$exp}." . self::signPass($request, $exp);
    }

    public static function verifyPass(Request $request, ?string $value): bool
    {
        if (! is_string($value) || substr_count($value, '.') !== 1) {
            return false;
        }

        [$exp, $sig] = explode('.', $value);

        if (! ctype_digit($exp) || (int) $exp < time()) {
            return false;
        }

        return hash_equals(self::signPass($request, $exp), $sig);
    }

    private static function signPass(Request $request, string $exp): string
    {
        return hash_hmac('sha256', "pass|{$exp}|" . self::fingerprint($request), self::key());
    }
}
