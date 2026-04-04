<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Property;
use App\Models\Resident;
use App\Models\TroubleCategory;
use App\Models\Vendor;
use App\Models\VendorGenre;
use App\Models\VendorRegion;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TroubleVendorCooperationGroupNotificationTest extends TestCase
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

    public function test_pushes_trouble_summary_to_vendor_cooperation_line_group(): void
    {
        $property = Property::query()->create([
            'name' => 'テスト物件',
            'address' => '住所',
            'region' => '東京都',
            'room_count' => 10,
            'is_active' => true,
        ]);

        $category = TroubleCategory::query()->create([
            'name' => 'plumbing',
            'display_name' => '水回り',
            'show_phone_number' => false,
            'emergency_phone' => null,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $vendor = Vendor::query()->create([
            'name' => 'テスト業者',
            'phone' => '03-0000-0000',
            'line_uid' => null,
            'line_messaging_group_id' => 'C012345678901234567890123456789ab',
            'google_calendar_id' => null,
            'is_active' => true,
        ]);

        VendorGenre::query()->create([
            'vendor_id' => $vendor->id,
            'category_id' => $category->id,
        ]);

        VendorRegion::query()->create([
            'vendor_id' => $vendor->id,
            'region' => '東京都',
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

        $response = $this->postJson('/api/trouble-requests', [
            'category_id' => $category->id,
            'description' => '水道が漏れています。',
            'vendor_id' => $vendor->id,
            'preferred_date' => now()->format('Y-m-d'),
        ], [
            'Authorization' => 'Bearer fake-id-token',
        ]);

        $response->assertCreated();

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($vendor): bool {
            if ($request->url() !== 'https://api.line.me/v2/bot/message/push') {
                return false;
            }
            $data = $request->data();
            if (($data['to'] ?? null) !== $vendor->line_messaging_group_id) {
                return false;
            }
            $text = $data['messages'][0]['text'] ?? '';

            return str_contains((string) $text, 'トラブル依頼') && str_contains((string) $text, '水道が漏れています');
        });

        $this->assertDatabaseHas('notification_logs', [
            'recipient_type' => 'vendor',
            'recipient_id' => $vendor->id,
            'event_type' => 'trouble_vendor_cooperation_group',
            'status' => 'success',
        ]);
    }
}
