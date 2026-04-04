<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * LIFF から現在の入居者（アクティブ・line_uid 一致）を参照する
 */
final class CurrentResidentController extends Controller
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $lineUid = $request->attributes->get('line_uid');
        if (! is_string($lineUid) || $lineUid === '') {
            return response()->json(['message' => __('認証情報が不正です。')], 401);
        }

        $resident = $this->residentRepository->findActiveByLineUid($lineUid);
        if ($resident === null) {
            return response()->json(['message' => __('入居者登録を先に行ってください。')], 404);
        }

        $resident->load('property');

        return response()->json([
            'resident_id' => $resident->id,
            'name' => $resident->name,
            'age' => $resident->age,
            'room_number' => $resident->room_number,
            'phone' => $resident->phone,
            'property_id' => $resident->property_id,
            'property_name' => $resident->property?->name,
        ]);
    }
}
