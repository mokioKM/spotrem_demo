<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterInviteRequest;
use App\Services\Invite\InvitationService;
use Illuminate\Http\JsonResponse;

/**
 * POST /api/invite/register — Bearer の sub を line_uid として使用（body の line_uid は信頼しない）
 */
final class InviteRegisterController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {}

    public function __invoke(RegisterInviteRequest $request): JsonResponse
    {
        $lineUid = $request->attributes->get('line_uid');
        if (! is_string($lineUid) || $lineUid === '') {
            return response()->json(['message' => __('認証情報が不正です。')], 401);
        }

        $this->invitationService->registerLineUid($request->validated('token'), $lineUid);

        return response()->json(['message' => __('LINE連携が完了しました')]);
    }
}
