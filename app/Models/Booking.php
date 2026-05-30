<?php

namespace App\Models;

use App\Enums\BookingStatus;
use Database\Factories\BookingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'tenant_id',
    'booking_number',
    'user_id',
    'room_id',
    'guest_name',
    'guest_phone',
    'guest_email',
    'guest_address',
    'room',
    'assigned_room_number',
    'nid_number',
    'room_quantity',
    'discount',
    'promo_code',
    'check_in',
    'check_out',
    'status',
])]
class Booking extends Model
{
    public const NID_IMAGE_CATEGORY = 'booking_nid_image';

    /** @use HasFactory<BookingFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The model's default values for attributes.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => BookingStatus::Pending->value,
    ];

    public static function numberForId(int $id): string
    {
        return 'BKG-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'check_in' => 'date',
            'check_out' => 'date',
            'room_id' => 'integer',
            'room_quantity' => 'integer',
            'status' => BookingStatus::class,
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bookedRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
