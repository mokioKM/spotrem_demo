<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Media\CloudinaryDirectUploadSignatureService;
use Illuminate\Http\JsonResponse;

/**
 * LIFF から Cloudinary 直アップロード用の署名パラメータを返す（バイナリはサーバを経由しない）
 */
final class TroubleMediaUploadSignatureController extends Controller
{
    public function __invoke(CloudinaryDirectUploadSignatureService $signatureService): JsonResponse
    {
        return response()->json($signatureService->buildPayload());
    }
}
