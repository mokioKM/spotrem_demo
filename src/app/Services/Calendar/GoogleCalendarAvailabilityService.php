<?php

declare(strict_types=1);

namespace App\Services\Calendar;

use Carbon\Carbon;
use Google\Client as GoogleClient;
use Google\Service\Calendar as GoogleCalendarService;
use Google\Service\Calendar\Event;
use Illuminate\Support\Facades\Log;

/**
 * Google Calendar API で「タイトルにキーワードを含むイベント」を空き枠として返す
 *
 * サービスアカウント JSON のパスを .env で指定し、対象カレンダーを SA に共有する必要がある
 */
final class GoogleCalendarAvailabilityService
{
    /**
     * @return list<array{date: string, label: string}>
     */
    public function fetchAvailableSlots(?string $googleCalendarId, Carbon $from, Carbon $to): array
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
            return $this->fetchSlotsFromApi($googleCalendarId, $from, $to, $client);
        } catch (\Throwable $e) {
            Log::error('Google Calendar API failed', [
                'calendar_id' => $googleCalendarId,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
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

    /**
     * @return list<array{date: string, label: string}>
     */
    private function fetchSlotsFromApi(string $calendarId, Carbon $from, Carbon $to, GoogleClient $client): array
    {
        // 空文字にするとタイトル条件なし（全日程を枠として返す）。未設定時の既定は config 側で「対応可能」
        $keyword = (string) config('services.google.calendar_slot_title_keyword', '対応可能');

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

        $out = [];
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
            $dateTimeStr = $start->getDateTime();
            if (is_string($dateStr) && $dateStr !== '') {
                $label = $summary !== '' ? $summary : $keyword.'（終日）';
                $out[] = ['date' => $dateStr, 'label' => $label];

                continue;
            }
            if (is_string($dateTimeStr) && $dateTimeStr !== '') {
                $dt = Carbon::parse($dateTimeStr)->timezone('Asia/Tokyo');
                $d = $dt->format('Y-m-d');
                $label = $summary !== '' ? $summary : $dt->locale('ja')->isoFormat('M月D日（ddd）H:mm');
                $out[] = ['date' => $d, 'label' => $label];
            }
        }

        return $out;
    }
}
