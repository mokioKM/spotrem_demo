<?php

declare(strict_types=1);

namespace App\Services\Line;

/**
 * オプション請求の LINE ボタン postback で送る data 値（Webhook 側と送信側で共有）
 */
final class OptionInvoiceLinePostback
{
    public const PAYMENT_COMPLETE = 'option_invoice_payment_complete';
}
