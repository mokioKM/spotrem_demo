<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Property;
use App\Models\TroubleCategory;
use App\Models\Vendor;
use App\Models\VendorGenre;
use App\Models\VendorRegion;
use App\Services\Calendar\GoogleCalendarAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class VendorAvailabilityControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_lists_vendors_with_availability_without_slot_payload(): void
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
            'name' => '空きあり業者',
            'phone' => '03-1111-1111',
            'google_calendar_id' => 'calendar@example.com',
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

        $this->mock(GoogleCalendarAvailabilityService::class, function ($mock): void {
            $mock->shouldReceive('fetchAvailableThreeHourSlots')
                ->once()
                ->andReturn([
                    [
                        'date' => '2026-06-10',
                        'start_time' => '09:00',
                        'end_time' => '12:00',
                        'label' => '6月10日（火）9:00–12:00',
                    ],
                ]);
        });

        $response = $this->getJson('/api/vendors/availability?category_id='.$category->id.'&property_id='.$property->id);

        $response->assertOk()
            ->assertJson([
                [
                    'vendor_id' => $vendor->id,
                    'vendor_name' => '空きあり業者',
                ],
            ]);
        $response->assertJsonMissing(['available_slots']);
    }

    public function test_returns_three_hour_slots_for_selected_vendor(): void
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
            'name' => '空きあり業者',
            'phone' => '03-1111-1111',
            'google_calendar_id' => 'calendar@example.com',
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

        $slots = [[
            'date' => '2026-06-10',
            'start_time' => '09:00',
            'end_time' => '12:00',
            'label' => '6月10日（火）9:00–12:00',
        ]];

        $this->mock(GoogleCalendarAvailabilityService::class, function ($mock) use ($slots): void {
            $mock->shouldReceive('fetchAvailableThreeHourSlots')
                ->once()
                ->with(
                    'calendar@example.com',
                    \Mockery::type(Carbon::class),
                    \Mockery::type(Carbon::class),
                )
                ->andReturn($slots);
        });

        $response = $this->getJson('/api/vendors/availability?category_id='.$category->id.'&property_id='.$property->id.'&vendor_id='.$vendor->id);

        $response->assertOk()->assertJson([
            'vendor_id' => $vendor->id,
            'vendor_name' => '空きあり業者',
            'available_slots' => $slots,
        ]);
    }
}
