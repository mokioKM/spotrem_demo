<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * システム設定KV（主キーは key）
 */
class SystemSetting extends Model
{
    public const CREATED_AT = null;

    /** 管理会社グループ通知先（Messaging API の groupId / roomId 等） */
    public const KEY_NOTIFICATION_GROUP_LINE_UID = 'notification_group_line_uid';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'updated_at' => 'datetime',
        ];
    }

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $row = static::query()->find($key);

        return $row !== null && $row->value !== '' ? (string) $row->value : $default;
    }

    public static function putValue(string $key, string $value, ?string $description = null): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
                'updated_at' => now(),
            ],
        );
    }
}
