<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Models\NotificationLog;

/**
 * notification_logs への INSERT を一本化し、呼び出し元（Service/Job）の重複を防ぐ
 */
final class NotificationLogWriter
{
    /**
     * @param  non-empty-string  $recipientType  resident / vendor / admin / group 等
     * @param  non-empty-string  $channel  例: line_message
     * @param  non-empty-string  $eventType  設計書 07 の event_type
     * @param  non-empty-string  $status  success / failed / skipped
     */
    public function write(
        string $recipientType,
        int $recipientId,
        string $channel,
        string $eventType,
        string $status,
        ?int $relatedId = null,
    ): NotificationLog {
        return NotificationLog::query()->create([
            'recipient_type' => $recipientType,
            'recipient_id' => $recipientId,
            'channel' => $channel,
            'event_type' => $eventType,
            'related_id' => $relatedId,
            'sent_at' => now(),
            'status' => $status,
        ]);
    }
}
