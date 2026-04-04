<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateNotificationGroupSettingRequest;
use App\Models\SystemSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class SystemSettingController extends Controller
{
    public function edit(): View
    {
        $uid = SystemSetting::getValue(SystemSetting::KEY_NOTIFICATION_GROUP_LINE_UID, '') ?? '';

        return view('admin.settings.notification_group', [
            'notification_group_line_uid' => $uid,
        ]);
    }

    public function update(UpdateNotificationGroupSettingRequest $request): RedirectResponse
    {
        $v = $request->validated('notification_group_line_uid');
        SystemSetting::putValue(
            SystemSetting::KEY_NOTIFICATION_GROUP_LINE_UID,
            $v !== null ? trim($v) : '',
            __('管理会社LINEグループ（トラブル新規依頼などの通知先）'),
        );

        return redirect()
            ->route('admin.settings.notification-group')
            ->with('status', __('保存しました。'));
    }
}
