<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\OptionBilling;
use App\Services\Delivery\OptionInvoicePdfProxyService;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * 入居者向け：署名付き URL から請求書 PDF を配信（LINE 送信用リンクの宛先）
 */
final class PublicOptionInvoiceController extends Controller
{
    public function show(OptionBilling $optionBilling, OptionInvoicePdfProxyService $proxy): StreamedResponse
    {
        return $proxy->streamedPdfResponse($optionBilling);
    }
}
