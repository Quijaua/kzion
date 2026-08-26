<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhotoExif extends Model
{
    use HasFactory;

    protected $table = 'photo_exif';

    protected $fillable = [
        'photo_id',
        'make',
        'model',
        'lens',
        'iso',
        'aperture',
        'shutter_speed',
        'focal_length',
        'gps_latitude',
        'gps_longitude',
        'software',
        'exif_data_json',
    ];

    protected function casts(): array
    {
        return [
            'iso' => 'integer',
            'gps_latitude' => 'decimal:8',
            'gps_longitude' => 'decimal:8',
            'exif_data_json' => 'array',
        ];
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(Photo::class);
    }
}
