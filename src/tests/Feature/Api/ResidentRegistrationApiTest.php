<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Property;
use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResidentRegistrationApiTest extends TestCase
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
            'https://api.line.me/v2/bot/message/push' => Http::response([], 200),
        ]);
    }

    public function test_requires_bearer_token(): void
    {
        $response = $this->postJson('/api/residents', [
            'property_id' => 1,
            'name' => '山田',
            'room_number' => '101',
            'phone' => '090-0000-0000',
        ]);

        $response->assertStatus(401);
    }

    public function test_registers_resident_and_returns_201(): void
    {
        $property = Property::query()->create([
            'name' => 'テスト物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => 10,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/residents', [
            'property_id' => $property->id,
            'name' => '山田太郎',
            'age' => 30,
            'room_number' => '101',
            'phone' => '090-1234-5678',
        ], [
            'Authorization' => 'Bearer fake-id-token',
        ]);

        $response->assertCreated();
        $response->assertJsonPath('resident_id', 1);

        $this->assertDatabaseHas('residents', [
            'line_uid' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
            'name' => '山田太郎',
            'property_id' => $property->id,
            'is_active' => true,
        ]);

        Http::assertSentCount(2);
    }

    public function test_returns_409_when_line_uid_already_active(): void
    {
        $property = Property::query()->create([
            'name' => 'テスト物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => null,
            'is_active' => true,
        ]);

        Resident::query()->create([
            'property_id' => $property->id,
            'line_uid' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
            'name' => '既存',
            'age' => null,
            'room_number' => '202',
            'phone' => '090-0000-0000',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/residents', [
            'property_id' => $property->id,
            'name' => '山田太郎',
            'room_number' => '101',
            'phone' => '090-1234-5678',
        ], [
            'Authorization' => 'Bearer fake-id-token',
        ]);

        $response->assertStatus(409);
    }

    public function test_reactivates_inactive_resident_with_same_line_uid(): void
    {
        $property = Property::query()->create([
            'name' => 'テスト物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => null,
            'is_active' => true,
        ]);

        Resident::query()->create([
            'property_id' => $property->id,
            'line_uid' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
            'name' => '退去済み',
            'age' => null,
            'room_number' => '999',
            'phone' => '090-0000-0000',
            'registered_at' => now()->subYear(),
            'is_active' => false,
        ]);

        $response = $this->postJson('/api/residents', [
            'property_id' => $property->id,
            'name' => '再入居 花子',
            'room_number' => '303',
            'phone' => '080-9999-8888',
        ], [
            'Authorization' => 'Bearer fake-id-token',
        ]);

        $response->assertCreated();

        $this->assertDatabaseCount('residents', 1);
        $this->assertDatabaseHas('residents', [
            'line_uid' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
            'name' => '再入居 花子',
            'room_number' => '303',
            'phone' => '080-9999-8888',
            'is_active' => true,
        ]);
    }
}
