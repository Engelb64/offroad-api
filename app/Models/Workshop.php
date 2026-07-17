<?php

namespace App\Models;

use App\Enums\WorkshopStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Workshop extends Model
{
    use HasFactory;

    protected $hidden = [
        'location',
    ];

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'description',
        'phone',
        'email',
        'website',
        'address',
        'city',
        'state',
        'country',
        'latitude',
        'longitude',
        'services',
        'schedule',
        'status',
        'verified',
        'photo_path',
        'moderation_note',
        'moderation_at',
        'moderated_by',
    ];

    protected function casts(): array
    {
        return [
            'services' => 'array',
            'schedule' => 'array',
            'status' => WorkshopStatus::class,
            'verified' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'moderation_at' => 'datetime',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function moderatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function photos(): HasMany
    {
        return $this->hasMany(WorkshopPhoto::class)->orderBy('sort_order');
    }

    public function isOwnedBy(User $user): bool
    {
        return $this->owner_id !== null && (int) $this->owner_id === (int) $user->id;
    }

    protected static function booted(): void
    {
        static::saved(function (Workshop $workshop): void {
            $workshop->syncLocation();
        });
    }

    public function syncLocation(): void
    {
        if ($this->latitude === null || $this->longitude === null) {
            DB::update('UPDATE workshops SET location = NULL WHERE id = ?', [$this->id]);

            return;
        }

        DB::update(
            'UPDATE workshops
             SET location = ST_SetSRID(ST_MakePoint(?, ?), 4326)::geography
             WHERE id = ?',
            [
                (float) $this->longitude,
                (float) $this->latitude,
                $this->id,
            ],
        );
    }

    public static function uniqueSlugFromName(string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'taller';
        $slug = $base;
        $i = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i;
            $i++;
        }

        return $slug;
    }
}
