<?php

declare(strict_types=1);

namespace App\Services\Line;

use App\Models\InvitationToken;
use App\Services\Invite\InvitationService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 公式 LINE トークへの「業者連携 / 担当者連携 {token}」テキストから UID を紐づける
 */
final class LineInvitationRegistrationFromTextService
{
    public function __construct(
        private readonly InvitationService $invitationService,
    ) {}

    /**
     * 連携コマンドにマッチした場合は返信文を返す。マッチしなければ null。
     */
    public function tryRegisterFromText(string $rawText, string $lineUid): ?string
    {
        $parsed = $this->parseCommand($rawText);
        if ($parsed === null) {
            return null;
        }

        $expectedRole = $parsed['expected_role'];
        $token = $parsed['token'];

        $inv = InvitationToken::query()->where('token', $token)->first();
        if ($inv !== null && $inv->role !== $expectedRole) {
            return $expectedRole === 'vendor'
                ? __('この連携コードは業者用です。担当者の方は「担当者連携」と送信してください。')
                : __('この連携コードは担当者用です。業者の方は「業者連携」と送信してください。');
        }

        try {
            $this->invitationService->registerLineUid($token, $lineUid);
        } catch (BadRequestHttpException|ConflictHttpException|HttpException $e) {
            return $e->getMessage() !== ''
                ? $e->getMessage()
                : __('連携に失敗しました。');
        }

        return $expectedRole === 'vendor'
            ? "業者LINE連携が完了しました。\n修理依頼の通知をこのトークで受け取れます。"
            : "担当者LINE連携が完了しました。\n新規トラブル依頼の通知をこのトークで受け取れます。";
    }

    /**
     * @return array{expected_role: 'vendor'|'admin_user', token: string}|null
     */
    private function parseCommand(string $rawText): ?array
    {
        $text = trim(preg_replace('/\s+/u', ' ', $rawText) ?? $rawText);
        if ($text === '') {
            return null;
        }

        $vendorPrefix = (string) config('services.line.vendor_line_registration_prefix', '業者連携');
        $adminPrefix = (string) config('services.line.admin_line_registration_prefix', '担当者連携');

        foreach ([
            ['prefix' => $vendorPrefix, 'role' => 'vendor'],
            ['prefix' => $adminPrefix, 'role' => 'admin_user'],
        ] as $item) {
            $prefix = $item['prefix'];
            if ($prefix === '') {
                continue;
            }
            $pattern = '/^'.preg_quote($prefix, '/').'\s+([0-9a-f]{64})$/iu';
            if (preg_match($pattern, $text, $m) === 1) {
                return [
                    'expected_role' => $item['role'],
                    'token' => strtolower($m[1]),
                ];
            }
        }

        return null;
    }
}
