<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    public $timestamps = false;

    const SENDER_CUSTOMER = 'customer';
    const SENDER_CARRIER = 'carrier';
    const SENDER_AI = 'ai';
    const SENDER_SYSTEM = 'system';

    protected $fillable = [
        'chat_id',
        'sender_type',
        'sender_id',
        'content',
        'is_read',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function chat(): BelongsTo
    {
        return $this->belongsTo(Chat::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function isFromAI(): bool
    {
        return $this->sender_type === self::SENDER_AI;
    }

    public function isFromSystem(): bool
    {
        return $this->sender_type === self::SENDER_SYSTEM;
    }

    public function isFromHuman(): bool
    {
        return in_array($this->sender_type, [self::SENDER_CUSTOMER, self::SENDER_CARRIER]);
    }
}
