<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Property;
use App\Models\Resident;
use App\Models\TroubleCategory;
use App\Models\Vendor;
use App\Models\VendorGenre;
use App\Models\VendorRegion;
use App\Services\Calendar\GoogleCalendarAvailabilityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TroubleVendorDirectNotificationTest extends TestCase
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

    public function test_pushes_trouble_summary_with_preferred_slots_to_vendor_line_uid(): void
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
            'line_uid' => 'Uvendorlineuidvendorlineuidvend',
            'google_calendar_id' => 'vendor-calendar@example.com',
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

        $slotDate = now('Asia/Tokyo')->addDay()->format('Y-m-d');

        $this->mock(GoogleCalendarAvailabilityService::class, function ($mock): void {
            $mock->shouldReceive('isThreeHourSlotAvailable')
                ->twice()
                ->andReturn(true);
        });

        $response = $this->postJson('/api/trouble-requests', [
            'category_id' => $category->id,
            'description' => '水道が漏れています。',
            'vendor_id' => $vendor->id,
            'preferred_slots' => [
                [
                    'priority' => 1,
                    'date' => $slotDate,
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                ],
                [
                    'priority' => 2,
                    'date' => $slotDate,
                    'start_time' => '12:00',
                    'end_time' => '15:00',
                ],
            ],
        ], [
            'Authorization' => 'Bearer fake-id-token',
        ]);

        $response->assertCreated();

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) use ($vendor, $slotDate): bool {
            if ($request->url() !== 'https://api.line.me/v2/bot/message/push') {
                return false;
            }
            $data = $request->data();
            if (($data['to'] ?? null) !== $vendor->line_uid) {
                return false;
            }
            $text = $data['messages'][0]['text'] ?? '';

            return str_contains((string) $text, '修理依頼')
                && str_contains((string) $text, '水道が漏れています')
                && str_contains((string) $text, '第1希望')
                && str_contains((string) $text, $slotDate)
                && str_contains((string) $text, '09:00–12:00');
        });

        $this->assertDatabaseHas('trouble_request_preferred_slots', [
            'priority' => 1,
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $this->assertDatabaseHas('notification_logs', [
            'recipient_type' => 'vendor',
            'recipient_id' => $vendor->id,
            'event_type' => 'vendor_dispatched',
            'status' => 'success',
        ]);
    }

    public function test_rejects_invalid_preferred_slot_for_vendor(): void
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
            'line_uid' => 'Uvendorlineuidvendorlineuidvend',
            'google_calendar_id' => 'vendor-calendar@example.com',
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
            'room_number' => '101',
            'phone' => '090-0000-0000',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $this->mock(GoogleCalendarAvailabilityService::class, function ($mock): void {
            $mock->shouldReceive('isThreeHourSlotAvailable')
                ->once()
                ->andReturn(false);
        });

        $this->postJson('/api/trouble-requests', [
            'category_id' => $category->id,
            'description' => 'テスト',
            'vendor_id' => $vendor->id,
            'preferred_slots' => [
                [
                    'priority' => 1,
                    'date' => now('Asia/Tokyo')->addDay()->format('Y-m-d'),
                    'start_time' => '09:00',
                    'end_time' => '12:00',
                ],
            ],
        ], [
            'Authorization' => 'Bearer fake-id-token',
        ])->assertStatus(422);
    }
}
