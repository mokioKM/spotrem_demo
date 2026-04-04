<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 基本設計書 SpotRem_DB設計 の roles 初期データ
        $roles = [
            [
                'name' => 'super_admin',
                'display_name' => 'スーパー管理者',
                'description' => null,
            ],
            [
                'name' => 'staff',
                'display_name' => '担当者',
                'description' => null,
            ],
        ];

        foreach ($roles as $row) {
            Role::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'display_name' => $row['display_name'],
                    'description' => $row['description'],
                ],
            );
        }
    }
}
