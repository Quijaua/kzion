<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Photo extends Model
{
    use HasFactory;

    protected $fillable = [
        'filename',
        'original_filename',
        'storage_path',
        'mime_type',
        'file_size',
        'width',
        'height',
        'title',
        'description',
        'taken_at',
        'location_id',
        'uploaded_by',
        'upload_token',
        'checksum',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'taken_at' => 'datetime',
        ];
    }

    /** @return BelongsToMany<Person> */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'photo_people');
    }

    /** @return BelongsToMany<Album> */
    public function albums(): BelongsToMany
    {
        return $this->belongsToMany(Album::class, 'album_photos');
    }

    /** Local da foto (cadastro em /locations). */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function exif(): HasOne
    {
        return $this->hasOne(PhotoExif::class);
    }

    /** Quem enviou a foto. Nulo em fotos antigas ou de usuário já excluído. */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Restrict to photos the given user may see.
     *
     * São dois caminhos, e basta um deles:
     *
     *  - **pessoas**: a regra é PERMISSIVA — basta o usuário ter acesso a UMA
     *    das pessoas marcadas. Uma foto com Pedro e Ana aparece para quem só
     *    tem permissão do Pedro; o que ele não pode é navegar pela Ana
     *    (`/people/{ana}` continua bloqueado e o nome dela não vira link);
     *  - **dono**: quem enviou a foto sempre a vê, mesmo sem ninguém marcado
     *    nela e mesmo sem nenhuma pessoa concedida. É o que faz o próprio
     *    upload aparecer para quem acabou de fazê-lo.
     *
     * Fora isso, uma foto sem ninguém marcado fica restrita ao dono e aos
     * administradores — é assim que o upload em lote as cria, e sem essa
     * restrição elas vazariam para todos os usuários.
     *
     * Admins veem tudo.
     */
    public function scopeAccessibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        // O `where(function () {...})` deixa o OU explícito no código. O
        // Eloquent já aninha sozinho as condições de um scope local
        // (`callScope()` chama `addNewWheresWithinGroup`), justamente para o
        // `orWhere` não escapar e anular os filtros de busca combinados com
        // ele — mas isso depende de a regra continuar sendo invocada como
        // scope. Agrupando aqui, ela continua correta mesmo se alguém um dia
        // copiar estas condições para dentro de uma query.
        return $query->where(function (Builder $q) use ($user) {
            $q->whereHas(
                'people',
                fn (Builder $sub) => $sub->whereIn('person_id', $user->photoAccessPersonIds())
            )->orWhere('photos.uploaded_by', $user->id);
        });
    }
}
