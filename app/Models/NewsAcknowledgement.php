<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One recipient of an acknowledgement-required news item. acknowledged_at
 * stays null until they confirm, and is never overwritten afterwards so the
 * original confirmation time is a reliable record.
 */
class NewsAcknowledgement extends Model
{
    protected $fillable = ['news_id', 'user_id'];

    protected function casts(): array
    {
        return [
            'acknowledged_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
