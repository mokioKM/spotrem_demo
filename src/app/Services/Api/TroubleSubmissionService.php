<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\AdminUser;
use App\Models\Property;
use App\Models\RequestAttachment;
use App\Models\Resident;
use App\Models\TroubleRequest;
use App\Models\Vendor;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Services\Calendar\GoogleCalendarAvailabilityService;
use App\Services\Line\LineMessagingService;
use App\Services\Media\CloudinaryTroubleAttachmentVerifier;
use App\Services\Notification\NotificationLogWriter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * トラブル依頼の登録と通知（基本設計 02）
 */
final class TroubleSubmissionService
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
        private readonly VendorRepositoryInterface $vendorRepository,
        private readonly GoogleCalendarAvailabilityService $calendarAvailability,
        private readonly LineMessagingService $lineMessaging,
        private readonly NotificationLogWriter $notificationLogWriter,
        private readonly CloudinaryTroubleAttachmentVerifier $cloudinaryAttachmentVerifier,
    ) {}

    /**
     * @param  array{category_id: int, description: string, vendor_id?: int|null, preferred_date?: string|null, attachments?: list<array{cloudinary_public_id: string, file_type: string, url: string}>}  $payload
     */
    public function submit(string $lineUid, array $payload): TroubleRequest
    {
        $resident = $this->residentRepository->findActiveByLineUid($lineUid);
        if ($resident === null) {
            throw new HttpException(401, __('入居者登録を先に行ってください。'));
        }

        $resident->load('property');
        $property = $resident->property;
        if ($property === null) {
            throw new HttpException(500, __('物件情報を取得できませんでした。'));
        }

        $vendorId = $payload['vendor_id'] ?? null;
        if ($vendorId !== null) {
            $this->assertVendorAllowedForRequest((int) $vendorId, (int) $payload['category_id'], $property);
        }

        foreach ($payload['attachments'] ?? [] as $row) {
            $this->cloudinaryAttachmentVerifier->assertValid(
                (string) $row['cloudinary_public_id'],
                (string) $row['url'],
                (string) $row['file_type'],
            );
        }

        $request = DB::transaction(function () use ($resident, $property, $payload): TroubleRequest {
            $tr = TroubleRequest::query()->create([
                'resident_id' => $resident->id,
                'property_id' => $property->id,
                'category_id' => $payload['category_id'],
                'vendor_id' => $payload['vendor_id'] ?? null,
                'description' => $payload['description'],
                'preferred_date' => $payload['preferred_date'] ?? null,
                'scheduled_at' => null,
                'status' => 'pending',
            ]);

            foreach ($payload['attachments'] ?? [] as $row) {
                RequestAttachment::query()->create([
                    'request_id' => $tr->id,
                    'cloudinary_public_id' => $row['cloudinary_public_id'],
                    'file_type' => $row['file_type'],
                    'url' => $row['url'],
                ]);
            }

            return $tr->load(['category', 'vendor', 'resident', 'property']);
        });

        $this->sendNotifications($request, $resident, $property);

        return $request;
    }

    private function assertVendorAllowedForRequest(int $vendorId, int $categoryId, Property $property): void
    {
        $allowedIds = $this->vendorRepository
            ->findActiveMatchingCategoryAndRegion($categoryId, (string) $property->region)
            ->pluck('id')
            ->all();

        if (! in_array($vendorId, $allowedIds, true)) {
            throw new HttpException(422, __('選択した業者はこの地域・カテゴリでは利用できません。'));
        }
    }

    private function sendNotifications(TroubleRequest $request, Resident $resident, Property $property): void
    {
        $residentText = $this->buildResidentAcknowledgementText($request, $resident, $property);
        $this->pushAndLog(
            (string) $resident->line_uid,
            [['type' => 'text', 'text' => $residentText]],
            'resident',
            (int) $resident->id,
            'trouble_received',
            (int) $request->id,
        );

        $this->notifyLinkedAdminUsers($request, $resident, $property);
        $this->notifyAssignedVendor($request, $resident, $property);
    }

    private function notifyLinkedAdminUsers(TroubleRequest $request, Resident $resident, Property $property): void
    {
        $admins = AdminUser::query()
            ->where('is_active', true)
            ->whereNotNull('line_uid')
            ->where('line_uid', '!=', '')
            ->get();

        if ($admins->isEmpty()) {
            $this->notificationLogWriter->write(
                'admin',
                0,
                'line_message',
                'trouble_new_request',
                'skipped',
                (int) $request->id,
            );

            return;
        }

        $text = $this->buildAdminNewRequestText($request, $resident, $property);
        $messages = [['type' => 'text', 'text' => $text]];

        foreach ($admins as $admin) {
            $this->pushAndLog(
                (string) $admin->line_uid,
                $messages,
                'admin',
                (int) $admin->id,
                'trouble_new_request',
                (int) $request->id,
            );
        }
    }

    private function notifyAssignedVendor(TroubleRequest $request, Resident $resident, Property $property): void
    {
        if ($request->vendor_id === null) {
            return;
        }

        $vendor = $request->vendor;
        if ($vendor === null) {
            return;
        }

        $lineUid = $vendor->line_uid;
        if (! is_string($lineUid) || trim($lineUid) === '') {
            $this->notificationLogWriter->write(
                'vendor',
                (int) $vendor->id,
                'line_message',
                'vendor_dispatched',
                'skipped',
                (int) $request->id,
            );

            return;
        }

        $text = $this->buildVendorDispatchedText($request, $resident, $property, $vendor);
        $this->pushAndLog(
            trim($lineUid),
            [['type' => 'text', 'text' => $text]],
            'vendor',
            (int) $vendor->id,
            'vendor_dispatched',
            (int) $request->id,
        );
    }

    private function buildAdminNewRequestText(TroubleRequest $request, Resident $resident, Property $property): string
    {
        $categoryName = $request->category?->display_name ?? '—';
        $vendorName = $request->vendor?->name ?? '（未指定）';
        $pref = $request->preferred_date?->format('Y-m-d') ?? '—';
        $room = trim((string) $resident->room_number);
        $roomPart = $room !== '' ? "{$room}号室" : '—';

        return "【新規トラブル依頼】\n"
            ."依頼ID：{$request->id}\n"
            ."物件：{$property->name} {$roomPart}\n"
            ."種類：{$categoryName}\n"
            ."希望日：{$pref}\n"
            ."業者：{$vendorName}\n\n"
            .'管理画面から詳細を確認してください。';
    }

    private function buildVendorDispatchedText(
        TroubleRequest $request,
        Resident $resident,
        Property $property,
        Vendor $vendor,
    ): string {
        $categoryName = $request->category?->display_name ?? '—';
        $pref = $request->preferred_date?->format('Y-m-d') ?? '—';
        $room = trim((string) $resident->room_number);
        $roomPart = $room !== '' ? "{$room}号室" : '—';

        $description = trim((string) $request->description);
        $detailBlock = $description === ''
            ? '（記載なし）'
            : mb_strimwidth($description, 0, 1800, '…');

        $attachmentCount = $request->requestAttachments()->count();
        $attachLine = $attachmentCount > 0 ? "【添付】{$attachmentCount}件\n" : '';

        return "【修理依頼】\n"
            ."依頼ID：{$request->id}\n"
            ."物件：{$property->name} {$roomPart}\n"
            ."種類：{$categoryName}\n"
            ."希望日：{$pref}\n"
            ."担当業者：{$vendor->name}\n"
            .$attachLine
            ."【詳細】\n{$detailBlock}\n\n"
            .'管理画面で依頼の詳細・添付を確認できます。';
    }

    /**
     * 入居者向け：送信内容の要約＋受付完了の案内（LINE 文字数上限を避けるため詳細は mb_strimwidth で短縮）
     */
    private function buildResidentAcknowledgementText(TroubleRequest $request, Resident $resident, Property $property): string
    {
        $categoryLabel = $request->category?->display_name ?? '—';
        $prefLabel = $request->preferred_date?->format('Y-m-d') ?? '指定なし';
        $vendorLabel = $request->vendor?->name ?? '指定なし';

        $description = trim((string) $request->description);
        $detailSummary = $description === ''
            ? '（記載なし）'
            : mb_strimwidth($description, 0, 700, '…');

        $attachmentCount = $request->requestAttachments()->count();
        $attachmentLine = $attachmentCount > 0
            ? "【添付】{$attachmentCount}件\n"
            : '';

        $room = trim((string) $resident->room_number);
        $roomPart = $room !== '' ? "{$room}号室" : '—';

        return "送信ありがとうございます。以下の内容で受け付けました。\n\n"
            ."【物件】{$property->name} {$roomPart}\n"
            ."【種別】{$categoryLabel}\n"
            ."【希望日】{$prefLabel}\n"
            ."【担当業者】{$vendorLabel}\n"
            .$attachmentLine
            ."【詳細（要約）】\n{$detailSummary}\n\n"
            .'管理会社・業者からの連絡をお待ちください。';
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    private function pushAndLog(
        string $lineUid,
        array $messages,
        string $recipientType,
        int $recipientId,
        string $eventType,
        int $relatedId,
    ): void {
        $status = 'failed';
        try {
            $ok = $this->lineMessaging->pushToUser($lineUid, $messages, $eventType);
            $status = $ok ? 'success' : 'failed';
        } catch (\Throwable $e) {
            Log::error('LINE push failed', ['event' => $eventType, 'message' => $e->getMessage()]);
        }

        try {
            $this->notificationLogWriter->write(
                $recipientType,
                $recipientId,
                'line_message',
                $eventType,
                $status,
                $relatedId,
            );
        } catch (\Throwable $e) {
            Log::error('notification log write failed', ['message' => $e->getMessage()]);
        }
    }
}
