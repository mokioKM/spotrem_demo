<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 管理画面ロール定義（権限の拡張は roles テーブルへの行追加で対応する）
 */
class Role extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'description',
    ];

    /**
     * @return HasMany<AdminUser, $this>
     */
    public function adminUsers(): HasMany
    {
        return $this->hasMany(AdminUser::class);
    }
}
