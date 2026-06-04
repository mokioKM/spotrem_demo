<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Calendar;

use App\Services\Calendar\GoogleCalendarAvailabilityService;
use Carbon\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class GoogleCalendarAvailabilityServiceTest extends TestCase
{
    private GoogleCalendarAvailabilityService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new GoogleCalendarAvailabilityService;
    }

    public function test_builds_three_hour_slots_from_timed_window(): void
    {
        $from = Carbon::parse('2026-06-10', 'Asia/Tokyo')->startOfDay();
        $to = $from->copy();

        $windows = [[
            'start' => Carbon::parse('2026-06-10 10:00', 'Asia/Tokyo'),
            'end' => Carbon::parse('2026-06-10 14:00', 'Asia/Tokyo'),
        ]];

        $slots = $this->service->buildThreeHourSlotsFromWindows($windows, $from, $to);

        $this->assertSame([
            ['date' => '2026-06-10', 'start_time' => '09:00', 'end_time' => '12:00'],
            ['date' => '2026-06-10', 'start_time' => '12:00', 'end_time' => '15:00'],
        ], array_map(static fn (array $slot): array => [
            'date' => $slot['date'],
            'start_time' => $slot['start_time'],
            'end_time' => $slot['end_time'],
        ], $slots));
    }

    public function test_all_day_window_covers_nine_to_eighteen_only(): void
    {
        $from = Carbon::parse('2026-06-10', 'Asia/Tokyo')->startOfDay();
        $to = $from->copy();

        $windows = [[
            'start' => Carbon::parse('2026-06-10 09:00', 'Asia/Tokyo'),
            'end' => Carbon::parse('2026-06-10 18:00', 'Asia/Tokyo'),
        ]];

        $slots = $this->service->buildThreeHourSlotsFromWindows($windows, $from, $to);

        $this->assertSame([
            '09:00',
            '12:00',
            '15:00',
        ], array_column($slots, 'start_time'));
        $this->assertNotContains('18:00', array_column($slots, 'start_time'));
    }

    #[DataProvider('slotLabelProvider')]
    public function test_slot_label_contains_date_and_time_range(string $date, string $start, string $end): void
    {
        $from = Carbon::parse($date, 'Asia/Tokyo')->startOfDay();
        $to = $from->copy();
        $windows = [[
            'start' => Carbon::parse("{$date} {$start}", 'Asia/Tokyo'),
            'end' => Carbon::parse("{$date} {$end}", 'Asia/Tokyo'),
        ]];

        $slots = $this->service->buildThreeHourSlotsFromWindows($windows, $from, $to);

        $this->assertNotEmpty($slots);
        $this->assertStringContainsString($start.'–', $slots[0]['label']);
    }

    /**
     * @return list<array{0: string, 1: string, 2: string}>
     */
    public static function slotLabelProvider(): array
    {
        return [
            ['2026-06-10', '09:00', '12:00'],
            ['2026-06-11', '18:00', '21:00'],
        ];
    }
}
