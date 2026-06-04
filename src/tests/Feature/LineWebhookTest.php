<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AdminUser;
use App\Models\InvitationToken;
use App\Models\OptionBilling;
use App\Models\OptionContract;
use App\Models\Property;
use App\Models\Resident;
use App\Models\Role;
use App\Models\Vendor;
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
            'phone' => '090-0000-0000',
            'registered_at' => now(),
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

    private function createIssuerAdmin(): AdminUser
    {
        $role = Role::query()->create([
            'name' => 'super_admin',
            'display_name' => 'スーパー管理者',
            'description' => null,
        ]);

        return AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => '発行者',
            'email' => 'issuer-'.uniqid('', true).'@example.com',
            'password_hash' => 'x',
            'line_uid' => null,
            'is_active' => true,
        ]);
    }

    public function test_vendor_registration_text_links_line_uid(): void
    {
        Http::fake([
            'https://api.line.me/v2/bot/message/reply' => Http::response([], 200),
        ]);

        $issuer = $this->createIssuerAdmin();

        $vendor = Vendor::query()->create([
            'name' => '業者A',
            'phone' => '03-1111-2222',
            'line_uid' => null,
            'is_active' => true,
        ]);

        $token = bin2hex(random_bytes(32));
        InvitationToken::query()->create([
            'token' => $token,
            'role' => 'vendor',
            'issued_by' => $issuer->id,
            'target_vendor_id' => $vendor->id,
            'target_admin_user_id' => null,
            'expires_at' => now()->addDay(),
            'is_used' => false,
        ]);

        $lineUid = 'Uvendorwebhookregistrationuid12';
        $messageText = '業者連携 '.$token;
        $body = json_encode([
            'events' => [[
                'type' => 'message',
                'replyToken' => 'VENDOR_REG_REPLY',
                'source' => ['type' => 'user', 'userId' => $lineUid],
                'message' => ['type' => 'text', 'text' => $messageText],
            ]],
        ], JSON_THROW_ON_ERROR);

        $secret = (string) config('services.line.messaging_channel_secret');
        $sig = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $this->call('POST', '/line/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
            'HTTP_X_LINE_SIGNATURE' => $sig,
        ], $body)->assertOk();

        $vendor->refresh();
        $this->assertSame($lineUid, $vendor->line_uid);
        $this->assertTrue(InvitationToken::query()->where('token', $token)->value('is_used'));

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            $text = (string) (($request->data()['messages'][0]['text'] ?? ''));

            return str_contains($text, '業者LINE連携が完了');
        });
    }

    public function test_admin_registration_text_links_line_uid(): void
    {
        Http::fake([
            'https://api.line.me/v2/bot/message/reply' => Http::response([], 200),
        ]);

        $issuer = $this->createIssuerAdmin();

        $role = Role::query()->create([
            'name' => 'staff',
            'display_name' => '担当者',
            'description' => null,
        ]);
        $admin = AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => '担当者A',
            'email' => 'admin-reg@example.com',
            'password_hash' => 'x',
            'line_uid' => null,
            'is_active' => true,
        ]);

        $token = bin2hex(random_bytes(32));
        InvitationToken::query()->create([
            'token' => $token,
            'role' => 'admin_user',
            'issued_by' => $issuer->id,
            'target_vendor_id' => null,
            'target_admin_user_id' => $admin->id,
            'expires_at' => now()->addDay(),
            'is_used' => false,
        ]);

        $lineUid = 'Uadminwebhookregistrationuid123';
        $messageText = '担当者連携 '.$token;
        $body = json_encode([
            'events' => [[
                'type' => 'message',
                'replyToken' => 'ADMIN_REG_REPLY',
                'source' => ['type' => 'user', 'userId' => $lineUid],
                'message' => ['type' => 'text', 'text' => $messageText],
            ]],
        ], JSON_THROW_ON_ERROR);

        $secret = (string) config('services.line.messaging_channel_secret');
        $sig = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $this->call('POST', '/line/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
            'HTTP_X_LINE_SIGNATURE' => $sig,
        ], $body)->assertOk();

        $admin->refresh();
        $this->assertSame($lineUid, $admin->line_uid);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            $text = (string) (($request->data()['messages'][0]['text'] ?? ''));

            return str_contains($text, '担当者LINE連携が完了');
        });
    }

    public function test_invalid_registration_token_replies_error(): void
    {
        Http::fake([
            'https://api.line.me/v2/bot/message/reply' => Http::response([], 200),
        ]);

        $fakeToken = str_repeat('a', 64);
        $body = json_encode([
            'events' => [[
                'type' => 'message',
                'replyToken' => 'INVALID_REG',
                'source' => ['type' => 'user', 'userId' => 'Uinvalid'],
                'message' => ['type' => 'text', 'text' => '業者連携 '.$fakeToken],
            ]],
        ], JSON_THROW_ON_ERROR);

        $secret = (string) config('services.line.messaging_channel_secret');
        $sig = base64_encode(hash_hmac('sha256', $body, $secret, true));

        $this->call('POST', '/line/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json; charset=utf-8',
            'HTTP_X_LINE_SIGNATURE' => $sig,
        ], $body)->assertOk();

        Http::assertSent(function (\Illuminate\Http\Client\Request $request): bool {
            $text = (string) (($request->data()['messages'][0]['text'] ?? ''));

            return str_contains($text, '無効') || str_contains($text, '期限切れ');
        });
    }
}
