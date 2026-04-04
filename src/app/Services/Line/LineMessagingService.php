<?php

declare(strict_types=1);

namespace App\Services\Line;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * LINE Messaging API への push 送信
 */
final class LineMessagingService
{
    private const PUSH_ENDPOINT = 'https://api.line.me/v2/bot/message/push';

    private const REPLY_ENDPOINT = 'https://api.line.me/v2/bot/message/reply';

    /**
     * ユーザー（またはグループ・ルーム）の ID 宛てにプッシュ
     *
     * @param  list<array<string, mixed>>  $messages  LINE メッセージオブジェクトの配列
     */
    public function pushToUser(string $lineUserId, array $messages, string $eventType): bool
    {
        return $this->push($lineUserId, $messages, $eventType);
    }

    /**
     * グループ・ルーム等の target ID 宛て（管理会社通知用）
     *
     * @param  list<array<string, mixed>>  $messages
     */
    public function pushToTarget(string $targetId, array $messages, string $eventType): bool
    {
        return $this->push($targetId, $messages, $eventType);
    }

    /**
     * Webhook の replyToken で返信（テキスト受信時の自動応答など）
     *
     * @param  list<array<string, mixed>>  $messages
     */
    public function reply(string $replyToken, array $messages, string $eventType): bool
    {
        $token = config('services.line.messaging_channel_access_token');
        if (! is_string($token) || $token === '') {
            Log::warning('LINE_CHANNEL_ACCESS_TOKEN が未設定のため reply をスキップします', ['event' => $eventType]);

            return false;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::REPLY_ENDPOINT, [
                    'replyToken' => $replyToken,
                    'messages' => $messages,
                ]);

            if (! $response->successful()) {
                Log::error('LINE reply API error', [
                    'event' => $eventType,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('LINE reply exception', [
                'event' => $eventType,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    private function push(string $to, array $messages, string $eventType): bool
    {
        $token = config('services.line.messaging_channel_access_token');
        if (! is_string($token) || $token === '') {
            Log::warning('LINE_CHANNEL_ACCESS_TOKEN が未設定のため push をスキップします', ['event' => $eventType]);

            return false;
        }

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => 'Bearer '.$token,
                    'Content-Type' => 'application/json',
                ])
                ->post(self::PUSH_ENDPOINT, [
                    'to' => $to,
                    'messages' => $messages,
                ]);

            if (! $response->successful()) {
                Log::error('LINE push API error', [
                    'event' => $eventType,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('LINE push exception', [
                'event' => $eventType,
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
