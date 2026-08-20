<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConversationStatus;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * Fil de discussion Client ↔ Admin.
 *
 * En v1 l'administrateur fait l'intermédiaire avec le propriétaire, qui n'a
 * pas de compte. La structure accueillera un fil direct en v2.
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $property_id
 * @property int|null $booking_id
 * @property string|null $subject
 * @property ConversationStatus $status
 * @property Carbon|null $last_message_at
 * @property int $customer_unread_count
 * @property int $admin_unread_count
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 * @property-read Property|null $property
 * @property-read Booking|null $booking
 * @property-read Collection<int, Message> $messages
 * @property-read Message|null $latestMessage
 */
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id', 'property_id', 'booking_id', 'subject', 'status',
        'last_message_at', 'customer_unread_count', 'admin_unread_count',
    ];

    protected function casts(): array
    {
        return [
            'status' => ConversationStatus::class,
            'last_message_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }

    /** @return HasMany<Message, $this> */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->where('status', ConversationStatus::Open);
    }

    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('last_message_at')->orderByDesc('id');
    }
}
