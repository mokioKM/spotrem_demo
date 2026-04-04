<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\OptionBilling;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 入居者（LIFF）からの入金報告（API）
 */
final class OptionBillingPaidService
{
    public function markPaidByResidentLineUid(OptionBilling $billing, string $lineUid): void
    {
        $billing->loadMissing('optionContract.resident');
        $resident = $billing->optionContract?->resident;
        if ($resident === null || ! $resident->is_active) {
            throw new HttpException(403, __('この請求にアクセスできません。'));
        }

        if ((string) $resident->line_uid !== $lineUid) {
            throw new HttpException(403, __('この請求にアクセスできません。'));
        }

        if ($billing->status === 'paid') {
            throw new HttpException(409, __('既に入金済みとして登録されています。'));
        }

        DB::transaction(function () use ($billing): void {
            $billing->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
            ])->save();
        });
    }
}
