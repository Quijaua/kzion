<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Prunable;

/**
 * Dispositivo marcado como confiável após o login.
 * Registros vencidos são apagados por `php artisan model:prune`.
 */
class JsChallengeDevice extends Model
{
    use Prunable;

    protected $table = 'js_challenge_devices';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id', 'user_id', 'secret_hash', 'network',
        'user_agent', 'last_ip', 'last_seen_at', 'expires_at',
    ];

    protected $hidden = ['secret_hash'];

    protected $casts = [
        'last_seen_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function prunable(): Builder
    {
        return static::where('expires_at', '<', now());
    }
}
