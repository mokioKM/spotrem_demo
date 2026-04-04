<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * 通知送信ログ（recipient は morph、related_id は設計上ポリモーフィック未拡張のため生ID）
 */
class NotificationLog extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'recipient_type',
        'recipient_id',
        'channel',
        'event_type',
        'related_id',
        'sent_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    /**
     * recipient_type は morphMap（resident / vendor / admin）で解決する
     */
    public function recipient(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'recipient_type', 'recipient_id');
    }
}
