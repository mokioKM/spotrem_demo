<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\TroubleRequest;
use App\Services\Line\LineMessagingService;
use App\Services\Notification\NotificationLogWriter;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 管理画面からの依頼ステータス更新と通知（基本設計 05）
 */
final class TroubleRequestWorkflowService
{
    public function __construct(
        private readonly LineMessagingService $lineMessaging,
        private readonly NotificationLogWriter $notificationLogWriter,
    ) {}

    public function schedule(TroubleRequest $tr, CarbonInterface $scheduledAt): void
    {
        if ($tr->status !== 'pending') {
            throw new HttpException(400, __('日程確定は受付済みの依頼のみ可能です。'));
        }

        $tr->forceFill([
            'scheduled_at' => $scheduledAt,
            'status' => 'scheduled',
        ])->save();

        $tr->load(['resident', 'vendor', 'category', 'property']);
        $resident = $tr->resident;
        $property = $tr->property;
        if ($resident === null || $property === null) {
            return;
        }

        $vendorName = $tr->vendor?->name ?? '—';
        $when = $scheduledAt->timezone('Asia/Tokyo')->format('Y年n月j日（D）H:i');
        $text = "【修理日程のご案内】\n\nお問い合わせいただいたトラブルの対応日程が確定しました。\n\n担当業者：{$vendorName}\n訪問日時：{$when}\n\nご不明な点は管理会社までご連絡ください。";

        $this->pushResidentLog((string) $resident->line_uid, [['type' => 'text', 'text' => $text]], (int) $resident->id, (int) $tr->id);
    }

    public function markCompleted(TroubleRequest $tr): void
    {
        if ($tr->status !== 'scheduled') {
            throw new HttpException(400, __('対応完了にできるのは日程確定済みの依頼のみです。'));
        }

        $tr->forceFill(['status' => 'completed'])->save();
    }

    public function cancel(TroubleRequest $tr): void
    {
        if ($tr->status === 'completed') {
            throw new HttpException(400, __('対応完了済みはキャンセルできません。'));
        }

        $tr->forceFill(['status' => 'cancelled'])->save();
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     */
    private function pushResidentLog(string $lineUid, array $messages, int $residentId, int $relatedId): void
    {
        $status = 'failed';
        try {
            $ok = $this->lineMessaging->pushToUser($lineUid, $messages, 'schedule_confirmed');
            $status = $ok ? 'success' : 'failed';
        } catch (\Throwable $e) {
            Log::error('schedule_confirmed LINE failed', ['message' => $e->getMessage()]);
        }

        try {
            $this->notificationLogWriter->write(
                'resident',
                $residentId,
                'line_message',
                'schedule_confirmed',
                $status,
                $relatedId,
            );
        } catch (\Throwable $e) {
            Log::error('notification log failed', ['message' => $e->getMessage()]);
        }
    }
}
