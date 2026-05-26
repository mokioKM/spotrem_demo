<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\OptionBilling;
use App\Models\OptionContract;
use App\Services\Line\LineMessagingService;
use App\Services\Line\OptionInvoiceLinePostback;
use App\Services\Notification\NotificationLogWriter;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * デモ用：オプション契約の概要と請求書 PDF リンクを入居者の LINE にプッシュする
 */
final class OptionContractResidentNotifyService
{
    public function __construct(
        private readonly LineMessagingService $lineMessaging,
        private readonly NotificationLogWriter $notificationLogWriter,
    ) {}

    public function sendDemoInvoiceAndSummary(OptionContract $contract): void
    {
        $contract->loadMissing(['resident.property', 'optionBillings']);

        $resident = $contract->resident;
        if ($resident === null || ! $resident->is_active) {
            throw new HttpException(422, __('入居者が見つからないか無効です。'));
        }

        $lineUid = $resident->line_uid;
        if (! is_string($lineUid) || $lineUid === '') {
            throw new HttpException(422, __('入居者の LINE が未連携のため送信できません。'));
        }

        /** @var OptionBilling|null $billing */
        $billing = $contract->optionBillings
            ->filter(static fn (OptionBilling $b): bool => is_string($b->invoice_pdf_url) && trim($b->invoice_pdf_url) !== '')
            ->sortByDesc(static fn (OptionBilling $b): int => (int) $b->id)
            ->first();

        if ($billing === null || $billing->invoice_pdf_url === null || trim($billing->invoice_pdf_url) === '') {
            throw new HttpException(422, __('請求書 PDF が未登録です。先にアップロードしてください。'));
        }

        // LINE 内蔵ブラウザは Cloudinary の raw URL だと白画面になりやすいため、
        // 自サーバで application/pdf を付けて返す署名付き URL を送る（APP_URL は外向きに届くこと）。
        $pdfUrl = URL::temporarySignedRoute(
            'public.option-invoices.show',
            now()->addMonths(6),
            ['optionBilling' => $billing->id],
        );
        $amount = number_format((float) $contract->amount, 0, '.', ',');
        $due = $contract->due_date?->timezone('Asia/Tokyo')->format('Y-m-d') ?? '—';

        $text = "【オプション請求のご案内】\n\n"
            ."契約：{$contract->name}\n"
            ."金額：¥{$amount}\n"
            ."支払期限（契約ベース）：{$due}\n"
            ."請求期間：{$billing->billing_period}\n\n"
            ."請求書PDF（タップで開けます）\n{$pdfUrl}\n\n"
            ."※白い画面のときは、右上の「⋯」などから「ブラウザで開く」をお試しください。\n\n"
            .'ご不明点は管理会社までお問い合わせください。';

        $paymentPrompt = [
            'type' => 'template',
            'altText' => 'お支払いが完了した方は「入金完了」をタップしてください',
            'template' => [
                'type' => 'buttons',
                'title' => '請求に関するお知らせ',
                'text' => 'お支払い完了後、下のボタンをタップしてください。',
                'actions' => [
                    [
                        'type' => 'postback',
                        'label' => '入金完了',
                        'data' => OptionInvoiceLinePostback::buildPaymentComplete((int) $billing->id),
                    ],
                ],
            ],
        ];

        $status = 'failed';
        try {
            $ok = $this->lineMessaging->pushToUser(
                $lineUid,
                [
                    ['type' => 'text', 'text' => $text],
                    $paymentPrompt,
                ],
                'option_invoice_demo',
            );
            $status = $ok ? 'success' : 'failed';
        } catch (\Throwable $e) {
            Log::error('LINE push failed (option invoice demo)', ['message' => $e->getMessage()]);
        }

        try {
            $this->notificationLogWriter->write(
                'resident',
                (int) $resident->id,
                'line_message',
                'option_invoice_demo',
                $status,
                (int) $contract->id,
            );
        } catch (\Throwable $e) {
            Log::error('notification_logs write failed (option invoice demo)', ['message' => $e->getMessage()]);
        }

        if ($status !== 'success') {
            throw new HttpException(502, __('LINE 送信に失敗しました。トークン設定とログを確認してください。'));
        }
    }
}
