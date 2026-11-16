<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Configurações do desafio JS editáveis em /settings/jschallenge.
 *
 * O `.env` (via config/jschallenge.php) é o PADRÃO; o que o admin salva na
 * tela fica na tabela `settings` (chaves "jschallenge.*") e se sobrepõe a ele
 * em tempo de execução. "Restaurar padrões" apaga as chaves e volta ao `.env`.
 *
 * Exceção de propósito: JSCHALLENGE_ENABLED=false no `.env` é a chave de
 * EMERGÊNCIA e vence a tela. Se uma configuração errada travar todo mundo do
 * lado de fora (inclusive o admin), desligar no `.env` + config:clear resolve
 * sem mexer no banco.
 *
 * As chaves salvas ficam em cache (invalidado ao salvar), porque são lidas em
 * TODA requisição, antes do middleware do desafio — inclusive as de bots.
 */
class JsChallengeSettings
{
    private const CACHE_KEY = 'settings.jschallenge';

    private const PREFIX = 'jschallenge.';

    /** campo da tela => chave de config */
    public const MAP = [
        'enabled' => 'jschallenge.enabled',
        'difficulty' => 'jschallenge.difficulty',
        'pass_ttl' => 'jschallenge.pass_ttl',
        'bind_ip' => 'jschallenge.bind_ip',
        'except_ips' => 'jschallenge.except_ips',
        'trusted_enabled' => 'jschallenge.trusted.enabled',
        'trusted_days' => 'jschallenge.trusted.ttl_days',
        'trusted_network' => 'jschallenge.trusted.network',
        'trusted_max_ips' => 'jschallenge.trusted.max_ips_per_hour',
    ];

    private const BOOLS = ['enabled', 'bind_ip', 'trusted_enabled'];

    private const INTS = ['difficulty', 'pass_ttl', 'trusted_days', 'trusted_max_ips'];

    /** Aplica .env + valores salvos à config. Chamado no boot e depois de salvar. */
    public static function apply(): void
    {
        $defaults = self::defaults();
        $saved = self::saved();

        foreach (self::MAP as $field => $key) {
            $value = array_key_exists($field, $saved) ? $saved[$field] : $defaults[$field];

            if ($field === 'enabled') {
                $value = $defaults['enabled'] && $value; // .env desligado vence
            }

            config([$key => $value]);
        }
    }

    /**
     * Valores do `.env`. Fotografados na primeira chamada, antes de qualquer
     * sobreposição, e guardados na própria config.
     */
    public static function defaults(): array
    {
        if (! config()->has('jschallenge._env')) {
            config(['jschallenge._env' => array_map(fn ($key) => config($key), self::MAP)]);
        }

        return config('jschallenge._env');
    }

    /** O que está salvo na tela (só os campos que o admin já salvou), já tipado. */
    public static function saved(): array
    {
        try {
            $raw = Cache::rememberForever(self::CACHE_KEY, fn () => Setting::query()
                ->where('key', 'like', self::PREFIX.'%')
                ->pluck('value', 'key')
                ->all());
        } catch (\Throwable) {
            return []; // banco ainda sem a tabela (instalação nova) ou fora do ar
        }

        $out = [];

        foreach ($raw as $key => $value) {
            $field = substr($key, strlen(self::PREFIX));

            if (array_key_exists($field, self::MAP)) {
                $out[$field] = self::cast($field, $value);
            }
        }

        return $out;
    }

    /** Valores que a tela deve mostrar: salvos por cima do `.env`. */
    public static function values(): array
    {
        return array_merge(self::defaults(), self::saved());
    }

    public static function save(array $values): void
    {
        foreach (self::MAP as $field => $_) {
            if (! array_key_exists($field, $values)) {
                continue;
            }

            $value = $values[$field];
            Setting::put(self::PREFIX.$field, match (true) {
                $field === 'except_ips' => json_encode(array_values($value)),
                is_bool($value) => $value,
                default => (string) $value,
            });
        }

        Cache::forget(self::CACHE_KEY);
        self::apply();
    }

    public static function reset(): void
    {
        Setting::query()->where('key', 'like', self::PREFIX.'%')->delete();
        Cache::forget(self::CACHE_KEY);
        self::apply();
    }

    /** "200.1.2.3, 10.0.0.0/8\n2804::/32" → lista. */
    public static function parseIps(?string $text): array
    {
        return array_values(array_unique(array_filter(
            preg_split('/[\s,;]+/', (string) $text) ?: [],
            fn ($v) => $v !== ''
        )));
    }

    /** IP ou CIDR válido (IPv4 ou IPv6)? */
    public static function isIpOrCidr(string $value): bool
    {
        [$ip, $bits] = array_pad(explode('/', $value, 2), 2, null);

        $flags = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 32
            : (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? 128 : 0);

        if ($flags === 0) {
            return false;
        }

        if ($bits === null) {
            return true;
        }

        return ctype_digit($bits) && (int) $bits <= $flags && IpUtils::checkIp($ip, $value);
    }

    /** A base de ASN (MaxMind) está disponível para o modo "operadora"? */
    public static function asnAvailable(): bool
    {
        return class_exists(\MaxMind\Db\Reader::class)
            && is_file((string) config('jschallenge.trusted.asn_db'));
    }

    private static function cast(string $field, ?string $value): mixed
    {
        return match (true) {
            in_array($field, self::BOOLS, true) => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            in_array($field, self::INTS, true) => (int) $value,
            $field === 'except_ips' => array_values(array_filter((array) json_decode((string) $value, true), 'is_string')),
            default => (string) $value,
        };
    }
}
