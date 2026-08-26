<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'role', 'active', 'all_people_access'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'all_people_access' => 'boolean',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    /** @return BelongsToMany<Person> */
    public function people(): BelongsToMany
    {
        return $this->belongsToMany(Person::class, 'user_people');
    }

    /*
     * -------------------------------------------------------------------
     * Duas listas de pessoas, de propósito
     *
     * Elas eram a mesma coisa e foram separadas em 2026-08-25, quando o
     * "acesso à lista completa de pessoas" entrou em Configurações:
     *
     *  - VISÍVEIS  — quem ele pode listar em /people e marcar numa foto.
     *                A chave `all_people_access` amplia ESTA lista.
     *  - DE ACESSO — quem lhe dá direito de ver fotos. Continua sendo só o
     *                pivô `user_people`; a chave NÃO mexe aqui.
     *
     * Trocar uma pela outra por engano é justamente o acidente que a
     * separação existe para evitar: usar a lista de visíveis no filtro das
     * fotos entregaria o acervo inteiro a quem só deveria poder marcar nomes.
     * -------------------------------------------------------------------
     */

    /** Vê todos os nomes do acervo (para listar e marcar, não para ver fotos). */
    public function seesAllPeople(): bool
    {
        return $this->isAdmin() || (bool) $this->all_people_access;
    }

    /** Pessoas que ele pode listar e marcar em fotos. */
    public function visiblePeople(): Builder
    {
        if ($this->seesAllPeople()) {
            return Person::query();
        }

        return Person::whereIn('id', $this->grantedPersonIds());
    }

    /** @return int[] */
    public function visiblePersonIds(): array
    {
        return $this->seesAllPeople()
            ? array_map('intval', Person::pluck('id')->all())
            : $this->grantedPersonIds();
    }

    /** Ele pode abrir a página desta pessoa? */
    public function canSeePerson(Person|int $person): bool
    {
        $id = (int) ($person instanceof Person ? $person->id : $person);

        return $this->seesAllPeople() || in_array($id, $this->grantedPersonIds(), true);
    }

    /**
     * Pessoas cujo vínculo dá acesso às FOTOS delas — só o pivô `user_people`.
     *
     * Não confundir com visiblePersonIds(): esta é a lista que o
     * `Photo::scopeAccessibleTo()` consulta, e `all_people_access` não a
     * amplia.
     *
     * @return int[]
     */
    public function photoAccessPersonIds(): array
    {
        if ($this->isAdmin()) {
            return array_map('intval', Person::pluck('id')->all());
        }

        return $this->grantedPersonIds();
    }

    /** @return int[] */
    protected function grantedPersonIds(): array
    {
        return array_map('intval', $this->people()->pluck('person_id')->all());
    }
}
