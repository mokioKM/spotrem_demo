<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\OptionBilling;
use App\Models\OptionContract;
use App\Models\Property;
use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OptionBillingPaidApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://api.line.me/oauth2/v2.1/verify' => Http::response([
                'iss' => 'https://access.line.me',
                'sub' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
                'aud' => 'test-liff-channel',
                'exp' => time() + 3600,
                'iat' => time(),
                'name' => 'Test User',
            ], 200),
        ]);
    }

    public function test_requires_bearer_token(): void
    {
        $property = Property::query()->create([
            'name' => 'テスト物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => null,
            'is_active' => true,
        ]);

        $resident = Resident::query()->create([
            'property_id' => $property->id,
            'line_uid' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
            'name' => '山田',
            'age' => null,
            'room_number' => '101',
            'phone' => '090-0000-0000',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $contract = OptionContract::query()->create([
            'resident_id' => $resident->id,
            'name' => 'オプション',
            'amount' => '1000.00',
            'due_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $billing = OptionBilling::query()->create([
            'option_contract_id' => $contract->id,
            'billing_period' => now()->format('Y-m'),
            'due_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/option-billings/{$billing->id}/paid");

        $response->assertStatus(401);
    }

    public function test_marks_billing_paid_for_matching_resident(): void
    {
        $property = Property::query()->create([
            'name' => 'テスト物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => 10,
            'is_active' => true,
        ]);

        $resident = Resident::query()->create([
            'property_id' => $property->id,
            'line_uid' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
            'name' => '山田',
            'age' => null,
            'room_number' => '101',
            'phone' => '090-0000-0000',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $contract = OptionContract::query()->create([
            'resident_id' => $resident->id,
            'name' => 'クリーニング',
            'amount' => '3000.00',
            'due_date' => now()->addMonth()->toDateString(),
            'is_active' => true,
        ]);

        $billing = OptionBilling::query()->create([
            'option_contract_id' => $contract->id,
            'billing_period' => now()->format('Y-m'),
            'due_date' => now()->addMonth()->toDateString(),
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/option-billings/{$billing->id}/paid", [], [
            'Authorization' => 'Bearer fake-id-token',
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', __('入金を受け付けました。ありがとうございます。'));

        $billing->refresh();
        $this->assertSame('paid', $billing->status);
        $this->assertNotNull($billing->paid_at);
    }

    public function test_returns_403_when_line_uid_does_not_match(): void
    {
        $property = Property::query()->create([
            'name' => 'テスト物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => null,
            'is_active' => true,
        ]);

        $resident = Resident::query()->create([
            'property_id' => $property->id,
            'line_uid' => 'Uotheruserotheruserotheruserotherus',
            'name' => '他人',
            'age' => null,
            'room_number' => '202',
            'phone' => '090-0000-0000',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $contract = OptionContract::query()->create([
            'resident_id' => $resident->id,
            'name' => 'オプション',
            'amount' => '1000.00',
            'due_date' => now()->toDateString(),
            'is_active' => true,
        ]);

        $billing = OptionBilling::query()->create([
            'option_contract_id' => $contract->id,
            'billing_period' => now()->format('Y-m'),
            'due_date' => now()->toDateString(),
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/option-billings/{$billing->id}/paid", [], [
            'Authorization' => 'Bearer fake-id-token',
        ]);

        $response->assertStatus(403);
    }
}
