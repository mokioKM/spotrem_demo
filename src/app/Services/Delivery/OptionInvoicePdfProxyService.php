<?php

declare(strict_types=1);

namespace App\Services\Delivery;

use App\Models\OptionBilling;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Cloudinary 上の請求書 PDF を自アプリ経由で配信する
 *
 * Cloudinary raw は Content-Type が application/octet-stream で返るため、
 * 自サーバ経由で application/pdf を付けて配信する。
 */
final class OptionInvoicePdfProxyService
{
    public function streamedPdfResponse(OptionBilling $billing, string $dispositionType = HeaderUtils::DISPOSITION_INLINE): StreamedResponse
    {
        $source = $billing->invoice_pdf_url;
        if (! is_string($source) || trim($source) === '') {
            throw new HttpException(404);
        }

        $filename = $billing->invoice_pdf_filename;
        if (! is_string($filename) || trim($filename) === '') {
            $filename = 'invoice.pdf';
        }

        $asciiFallback = preg_replace('/[^\x20-\x7E]+/', '_', $filename);
        if ($asciiFallback === '' || $asciiFallback === null) {
            $asciiFallback = 'invoice.pdf';
        }
        $disposition = HeaderUtils::makeDisposition(
            $dispositionType,
            $filename,
            $asciiFallback,
        );

        $upstream = Http::withOptions(['stream' => true, 'timeout' => 120])->get($source);
        if (! $upstream->successful()) {
            Log::error('option invoice proxy: upstream GET failed', [
                'billing_id' => $billing->id,
                'status' => $upstream->status(),
            ]);
            throw new HttpException(502, __('請求書の取得に失敗しました。'));
        }

        return response()->stream(function () use ($upstream): void {
            $body = $upstream->toPsrResponse()->getBody();
            while (! $body->eof()) {
                $chunk = $body->read(65536);
                if ($chunk === '') {
                    break;
                }
                echo $chunk;
            }
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
