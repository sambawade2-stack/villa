<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\BookingGuestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $booking_id
 * @property string $full_name
 * @property string|null $email
 * @property string|null $phone
 * @property bool $is_lead
 * @property string $age_group
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Booking|null $booking
 */
class BookingGuest extends Model
{
    /** @use HasFactory<BookingGuestFactory> */
    use HasFactory;

    protected $fillable = ['booking_id', 'full_name', 'email', 'phone', 'is_lead', 'age_group'];

    protected function casts(): array
    {
        return ['is_lead' => 'boolean'];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
