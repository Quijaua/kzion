<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    /** @return HasMany<Photo> */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class);
    }

    /** Fotos deste local que o usuário pode ver (mesma regra do resto do acervo). */
    public function accessiblePhotos(User $user): HasMany
    {
        return $this->photos()->accessibleTo($user);
    }

    /**
     * Pode renomear ou excluir este local?
     *
     * Um local é compartilhado por fotos de vários donos. Renomear ou excluir
     * mexe em todas elas — inclusive nas que o usuário não enxerga. Por isso só
     * o admin, ou quem vê TODAS as fotos ligadas ao local, pode alterá-lo.
     * Local sem nenhuma foto é livre para qualquer um.
     */
    public function canBeManagedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $this->photos()->count() === $this->accessiblePhotos($user)->count();
    }
}
