<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\InvitationToken;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * ブラウザで招待 URL を開いたときの案内（LIFF 側で API を呼ぶ前提）
 */
final class InvitePageController extends Controller
{
    public function show(Request $request): View
    {
        $plain = $request->query('token', '');
        if (! is_string($plain) || $plain === '') {
            return view('invite.invalid', ['reason' => __('トークンがありません。')]);
        }

        $row = InvitationToken::query()->where('token', $plain)->first();
        if ($row === null) {
            return view('invite.invalid', ['reason' => __('無効なURLです。')]);
        }

        if ($row->is_used) {
            return view('invite.invalid', ['reason' => __('このURLは既に使用済みです。')]);
        }

        if ($row->expires_at->isPast()) {
            return view('invite.invalid', ['reason' => __('有効期限が切れています。担当者に再発行を依頼してください。')]);
        }

        return view('invite.valid', ['token' => $plain]);
    }
}
