<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\OptionBilling;
use App\Services\Line\LineMessagingService;
use App\Services\Notification\NotificationLogWriter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * オプション請求のリマインド（期限7日以内・未送信・未払い）
 */
class OptionReminderCommand extends Command
{
    protected $signature = 'spotrem:option-reminder';

    protected $description = 'Send LINE reminders for option billings due within 7 days';

    public function __construct(
        private readonly LineMessagingService $lineMessaging,
        private readonly NotificationLogWriter $notificationLogWriter,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $today = now()->startOfDay();
        $until = $today->copy()->addDays(7);

        $rows = OptionBilling::query()
            ->with(['optionContract.resident'])
            ->where('status', 'pending')
            ->whereNull('reminder_sent_at')
            ->whereDate('due_date', '>=', $today->toDateString())
            ->whereDate('due_date', '<=', $until->toDateString())
            ->get();

        $sent = 0;
        foreach ($rows as $billing) {
            $contract = $billing->optionContract;
            $resident = $contract?->resident;
            if ($contract === null || $resident === null || ! $resident->is_active) {
                continue;
            }

            $lineUid = (string) $resident->line_uid;
            if ($lineUid === '') {
                $this->logSkip((int) $billing->id, (int) $resident->id, 'no_line_uid');
                continue;
            }

            $amount = number_format((float) $contract->amount, 0);
            $due = $billing->due_date?->format('Y-m-d') ?? '';
            $text = "【オプション料金のお知らせ】\n{$contract->name}\n請求期間: {$billing->billing_period}\n金額: {$amount}円（税込の場合は表記に従ってください）\nお支払い期限: {$due}\n\n明細はLINEまたは管理会社までお問い合わせください。";

            $ok = false;
            try {
                $ok = $this->lineMessaging->pushToUser($lineUid, [['type' => 'text', 'text' => $text]], 'option_billing_reminder');
            } catch (\Throwable $e) {
                Log::error('option reminder LINE failed', ['billing_id' => $billing->id, 'message' => $e->getMessage()]);
            }

            try {
                $this->notificationLogWriter->write(
                    'resident',
                    (int) $resident->id,
                    'line_message',
                    'option_billing_reminder',
                    $ok ? 'success' : 'failed',
                    (int) $billing->id,
                );
            } catch (\Throwable $e) {
                Log::error('option reminder log failed', ['message' => $e->getMessage()]);
            }

            if ($ok) {
                $billing->forceFill(['reminder_sent_at' => now()])->save();
                $sent++;
            }
        }

        $this->info("Reminders attempted: {$rows->count()}, marked sent: {$sent}");

        return self::SUCCESS;
    }

    private function logSkip(int $billingId, int $residentId, string $reason): void
    {
        try {
            $this->notificationLogWriter->write(
                'resident',
                $residentId,
                'line_message',
                'option_billing_reminder',
                'skipped',
                $billingId,
            );
        } catch (\Throwable) {
            // ログ失敗は握りつぶす（バッチ継続）
        }
    }
}
