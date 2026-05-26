<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\OptionBilling;
use App\Models\OptionContract;
use App\Models\Property;
use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LineWebhookTest extends TestCase
{
    use RefreshDatabase;

    private function createResidentWithBilling(string $lineUid = 'U_LINE_TEST_USER'): OptionBilling
    {
        $property = Property::create([
            'name' => 'テスト物件',
            'address' => '東京都テスト区1-1',
            'region' => '関東',
            'room_count' => 10,
            'is_active' => true,
        ]);
        $resident = Resident::create([
            'property_id' => $property->id,
            'line_uid' => $lineUid,
            'name' => 'テスト入居者',
            'room_number' => '101',
            'is_active' => true,
        ]);
        $contract = OptionContract::create([
            'resident_id' => $resident->id,
            'name' => 'テスト契約',
            'amount' => 5000,
            'due_date' => now()->addMonth(),
            'is_active' => true,
        ]);

        return OptionBilling::create([
            'option_contract_id' => $contract->id,
            'billing_period' => now()->format('Y-m'),
            'due_date' => now()->addMonth(),
            'status' => 'pending',
        ]);
    }

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

    public function test_replies_thank_you_on_option_invoice_payment_complete_postback(): void
    {
        Http::fake([
            'https://api.line.me/v2/bot/message/reply' => Http::response([], 200),
        ]);

        $secret = (string) config('services.line.messaging_channel_secret');
        $body = '{"events":[{"type":"postback","replyToken":"POSTBACK_REPLY","source":{"type":"user","userId":"U1"},"postback":{"data":"option_invoice_payment_complete"}}]}';
        $sig = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $response = $this->call('POST', '/line/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
            'HTTP_X_LINE_SIGNATURE' => $sig,
        ], $body);

        $response->assertOk();

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            if ($request->url() !== 'https://api.line.me/v2/bot/message/reply') {
                return false;
            }
            $data = $request->data();
            if (($data['replyToken'] ?? null) !== 'POSTBACK_REPLY') {
                return false;
            }
            $text = (string) (($data['messages'][0]['text'] ?? ''));

            return str_contains($text, '入金のご連絡ありがとうございます');
        });
    }

    public function test_payment_complete_postback_with_billing_id_updates_status(): void
    {
        Http::fake([
            'https://api.line.me/v2/bot/message/reply' => Http::response([], 200),
        ]);

        $billing = $this->createResidentWithBilling();

        $secret = (string) config('services.line.messaging_channel_secret');
        $body = json_encode([
            'events' => [[
                'type' => 'postback',
                'replyToken' => 'POSTBACK_REPLY_2',
                'source' => ['type' => 'user', 'userId' => 'U_LINE_TEST_USER'],
                'postback' => ['data' => "option_invoice_payment_complete:{$billing->id}"],
            ]],
        ]);
        $sig = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $response = $this->call('POST', '/line/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
            'HTTP_X_LINE_SIGNATURE' => $sig,
        ], $body);

        $response->assertOk();

        $billing->refresh();
        $this->assertSame('paid', $billing->status);
        $this->assertNotNull($billing->paid_at);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            if ($request->url() !== 'https://api.line.me/v2/bot/message/reply') {
                return false;
            }
            $data = $request->data();
            $text = (string) (($data['messages'][0]['text'] ?? ''));

            return str_contains($text, '入金のご連絡ありがとうございます');
        });
    }
}
