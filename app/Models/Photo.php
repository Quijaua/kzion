<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        'taken_at_precision',
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

    /*
     * Precisão da data da foto (`taken_at_precision`): quanto da data se sabe.
     * A data é guardada sempre completa — 'year' como AAAA-01-01 e 'month' como
     * AAAA-MM-01 — e a precisão diz quais partes são reais. Fotos antigas sem
     * precisão (null) contam como 'day'.
     */
    public const PRECISION_YEAR = 'year';

    public const PRECISION_MONTH = 'month';

    public const PRECISION_DAY = 'day';

    public const MONTHS = [
        1 => 'janeiro', 2 => 'fevereiro', 3 => 'março', 4 => 'abril', 5 => 'maio', 6 => 'junho',
        7 => 'julho', 8 => 'agosto', 9 => 'setembro', 10 => 'outubro', 11 => 'novembro', 12 => 'dezembro',
    ];

    /**
     * Monta data + precisão a partir do que se sabe (ano; ano e mês; ou
     * dia/mês/ano). Sem ano → sem data.
     *
     * @return array{0: ?Carbon, 1: ?string}
     */
    public static function dateFromParts(?int $year, ?int $month = null, ?int $day = null): array
    {
        if (! $year) {
            return [null, null];
        }

        if (! $month) {
            return [Carbon::create($year, 1, 1), self::PRECISION_YEAR];
        }

        if (! $day) {
            return [Carbon::create($year, $month, 1), self::PRECISION_MONTH];
        }

        return [Carbon::create($year, $month, $day), self::PRECISION_DAY];
    }

    public function datePrecision(): ?string
    {
        if ($this->taken_at === null) {
            return null;
        }

        return $this->taken_at_precision ?? self::PRECISION_DAY;
    }

    /** "1987", "março de 1987" ou "12/03/1987", conforme a precisão. */
    public function getTakenAtLabelAttribute(): ?string
    {
        return match ($this->datePrecision()) {
            null => null,
            self::PRECISION_YEAR => $this->taken_at->format('Y'),
            self::PRECISION_MONTH => self::MONTHS[(int) $this->taken_at->format('n')].' de '.$this->taken_at->format('Y'),
            default => $this->taken_at->format('d/m/Y'),
        };
    }

    /** Hora do EXIF, quando a data é completa e a hora é conhecida. */
    public function getTakenAtTimeAttribute(): ?string
    {
        if ($this->datePrecision() !== self::PRECISION_DAY || $this->taken_at->format('H:i:s') === '00:00:00') {
            return null;
        }

        return $this->taken_at->format('H:i');
    }

    /** Ano / mês / dia para os campos do formulário (null no que não se sabe). */
    public function takenAtParts(): array
    {
        $p = $this->datePrecision();

        return [
            'year' => $p ? (int) $this->taken_at->format('Y') : null,
            'month' => in_array($p, [self::PRECISION_MONTH, self::PRECISION_DAY], true) ? (int) $this->taken_at->format('n') : null,
            'day' => $p === self::PRECISION_DAY ? (int) $this->taken_at->format('j') : null,
        ];
    }

    /** Fotos de um ano. */
    public function scopeTakenInYear(Builder $query, int $year): Builder
    {
        return $query->whereYear('taken_at', $year);
    }

    /**
     * Fotos de um mês do ano (só as que têm o mês conhecido — uma foto "só de
     * 1987" está guardada em janeiro, mas não é de janeiro).
     */
    public function scopeTakenInMonth(Builder $query, int $month): Builder
    {
        return $query->whereMonth('taken_at', $month)->whereKnownMonth();
    }

    public function scopeWhereKnownMonth(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q
            ->whereNull('taken_at_precision')
            ->orWhere('taken_at_precision', '!=', self::PRECISION_YEAR));
    }

    /** Expressões SQL de ano e mês de `taken_at`, por banco (para agrupar). */
    public static function yearSql(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%Y', taken_at) AS INTEGER)",
            'pgsql' => 'CAST(EXTRACT(YEAR FROM taken_at) AS INTEGER)',
            default => 'YEAR(taken_at)',
        };
    }

    public static function monthSql(): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "CAST(strftime('%m', taken_at) AS INTEGER)",
            'pgsql' => 'CAST(EXTRACT(MONTH FROM taken_at) AS INTEGER)',
            default => 'MONTH(taken_at)',
        };
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
