<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Property;
use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ResidentProfileUpdateApiTest extends TestCase
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
            ], 200),
        ]);
    }

    public function test_put_updates_active_resident(): void
    {
        $property = Property::query()->create([
            'name' => 'テスト物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => 10,
            'is_active' => true,
        ]);

        $property2 = Property::query()->create([
            'name' => '別物件',
            'address' => '住所2',
            'region' => '大阪府',
            'room_count' => 5,
            'is_active' => true,
        ]);

        Resident::query()->create([
            'property_id' => $property->id,
            'line_uid' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
            'name' => '旧名前',
            'age' => 20,
            'room_number' => '101',
            'phone' => '090-0000-0000',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->putJson('/api/residents', [
            'property_id' => $property2->id,
            'name' => '新名前',
            'age' => 31,
            'room_number' => '202',
            'phone' => '080-1111-2222',
        ], [
            'Authorization' => 'Bearer fake-id-token',
        ]);

        $response->assertOk();
        $response->assertJsonPath('message', __('プロフィールを更新しました'));

        $this->assertDatabaseHas('residents', [
            'line_uid' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
            'name' => '新名前',
            'property_id' => $property2->id,
            'room_number' => '202',
            'phone' => '080-1111-2222',
            'age' => 31,
        ]);
    }

    public function test_put_returns_404_when_not_registered(): void
    {
        $property = Property::query()->create([
            'name' => 'テスト物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => null,
            'is_active' => true,
        ]);

        $response = $this->putJson('/api/residents', [
            'property_id' => $property->id,
            'name' => '名前',
            'room_number' => '101',
            'phone' => '090-1234-5678',
        ], [
            'Authorization' => 'Bearer fake-id-token',
        ]);

        $response->assertNotFound();
    }
}
