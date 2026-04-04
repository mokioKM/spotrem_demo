<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreResidentRequest;
use App\Http\Requests\Api\UpdateResidentProfileRequest;
use App\Services\Api\ResidentProfileUpdateService;
use App\Services\Api\ResidentRegistrationService;
use Illuminate\Http\JsonResponse;

final class ResidentController extends Controller
{
    public function store(StoreResidentRequest $request, ResidentRegistrationService $service): JsonResponse
    {
        $lineUid = $request->attributes->get('line_uid');
        if (! is_string($lineUid) || $lineUid === '') {
            return response()->json(['message' => __('認証情報が不正です。')], 401);
        }

        $resident = $service->register($lineUid, $request->validated());

        return response()->json([
            'message' => __('登録が完了しました'),
            'resident_id' => $resident->id,
        ], 201);
    }

    public function update(UpdateResidentProfileRequest $request, ResidentProfileUpdateService $service): JsonResponse
    {
        $lineUid = $request->attributes->get('line_uid');
        if (! is_string($lineUid) || $lineUid === '') {
            return response()->json(['message' => __('認証情報が不正です。')], 401);
        }

        $resident = $service->update($lineUid, $request->validated());

        return response()->json([
            'message' => __('プロフィールを更新しました'),
            'resident_id' => $resident->id,
        ]);
    }
}
