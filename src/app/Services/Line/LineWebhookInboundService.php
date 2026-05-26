<?php

declare(strict_types=1);

namespace App\Services\Line;

use App\Models\OptionBilling;
use App\Models\Resident;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Webhook で受け取ったイベントの処理（テキストは非対応案内、オプション請求の postback は感謝文を返信）
 */
final class LineWebhookInboundService
{
    private const UNSUPPORTED_TEXT_REPLY = "このトークでのテキストメッセージへの返信には対応しておりません。\nお困りの際は、画面下のメニューから「トラブル報告」などをご利用ください。";

    private const PAYMENT_THANK_YOU_REPLY = "入金のご連絡ありがとうございます。\n管理会社にて確認いたします。";

    public function __construct(
        private readonly LineMessagingService $lineMessaging,
    ) {}

    /**
     * @param  list<mixed>  $events
     */
    public function handleEvents(array $events): void
    {
        foreach ($events as $event) {
            if (! is_array($event)) {
                continue;
            }
            $this->handleOneEvent($event);
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handleOneEvent(array $event): void
    {
        $type = $event['type'] ?? null;
        if ($type === 'postback') {
            $this->handlePostbackEvent($event);

            return;
        }
        if ($type !== 'message') {
            return;
        }

        $message = $event['message'] ?? null;
        if (! is_array($message) || ($message['type'] ?? null) !== 'text') {
            return;
        }

        $replyToken = $event['replyToken'] ?? null;
        if (! is_string($replyToken) || $replyToken === '') {
            Log::warning('LINE webhook text message without replyToken', [
                'source' => $event['source'] ?? null,
            ]);

            return;
        }

        $ok = $this->lineMessaging->reply(
            $replyToken,
            [['type' => 'text', 'text' => self::UNSUPPORTED_TEXT_REPLY]],
            'webhook_text_unsupported',
        );

        if (! $ok) {
            Log::warning('LINE webhook auto-reply failed', [
                'event' => 'webhook_text_unsupported',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function handlePostbackEvent(array $event): void
    {
        $postback = $event['postback'] ?? null;
        if (! is_array($postback)) {
            return;
        }
        $data = $postback['data'] ?? null;
        if (! is_string($data)) {
            return;
        }

        $parsed = OptionInvoiceLinePostback::parsePaymentComplete($data);
        if (! $parsed['match']) {
            return;
        }

        $replyToken = $event['replyToken'] ?? null;
        if (! is_string($replyToken) || $replyToken === '') {
            Log::warning('LINE webhook postback without replyToken', [
                'source' => $event['source'] ?? null,
            ]);

            return;
        }

        $billingId = $parsed['billing_id'];
        $lineUid = $event['source']['userId'] ?? null;

        $this->markBillingPaidByResident($billingId, is_string($lineUid) ? $lineUid : null);

        $ok = $this->lineMessaging->reply(
            $replyToken,
            [['type' => 'text', 'text' => self::PAYMENT_THANK_YOU_REPLY]],
            'webhook_postback_option_invoice_payment_complete',
        );

        if (! $ok) {
            Log::warning('LINE webhook postback reply failed', [
                'event' => 'webhook_postback_option_invoice_payment_complete',
            ]);
        }
    }

    private function markBillingPaidByResident(?int $billingId, ?string $lineUid): void
    {
        if ($billingId !== null) {
            $billing = OptionBilling::find($billingId);
            if ($billing !== null && $billing->status !== 'paid') {
                DB::transaction(static function () use ($billing): void {
                    $billing->forceFill([
                        'status' => 'paid',
                        'paid_at' => now(),
                    ])->save();
                });
                Log::info('Option billing marked paid via LINE postback', ['billing_id' => $billingId]);
            }

            return;
        }

        if ($lineUid === null) {
            Log::warning('Option invoice payment postback: no billing_id and no lineUid');

            return;
        }

        $resident = Resident::where('line_uid', $lineUid)->first();
        if ($resident === null) {
            Log::warning('Option invoice payment postback: resident not found', ['line_uid' => $lineUid]);

            return;
        }

        $billing = OptionBilling::query()
            ->whereHas('optionContract', static fn ($q) => $q->where('resident_id', $resident->id))
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->first();

        if ($billing === null) {
            Log::info('Option invoice payment postback: no pending billing found', ['resident_id' => $resident->id]);

            return;
        }

        DB::transaction(static function () use ($billing): void {
            $billing->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
            ])->save();
        });
        Log::info('Option billing marked paid via LINE postback (fallback)', ['billing_id' => $billing->id]);
    }
}
