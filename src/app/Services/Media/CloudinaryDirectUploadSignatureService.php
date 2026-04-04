<?php

declare(strict_types=1);

namespace App\Services\Media;

use Cloudinary\Api\ApiUtils;
use Cloudinary\Utils;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * ブラウザ（LIFF）から Cloudinary へ直接 POST するための署名パラメータを生成する
 *
 * @see https://cloudinary.com/documentation/upload_images#generating_authentication_signatures
 */
final class CloudinaryDirectUploadSignatureService
{
    /**
     * @return array{cloud_name: string, api_key: string, timestamp: int, signature: string, folder: string, upload_url: string}
     */
    public function buildPayload(): array
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');
        if (! is_string($cloudName) || $cloudName === ''
            || ! is_string($apiKey) || $apiKey === ''
            || ! is_string($apiSecret) || $apiSecret === '') {
            Log::error('Cloudinary is not configured');

            throw new HttpException(503, __('アップロード機能の設定が不足しています。'));
        }

        // Upload API の URL では cloud name は小文字の識別子として扱われる（大文字混在だと Invalid cloud_name になり得る）
        $cloudName = strtolower(trim($cloudName));

        $folder = trim((string) config('services.cloudinary.trouble_upload_folder', 'spotrem/trouble'), '/');
        if ($folder === '') {
            $folder = 'spotrem/trouble';
        }

        // Configuration クラスは classmap 環境によっては解決されないことがあるため、
        // 署名のみ ApiUtils（Upload API と同一アルゴリズム）で生成する。
        $params = [
            'timestamp' => time(),
            'folder' => $folder,
        ];
        $params['signature'] = ApiUtils::signParameters($params, $apiSecret, Utils::ALGO_SHA1);
        $params['api_key'] = $apiKey;

        return [
            'cloud_name' => $cloudName,
            'api_key' => (string) $params['api_key'],
            'timestamp' => (int) $params['timestamp'],
            'signature' => (string) $params['signature'],
            'folder' => $folder,
            'upload_url' => sprintf('https://api.cloudinary.com/v1_1/%s/auto/upload', $cloudName),
        ];
    }
}
