<?php

declare(strict_types=1);

namespace App\Services\Line;

use Illuminate\Support\Facades\Log;

/**
 * Webhook で受け取ったイベントのうち、テキストメッセージに定型で返信する
 *
 * 個別のチャット対応は行わず、LIFF・リッチメニューへの誘導に留める。
 */
final class LineWebhookInboundService
{
    private const UNSUPPORTED_TEXT_REPLY = "このトークでのテキストメッセージへの返信には対応しておりません。\nお困りの際は、画面下のメニューから「トラブル報告」などをご利用ください。";

    public function __construct(
        private readonly LineMessagingService $lineMessaging,
    ) {}

    /**
     * @param  list<mixed>  $events
     */
    public function handleEvents(array $events): void
    {
        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }
            $this->handleOneEvent($event);
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handleOneEvent(array $event): void
    {
        $type = $event['type'] ?? null;
        if ($type !== 'message') {
            return;
        }

        $message = $event['message'] ?? null;
        if (! is_array($message) || ($message['type'] ?? null) !== 'text') {
            return;
        }

        $replyToken = $event['replyToken'] ?? null;
        if (! is_string($replyToken) || $replyToken === '') {
            Log::warning('LINE webhook text message without replyToken', [
                'source' => $event['source'] ?? null,
            ]);

            return;
        }

        $ok = $this->lineMessaging->reply(
            $replyToken,
            [['type' => 'text', 'text' => self::UNSUPPORTED_TEXT_REPLY]],
            'webhook_text_unsupported',
        );

        if (! $ok) {
            Log::warning('LINE webhook auto-reply failed', [
                'event' => 'webhook_text_unsupported',
            ]);
        }
    }
}
