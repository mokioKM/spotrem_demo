<?php

declare(strict_types=1);

namespace App\Services\Invite;

use App\Models\AdminUser;
use App\Models\InvitationToken;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 招待トークン発行・LINE UID 紐づけ（基本設計 03）
 */
final class InvitationService
{
    public function paginateForAdmin(int $perPage): LengthAwarePaginator
    {
        return InvitationToken::query()
            ->with(['issuer', 'targetVendor', 'targetAdminUser'])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  'admin_user'|'vendor'  $role
     */
    public function issue(
        int $issuerAdminUserId,
        string $role,
        ?int $targetAdminUserId,
        ?int $targetVendorId,
    ): InvitationToken {
        if ($role === 'admin_user' && $targetAdminUserId === null) {
            throw new HttpException(422, __('担当者を選択してください。'));
        }
        if ($role === 'vendor' && $targetVendorId === null) {
            throw new HttpException(422, __('業者を選択してください。'));
        }
        if ($role === 'admin_user' && $targetVendorId !== null) {
            throw new HttpException(422, __('パラメータが不正です。'));
        }
        if ($role === 'vendor' && $targetAdminUserId !== null) {
            throw new HttpException(422, __('パラメータが不正です。'));
        }

        $tokenString = bin2hex(random_bytes(32));

        return InvitationToken::query()->create([
            'token' => $tokenString,
            'role' => $role,
            'issued_by' => $issuerAdminUserId,
            'target_vendor_id' => $role === 'vendor' ? $targetVendorId : null,
            'target_admin_user_id' => $role === 'admin_user' ? $targetAdminUserId : null,
            'expires_at' => now()->addHours(72),
            'is_used' => false,
        ]);
    }

    public function publicInviteUrl(string $token): string
    {
        return url('/invite?token='.$token);
    }

    /**
     * LIFF から呼ばれる登録処理（ID トークンで確定した line_uid のみ使用する）
     */
    public function registerLineUid(string $plainToken, string $lineUid): void
    {
        DB::transaction(function () use ($plainToken, $lineUid): void {
            /** @var InvitationToken|null $inv */
            $inv = InvitationToken::query()
                ->where('token', $plainToken)
                ->where('is_used', false)
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($inv === null) {
                throw new BadRequestHttpException(__('この招待URLは無効か、期限切れです。'));
            }

            if ($inv->role === 'admin_user') {
                $targetId = $inv->target_admin_user_id;
                if ($targetId === null) {
                    throw new HttpException(500, __('招待データが不正です。'));
                }
                if (AdminUser::query()->where('line_uid', $lineUid)->where('id', '!=', $targetId)->exists()) {
                    throw new ConflictHttpException(__('このLINEアカウントは既に別の担当者に連携済みです。'));
                }
                AdminUser::query()->whereKey($targetId)->update([
                    'line_uid' => $lineUid,
                    'updated_at' => now(),
                ]);
            } elseif ($inv->role === 'vendor') {
                $targetId = $inv->target_vendor_id;
                if ($targetId === null) {
                    throw new HttpException(500, __('招待データが不正です。'));
                }
                if (Vendor::query()->where('line_uid', $lineUid)->where('id', '!=', $targetId)->exists()) {
                    throw new ConflictHttpException(__('このLINEアカウントは既に別の業者に連携済みです。'));
                }
                Vendor::query()->whereKey($targetId)->update([
                    'line_uid' => $lineUid,
                    'updated_at' => now(),
                ]);
            } else {
                throw new HttpException(500, __('招待データが不正です。'));
            }

            $inv->forceFill([
                'is_used' => true,
                'used_at' => now(),
            ])->save();
        });
    }
}
