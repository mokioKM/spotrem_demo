<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    public function run(): void
    {
        if (SystemSetting::query()->find(SystemSetting::KEY_NOTIFICATION_GROUP_LINE_UID) === null) {
            SystemSetting::putValue(
                SystemSetting::KEY_NOTIFICATION_GROUP_LINE_UID,
                '',
                __('管理会社LINEグループ（トラブル新規依頼などの通知先）'),
            );
        }
    }
}
