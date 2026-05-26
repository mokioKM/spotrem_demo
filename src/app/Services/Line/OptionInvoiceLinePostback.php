<?php

declare(strict_types=1);

namespace App\Services\Line;

/**
 * オプション請求の LINE ボタン postback で送る data 値（Webhook 側と送信側で共有）
 */
final class OptionInvoiceLinePostback
{
    public const PREFIX_PAYMENT_COMPLETE = 'option_invoice_payment_complete';

    /** @deprecated 後方互換用 — billing ID 無しの旧定数 */
    public const PAYMENT_COMPLETE = self::PREFIX_PAYMENT_COMPLETE;

    public static function buildPaymentComplete(int $billingId): string
    {
        return self::PREFIX_PAYMENT_COMPLETE . ':' . $billingId;
    }

    /**
     * postback data が入金完了か判定し、billing ID を返す（旧形式は null）
     *
     * @return array{match: bool, billing_id: int|null}
     */
    public static function parsePaymentComplete(string $data): array
    {
        if ($data === self::PREFIX_PAYMENT_COMPLETE) {
            return ['match' => true, 'billing_id' => null];
        }

        if (str_starts_with($data, self::PREFIX_PAYMENT_COMPLETE . ':')) {
            $id = (int) substr($data, strlen(self::PREFIX_PAYMENT_COMPLETE) + 1);

            return ['match' => true, 'billing_id' => $id > 0 ? $id : null];
        }

        return ['match' => false, 'billing_id' => null];
    }
}
