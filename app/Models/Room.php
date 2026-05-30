<?php

namespace App\Models;

use App\Observers\RoomObserver;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['room_name', 'tenant_id', 'room_type', 'capacity', 'rate', 'available_rooms', 'status', 'amenities', 'description'])]
#[ObservedBy([RoomObserver::class])]
class Room extends Model
{
    public const IMAGE_CATEGORY = 'room_image';

    /** @use HasFactory<\Database\Factories\RoomFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'capacity' => 'integer',
            'rate' => 'decimal:2',
            'available_rooms' => 'integer',
            'amenities' => 'array',
        ];
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
