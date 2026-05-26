<?php

declare(strict_types=1);

namespace App\Services\Media;

use Cloudinary\Api\Exception\ApiError as CloudinaryApiError;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * オプション請求書 PDF を Cloudinary に raw として保存する（サーバー経由アップロード）
 *
 * 画像・動画と分離し、フォルダも請求専用にすることで管理しやすくする。
 */
class CloudinaryOptionInvoiceUploadService
{
    /**
     * @return array{secure_url: string, public_id: string}
     */
    public function uploadPdf(UploadedFile $file): array
    {
        $cloudName = config('services.cloudinary.cloud_name');
        $apiKey = config('services.cloudinary.api_key');
        $apiSecret = config('services.cloudinary.api_secret');
        if (! is_string($cloudName) || $cloudName === ''
            || ! is_string($apiKey) || $apiKey === ''
            || ! is_string($apiSecret) || $apiSecret === '') {
            Log::error('Cloudinary is not configured for invoice upload');

            throw new HttpException(503, __('請求書アップロードの設定が不足しています。'));
        }

        $cloudName = strtolower(trim($cloudName));
        $folder = trim((string) config('services.cloudinary.invoice_upload_folder', 'spotrem/invoices'), '/');
        if ($folder === '') {
            $folder = 'spotrem/invoices';
        }

        $path = $file->getRealPath();
        if ($path === false || ! is_file($path)) {
            $path = $file->getPathname();
        }

        $cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => $cloudName,
                'api_key' => $apiKey,
                'api_secret' => $apiSecret,
            ],
        ]);

        // 一時ファイルは拡張子が無いことが多く、use_filename だと Cloudinary 側の推測で形式がずれる。
        // raw のまま .pdf で配信されるよう、フォルダ配下の public_id を明示的に *.pdf で付与する。
        $publicIdBasename = $this->buildRawPublicIdBasename($file);

        try {
            $result = $cloudinary->uploadApi()->upload(fopen($path, 'rb'), [
                'resource_type' => 'raw',
                'folder' => $folder,
                'public_id' => $publicIdBasename,
                'use_filename' => false,
                'unique_filename' => false,
            ]);
        } catch (CloudinaryApiError $e) {
            Log::error('Cloudinary invoice upload failed', ['message' => $e->getMessage()]);

            throw new HttpException(502, __('請求書のアップロードに失敗しました。しばらくしてから再度お試しください。'));
        }

        $secureUrl = $result['secure_url'] ?? null;
        $publicId = $result['public_id'] ?? null;
        if (! is_string($secureUrl) || $secureUrl === '' || ! is_string($publicId) || $publicId === '') {
            Log::error('Cloudinary invoice upload returned unexpected payload', ['keys' => array_keys($result ?? [])]);

            throw new HttpException(502, __('請求書のアップロード結果が不正です。'));
        }

        return [
            'secure_url' => $secureUrl,
            'public_id' => $publicId,
        ];
    }

    /**
     * raw アップロード用の public_id（フォルダ直下のファイル名部分）。末尾は必ず .pdf とする。
     */
    private function buildRawPublicIdBasename(UploadedFile $file): string
    {
        $original = $file->getClientOriginalName();
        $stem = pathinfo($original, PATHINFO_FILENAME);
        $stem = is_string($stem) ? trim($stem) : '';
        if ($stem === '') {
            $stem = 'invoice';
        }

        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $stem);
        if ($converted === false) {
            $converted = '';
        }
        $slug = preg_replace('/[^a-zA-Z0-9._-]+/', '_', $converted);
        $slug = trim((string) $slug, '._-');
        if ($slug === '') {
            $slug = 'invoice';
        }
        if (strlen($slug) > 72) {
            $slug = substr($slug, 0, 72);
        }

        $uniq = gmdate('Ymd_His').'_'.bin2hex(random_bytes(4));

        return $slug.'_'.$uniq.'.pdf';
    }
}
