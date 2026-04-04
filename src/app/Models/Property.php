<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * 管理物件マスタ（region は業者の対応地域照合に使用）
 */
class Property extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'region',
        'room_count',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<Resident, $this>
     */
    public function residents(): HasMany
    {
        return $this->hasMany(Resident::class);
    }

    /**
     * 依頼に記録された物件（入居者の現在の property_id とは一致しない場合がある）
     *
     * @return HasMany<TroubleRequest, $this>
     */
    public function troubleRequests(): HasMany
    {
        return $this->hasMany(TroubleRequest::class);
    }
}
