<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Resident;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use App\Services\Line\LineMessagingService;
use App\Services\Line\LineTroubleLiffUri;
use App\Services\Notification\NotificationLogWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/**
 * 入居者登録（基本設計 01）。通知失敗時も DB は確定し notification_logs のみ failed 記録する
 */
final class ResidentRegistrationService
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
        private readonly LineMessagingService $lineMessaging,
        private readonly NotificationLogWriter $notificationLogWriter,
    ) {}

    /**
     * @param  array{property_id: int, name: string, age?: int|null, room_number: string, phone: string}  $payload
     */
    public function register(string $lineUid, array $payload): Resident
    {
        $resident = DB::transaction(function () use ($lineUid, $payload): Resident {
            $existing = $this->residentRepository->findByLineUid($lineUid);

            if ($existing !== null && $existing->is_active) {
                throw new ConflictHttpException(__('このLINEアカウントは既に登録済みです。'));
            }

            $base = [
                'property_id' => $payload['property_id'],
                'line_uid' => $lineUid,
                'name' => $payload['name'],
                'age' => $payload['age'] ?? null,
                'room_number' => $payload['room_number'],
                'phone' => $payload['phone'],
                'registered_at' => now(),
                'is_active' => true,
            ];

            // line_uid は UNIQUE のため、再入居は既存レコードの更新で表現する（設計書は INSERT だが DB 制約に合わせる）
            if ($existing !== null) {
                return $this->residentRepository->reactivateForReentry($existing, $base);
            }

            return $this->residentRepository->create($base);
        });

        $this->sendRegistrationNotification($resident);

        return $resident;
    }

    private function sendRegistrationNotification(Resident $resident): void
    {
        $thankYou = "登録ありがとうございます。\n\nトラブル（お困りごと）がありましたら、下のボタンから内容をお送りください。";
        $liffUri = LineTroubleLiffUri::openUri();
        if ($liffUri !== null) {
            $messages = [
                ['type' => 'text', 'text' => $thankYou],
                [
                    'type' => 'template',
                    'altText' => 'トラブル内容を送る',
                    'template' => [
                        'type' => 'buttons',
                        'text' => 'トラブル内容はこちらのボタンから送信できます。',
                        'actions' => [
                            [
                                'type' => 'uri',
                                'label' => 'トラブル内容を送る',
                                'uri' => $liffUri,
                            ],
                        ],
                    ],
                ],
            ];
        } else {
            $fallback = $thankYou."\n\n※LINE のメニューから「トラブル報告」を開いてご連絡ください。";
            $messages = [['type' => 'text', 'text' => $fallback]];
        }

        $status = 'failed';
        try {
            $ok = $this->lineMessaging->pushToUser((string) $resident->line_uid, $messages, 'registration_complete');
            $status = $ok ? 'success' : 'failed';
        } catch (\Throwable $e) {
            Log::error('Registration LINE push failed', [
                'resident_id' => $resident->id,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $this->notificationLogWriter->write(
                'resident',
                (int) $resident->id,
                'line_message',
                'registration_complete',
                $status,
                null,
            );
        } catch (\Throwable $e) {
            Log::error('notification_logs write failed after registration', [
                'resident_id' => $resident->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
