<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LineWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_rejects_request_without_signature(): void
    {
        $response = $this->postJson('/line/webhook', ['events' => []]);

        $response->assertStatus(403);
    }

    public function test_rejects_invalid_signature(): void
    {
        $body = '{"events":[]}';
        $response = $this->call('POST', '/line/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
            'HTTP_X_LINE_SIGNATURE' => 'invalid',
        ], $body);

        $response->assertStatus(403);
    }

    public function test_accepts_valid_signature_and_returns_empty_json(): void
    {
        Http::fake([
            'https://api.line.me/v2/bot/message/reply' => Http::response([], 200),
        ]);

        $secret = (string) config('services.line.messaging_channel_secret');
        $this->assertNotSame('', $secret);

        $body = '{"events":[{"type":"message","replyToken":"TEST_REPLY_TOKEN","source":{"type":"user","userId":"Utest"},"message":{"type":"text","text":"hi"}}]}';
        $sig = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $response = $this->call('POST', '/line/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
            'HTTP_X_LINE_SIGNATURE' => $sig,
        ], $body);

        $response->assertOk();
        $response->assertExactJson([]);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            if ($request->url() !== 'https://api.line.me/v2/bot/message/reply') {
                return false;
            }
            $data = $request->data();
            if (($data['replyToken'] ?? null) !== 'TEST_REPLY_TOKEN') {
                return false;
            }
            $messages = $data['messages'] ?? [];
            $text = $messages[0]['text'] ?? '';

            return str_contains((string) $text, 'テキストメッセージへの返信には対応しておりません');
        });
    }

    public function test_does_not_reply_to_non_text_message(): void
    {
        Http::fake([
            'https://api.line.me/v2/bot/message/reply' => Http::response([], 200),
        ]);

        $secret = (string) config('services.line.messaging_channel_secret');
        $body = '{"events":[{"type":"message","replyToken":"T","source":{"type":"user","userId":"U1"},"message":{"type":"sticker","packageId":"1","stickerId":"1"}}]}';
        $sig = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $response = $this->call('POST', '/line/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
            'HTTP_X_LINE_SIGNATURE' => $sig,
        ], $body);

        $response->assertOk();
        Http::assertNothingSent();
    }
}
