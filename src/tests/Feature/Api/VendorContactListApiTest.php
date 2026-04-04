<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Property;
use App\Models\TroubleCategory;
use App\Models\Vendor;
use App\Models\VendorGenre;
use App\Models\VendorRegion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VendorContactListApiTest extends TestCase
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

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/vendors/contact-list?category_id=1&property_id=1');

        $response->assertStatus(401);
    }

    public function test_returns_matching_vendor_phones(): void
    {
        $category = TroubleCategory::query()->create([
            'name' => 'cat_a',
            'display_name' => 'A',
            'show_phone_number' => true,
            'emergency_phone' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $property = Property::query()->create([
            'name' => '物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => null,
            'is_active' => true,
        ]);

        $vendor = Vendor::query()->create([
            'name' => 'テスト業者',
            'phone' => '03-1111-2222',
            'line_uid' => null,
            'google_calendar_id' => null,
            'is_active' => true,
        ]);

        VendorGenre::query()->create(['vendor_id' => $vendor->id, 'category_id' => $category->id]);
        VendorRegion::query()->create(['vendor_id' => $vendor->id, 'region' => '東京都']);

        $response = $this->getJson(
            '/api/vendors/contact-list?category_id='.$category->id.'&property_id='.$property->id,
            ['Authorization' => 'Bearer fake-token'],
        );

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.vendor_name', 'テスト業者');
        $response->assertJsonPath('0.phone', '03-1111-2222');
    }
}
