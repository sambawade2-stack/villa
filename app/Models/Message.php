<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\MessageChannel;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $conversation_id
 * @property int $sender_id
 * @property string $body
 * @property array<string, mixed>|null $attachments
 * @property MessageChannel $channel
 * @property string|null $external_id
 * @property Carbon|null $read_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $failed_at
 * @property string|null $failure_reason
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Conversation|null $conversation
 * @property-read User|null $sender
 */
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    protected $fillable = [
        'conversation_id', 'sender_id', 'body', 'channel', 'attachments',
        'external_id', 'read_at', 'delivered_at', 'failed_at', 'failure_reason',
    ];

    protected function casts(): array
    {
        return [
            'channel' => MessageChannel::class,
            'attachments' => 'array',
            'read_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function isUnread(): bool
    {
        return $this->read_at === null;
    }

    public function hasFailed(): bool
    {
        return $this->failed_at !== null;
    }
}
