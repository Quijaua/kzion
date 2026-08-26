<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Album extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'cover_photo_id',
    ];

    protected function casts(): array
    {
        return [
            'cover_photo_id' => 'integer',
        ];
    }

    /** @return BelongsToMany<Photo> */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class, 'album_photos');
    }

    /** Foto usada como capa na listagem de álbuns. */
    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(Photo::class, 'cover_photo_id');
    }
}
