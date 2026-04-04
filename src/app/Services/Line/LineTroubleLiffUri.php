<?php

declare(strict_types=1);

namespace App\Services\Line;

/**
 * トラブル報告 LIFF を LINE 上で開くための URI（ボタンテンプレート用）
 */
final class LineTroubleLiffUri
{
    public static function openUri(): ?string
    {
        $id = config('services.line.liff_id_trouble');
        if (! is_string($id) || $id === '') {
            return null;
        }

        return 'https://liff.line.me/'.$id;
    }
}
