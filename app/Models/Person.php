<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Person extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'birth_date',
        'death_date',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'death_date' => 'date',
        ];
    }

    /** @return BelongsToMany<Photo> */
    public function photos(): BelongsToMany
    {
        return $this->belongsToMany(Photo::class, 'photo_people');
    }

    /** @return BelongsToMany<User> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_people');
    }

    public function photoCount(): int
    {
        return $this->photos()->count();
    }

    /** Photos of this person that a given user is allowed to see. */
    public function accessiblePhotos(User $user): BelongsToMany
    {
        return $this->photos()->accessibleTo($user);
    }
}
