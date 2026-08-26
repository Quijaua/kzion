<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Configurações da aplicação, em pares chave/valor.
 *
 * Vale para o que o administrador precisa mudar pela tela — o que é de
 * ambiente (banco, e-mail, storage) continua no `.env`.
 *
 * A leitura é uma consulta por chamada, sem cache nem memo estático: são
 * poucas linhas, indexadas por `key`, e só as telas de login, cadastro e
 * configurações consultam. Guardar o valor num estático economizaria quase
 * nada e traria estado que sobrevive entre requisições sob Octane e entre
 * testes no mesmo processo — exatamente o tipo de valor velho que causa "salvei
 * e não mudou nada".
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    /** Permitir que visitantes criem a própria conta em /register. */
    public const REGISTRATION_ENABLED = 'registration_enabled';

    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->where('key', $key)->value('value') ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = static::get($key);

        return $value === null ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public static function put(string $key, string|bool|null $value): void
    {
        if (is_bool($value)) {
            $value = $value ? '1' : '0';
        }

        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Visitantes podem criar a própria conta?
     *
     * O padrão é `true` para não mudar o comportamento de quem já tinha o
     * cadastro aberto antes desta tela existir.
     */
    public static function registrationEnabled(): bool
    {
        return static::bool(static::REGISTRATION_ENABLED, true);
    }
}
