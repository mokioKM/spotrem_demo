<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarService;
use Google\Service\Calendar\Event;
use Illuminate\Support\Facades\Log;

/**
 * Google Calendar API で「タイトルにキーワードを含むイベント」を空き枠とし、3時間単位の選択肢に変換する
 *
 * サービスアカウント JSON のパスを .env で指定し、対象カレンダーを SA に共有する必要がある
 */
class GoogleCalendarAvailabilityService
{
    /** @var list<array{start: string, end: string}> */
    private const THREE_HOUR_SLOT_DEFINITIONS = [
        ['start' => '09:00', 'end' => '12:00'],
        ['start' => '12:00', 'end' => '15:00'],
        ['start' => '15:00', 'end' => '18:00'],
        ['start' => '18:00', 'end' => '21:00'],
    ];

    private const ALL_DAY_AVAILABLE_START = '09:00';

    private const ALL_DAY_AVAILABLE_END = '18:00';

    /**
     * @return list<array{date: string, start_time: string, end_time: string, label: string}>
     */
    public function fetchAvailableThreeHourSlots(?string $googleCalendarId, Carbon $from, Carbon $to): array
    {
        if ($googleCalendarId === null || $googleCalendarId === '') {
            return [];
        }

        $client = $this->buildAuthenticatedGoogleClient();
        if ($client === null) {
            Log::debug('Google Calendar: credentials not set or invalid, skipping API');

            return [];
        }

        try {
            $windows = $this->fetchAvailabilityWindowsFromApi($googleCalendarId, $from, $to, $client);

            return $this->buildThreeHourSlotsFromWindows($windows, $from, $to);
        } catch (\Throwable $e) {
            Log::error('Google Calendar API failed', [
                'calendar_id' => $googleCalendarId,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * @return list<array{date: string, start_time: string, end_time: string, label: string}>
     */
    public function fetchAvailableSlots(?string $googleCalendarId, Carbon $from, Carbon $to): array
    {
        return $this->fetchAvailableThreeHourSlots($googleCalendarId, $from, $to);
    }

    public function isThreeHourSlotAvailable(
        ?string $googleCalendarId,
        string $date,
        string $startTime,
        string $endTime,
        Carbon $from,
        Carbon $to,
    ): bool {
        $slots = $this->fetchAvailableThreeHourSlots($googleCalendarId, $from, $to);

        foreach ($slots as $slot) {
            if ($slot['date'] === $date
                && $slot['start_time'] === $startTime
                && $slot['end_time'] === $endTime) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon}>  $windows
     * @return list<array{date: string, start_time: string, end_time: string, label: string}>
     */
    public function buildThreeHourSlotsFromWindows(array $windows, Carbon $from, Carbon $to): array
    {
        $tz = 'Asia/Tokyo';
        $cursor = $from->copy()->timezone($tz)->startOfDay();
        $lastDay = $to->copy()->timezone($tz)->startOfDay();

        $out = [];
        while ($cursor->lte($lastDay)) {
            foreach (self::THREE_HOUR_SLOT_DEFINITIONS as $definition) {
                $slotStart = $cursor->copy()->setTimeFromTimeString($definition['start']);
                $slotEnd = $cursor->copy()->setTimeFromTimeString($definition['end']);

                if (! $this->slotOverlapsAnyWindow($slotStart, $slotEnd, $windows)) {
                    continue;
                }

                $date = $cursor->format('Y-m-d');
                $out[] = [
                    'date' => $date,
                    'start_time' => $definition['start'],
                    'end_time' => $definition['end'],
                    'label' => $this->formatSlotLabel($slotStart, $definition['start'], $definition['end']),
                ];
            }

            $cursor->addDay();
        }

        return $out;
    }

    /**
     * @return list<array{start: Carbon, end: Carbon}>
     */
    private function fetchAvailabilityWindowsFromApi(
        string $calendarId,
        Carbon $from,
        Carbon $to,
        GoogleClient $client,
    ): array {
        $keyword = (string) config('services.google.calendar_slot_title_keyword', '工事対応可能');

        $calendarService = new GoogleCalendarService($client);

        $timeMin = $from->copy()->startOfDay()->timezone('Asia/Tokyo')->toIso8601String();
        $timeMax = $to->copy()->endOfDay()->timezone('Asia/Tokyo')->toIso8601String();

        $events = $calendarService->events->listEvents($calendarId, [
            'timeMin' => $timeMin,
            'timeMax' => $timeMax,
            'singleEvents' => true,
            'orderBy' => 'startTime',
            'maxResults' => 250,
        ]);

        $items = $events->getItems();
        if (! is_array($items)) {
            return [];
        }

        $windows = [];
        foreach ($items as $event) {
            if (! $event instanceof Event) {
                continue;
            }

            $summary = (string) ($event->getSummary() ?? '');
            if ($keyword !== '' && ! str_contains($summary, $keyword)) {
                continue;
            }

            $start = $event->getStart();
            if ($start === null) {
                continue;
            }

            $dateStr = $start->getDate();
            if (is_string($dateStr) && $dateStr !== '') {
                $dayStart = Carbon::parse($dateStr, 'Asia/Tokyo')->startOfDay()
                    ->setTimeFromTimeString(self::ALL_DAY_AVAILABLE_START);
                $dayEnd = Carbon::parse($dateStr, 'Asia/Tokyo')->startOfDay()
                    ->setTimeFromTimeString(self::ALL_DAY_AVAILABLE_END);
                $windows[] = ['start' => $dayStart, 'end' => $dayEnd];

                continue;
            }

            $dateTimeStr = $start->getDateTime();
            if (! is_string($dateTimeStr) || $dateTimeStr === '') {
                continue;
            }

            $windowStart = Carbon::parse($dateTimeStr)->timezone('Asia/Tokyo');
            $end = $event->getEnd();
            $endDateTimeStr = $end?->getDateTime();
            $windowEnd = is_string($endDateTimeStr) && $endDateTimeStr !== ''
                ? Carbon::parse($endDateTimeStr)->timezone('Asia/Tokyo')
                : $windowStart->copy()->addHour();

            if ($windowEnd->lte($windowStart)) {
                continue;
            }

            $windows[] = ['start' => $windowStart, 'end' => $windowEnd];
        }

        return $windows;
    }

    /**
     * @param  list<array{start: Carbon, end: Carbon}>  $windows
     */
    private function slotOverlapsAnyWindow(Carbon $slotStart, Carbon $slotEnd, array $windows): bool
    {
        foreach ($windows as $window) {
            if ($slotStart->lt($window['end']) && $slotEnd->gt($window['start'])) {
                return true;
            }
        }

        return false;
    }

    private function formatSlotLabel(Carbon $day, string $startTime, string $endTime): string
    {
        return $day->copy()->locale('ja')->isoFormat('M月D日（ddd）')
            .$startTime.'–'.$endTime;
    }

    private function resolvedCredentialsPath(): ?string
    {
        $raw = config('services.google.calendar_credentials_path');
        if (! is_string($raw) || trim($raw) === '') {
            return null;
        }

        $trimmed = trim($raw);
        $path = $trimmed[0] === DIRECTORY_SEPARATOR || preg_match('/^[A-Za-z]:\\\\/', $trimmed) === 1
            ? $trimmed
            : base_path($trimmed);

        return is_readable($path) ? $path : null;
    }

    /**
     * Base64（サービスアカウント JSON）またはファイルパスで Google\Client に認証を載せる
     */
    private function buildAuthenticatedGoogleClient(): ?GoogleClient
    {
        $client = new GoogleClient;
        $client->setApplicationName((string) config('app.name', 'SpotRem'));
        $client->setScopes([GoogleCalendarService::CALENDAR_READONLY]);
        $client->setAccessType('offline');

        $b64 = config('services.google.calendar_credentials_base64');
        if (is_string($b64) && trim($b64) !== '') {
            $decoded = base64_decode(trim($b64), true);
            if ($decoded === false) {
                Log::warning('Google Calendar: GOOGLE_CALENDAR_CREDENTIALS_BASE64 is not valid base64');

                return null;
            }
            /** @var mixed $arr */
            $arr = json_decode($decoded, true);
            if (! is_array($arr)) {
                Log::warning('Google Calendar: decoded credentials are not valid JSON object');

                return null;
            }
            $client->setAuthConfig($arr);

            return $client;
        }

        $credentialsPath = $this->resolvedCredentialsPath();
        if ($credentialsPath === null) {
            return null;
        }
        $client->setAuthConfig($credentialsPath);

        return $client;
    }
}
