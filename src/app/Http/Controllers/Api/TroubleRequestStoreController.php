<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreTroubleRequest;
use App\Services\Api\TroubleSubmissionService;
use Illuminate\Http\JsonResponse;

final class TroubleRequestStoreController extends Controller
{
    public function __construct(
        private readonly TroubleSubmissionService $troubleSubmissionService,
    ) {}

    public function __invoke(StoreTroubleRequest $request): JsonResponse
    {
        $lineUid = $request->attributes->get('line_uid');
        if (! is_string($lineUid) || $lineUid === '') {
            return response()->json(['message' => __('認証情報が不正です。')], 401);
        }

        $validated = $request->validated();
        $validated['attachments'] = $request->attachmentsPayload();

        $tr = $this->troubleSubmissionService->submit($lineUid, $validated);

        return response()->json([
            'message' => __('受付が完了しました'),
            'request_id' => $tr->id,
        ], 201);
    }
}
