<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AdminUser;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * ローカル／Docker で管理画面を試すための初期管理者（本番では別途作成しこのシーダーは使わない想定）
 */
class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $superAdminRole = Role::query()->where('name', 'super_admin')->first();
        $staffRole = Role::query()->where('name', 'staff')->first();

        if ($superAdminRole === null || $staffRole === null) {
            $this->command?->warn('roles テーブルに super_admin / staff が無いため AdminUserSeeder をスキップします。先に RoleSeeder を実行してください。');

            return;
        }

        AdminUser::query()->updateOrCreate(
            ['email' => 'admin@example.com'],
            [
                'role_id' => $superAdminRole->id,
                'name' => 'スーパー管理者（開発）',
                'password_hash' => Hash::make('password'),
                'line_uid' => null,
                'is_active' => true,
            ],
        );

        AdminUser::query()->updateOrCreate(
            ['email' => 'staff@example.com'],
            [
                'role_id' => $staffRole->id,
                'name' => '担当者（開発）',
                'password_hash' => Hash::make('password'),
                'line_uid' => null,
                'is_active' => true,
            ],
        );
    }
}
