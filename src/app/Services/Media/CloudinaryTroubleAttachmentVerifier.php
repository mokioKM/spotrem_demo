<?php

declare(strict_types=1);

namespace App\Services\Media;

use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 直アップロード後にクライアントから渡された public_id / URL が自テナント・所定フォルダ由来かざっくり検証する
 * （完全な改ざん防止には Admin API での存在確認が望ましいが、LIFF 経路と署名フォルダで実務上の下限を担保する）
 */
final class CloudinaryTroubleAttachmentVerifier
{
    public function assertValid(string $publicId, string $url, string $fileType): void
    {
        $cloudName = config('services.cloudinary.cloud_name');
        if (! is_string($cloudName) || $cloudName === '') {
            throw new HttpException(503, __('アップロード検証の設定が不足しています。'));
        }
        $cloudName = strtolower(trim($cloudName));

        $folder = trim((string) config('services.cloudinary.trouble_upload_folder', 'spotrem/trouble'), '/');
        if ($folder === '') {
            $folder = 'spotrem/trouble';
        }

        $prefix = $folder.'/';
        if ($publicId !== $folder && ! str_starts_with($publicId, $prefix)) {
            Log::warning('Cloudinary attachment public_id folder mismatch', ['public_id' => $publicId]);

            throw new HttpException(422, __('添付ファイルの情報が不正です。'));
        }

        $host = parse_url($url, PHP_URL_HOST);
        if ($host !== 'res.cloudinary.com') {
            Log::warning('Cloudinary attachment url host mismatch', ['url' => $url]);

            throw new HttpException(422, __('添付ファイルの URL が不正です。'));
        }

        $path = (string) (parse_url($url, PHP_URL_PATH) ?? '');
        if (! str_contains(strtolower($path), '/'.$cloudName.'/')) {
            Log::warning('Cloudinary attachment url path mismatch', ['url' => $url]);

            throw new HttpException(422, __('添付ファイルの URL が不正です。'));
        }

        if ($fileType !== 'image' && $fileType !== 'video') {
            throw new HttpException(422, __('添付の種別が不正です。'));
        }
    }
}
