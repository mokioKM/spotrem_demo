<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OptionBilling;
use App\Services\Api\OptionBillingPaidService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class OptionBillingPaidController extends Controller
{
    public function __construct(
        private readonly OptionBillingPaidService $optionBillingPaidService,
    ) {}

    public function __invoke(Request $request, OptionBilling $optionBilling): JsonResponse
    {
        $lineUid = $request->attributes->get('line_uid');
        if (! is_string($lineUid) || $lineUid === '') {
            return response()->json(['message' => __('認証情報が不正です。')], 401);
        }

        $this->optionBillingPaidService->markPaidByResidentLineUid($optionBilling, $lineUid);

        return response()->json(['message' => __('入金を受け付けました。ありがとうございます。')]);
    }
}
