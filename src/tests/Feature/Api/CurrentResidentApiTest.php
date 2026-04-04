<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Property;
use App\Models\Resident;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CurrentResidentApiTest extends TestCase
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

    public function test_returns_404_when_not_registered(): void
    {
        $response = $this->getJson('/api/me', [
            'Authorization' => 'Bearer fake-id-token',
        ]);

        $response->assertStatus(404);
    }

    public function test_returns_profile_when_active_resident_exists(): void
    {
        $property = Property::query()->create([
            'name' => 'テスト物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => 10,
            'is_active' => true,
        ]);

        Resident::query()->create([
            'property_id' => $property->id,
            'line_uid' => 'Udeadbeefdeadbeefdeadbeefdeadbeef',
            'name' => '山田',
            'age' => null,
            'room_number' => '101',
            'phone' => '090-0000-0000',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/me', [
            'Authorization' => 'Bearer fake-id-token',
        ]);

        $response->assertOk();
        $response->assertJsonPath('property_id', $property->id);
        $response->assertJsonPath('property_name', 'テスト物件');
        $response->assertJsonPath('room_number', '101');
    }
}
