<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\TroubleCategory;
use Illuminate\Database\Seeder;

class TroubleCategorySeeder extends Seeder
{
    public function run(): void
    {
        // 基本設計書 SpotRem_DB設計 の trouble_categories 初期データ
        // name は内部識別子として設計例（key_lost）に合わせたスネークケースを付与
        $categories = [
            [
                'name' => 'water_leak_drainage',
                'display_name' => '水漏れ・排水',
                'show_phone_number' => false,
                'emergency_phone' => null,
                'sort_order' => 1,
            ],
            [
                'name' => 'electrical_lighting',
                'display_name' => '電気・照明',
                'show_phone_number' => false,
                'emergency_phone' => null,
                'sort_order' => 2,
            ],
            [
                'name' => 'ac_ventilation',
                'display_name' => 'エアコン・換気扇',
                'show_phone_number' => false,
                'emergency_phone' => null,
                'sort_order' => 3,
            ],
            [
                'name' => 'key_lost',
                'display_name' => '鍵の紛失・故障',
                'show_phone_number' => true,
                'emergency_phone' => null,
                'sort_order' => 4,
            ],
            [
                'name' => 'other',
                'display_name' => 'その他',
                'show_phone_number' => false,
                'emergency_phone' => null,
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $row) {
            TroubleCategory::query()->updateOrCreate(
                ['name' => $row['name']],
                [
                    'display_name' => $row['display_name'],
                    'show_phone_number' => $row['show_phone_number'],
                    'emergency_phone' => $row['emergency_phone'],
                    'sort_order' => $row['sort_order'],
                    'is_active' => true,
                ],
            );
        }
    }
}
