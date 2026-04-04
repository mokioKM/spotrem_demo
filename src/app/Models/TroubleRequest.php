<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * トラブル依頼本体
 *
 * property_id は residents.property_id から導出可能だが、退去後に入居者の物件が変わっても
 * 依頼時点の物件を保持するため非正規化カラムとして保存する（リレーションは property() で参照）
 */
class TroubleRequest extends Model
{
    protected $fillable = [
        'resident_id',
        'property_id',
        'category_id',
        'vendor_id',
        'description',
        'preferred_date',
        'scheduled_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'preferred_date' => 'date',
            'scheduled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Resident, $this>
     */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(Resident::class);
    }

    /**
     * 依頼作成時点の物件（入居者の現在の property と必ずしも一致しない）
     *
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return BelongsTo<TroubleCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TroubleCategory::class, 'category_id');
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return HasMany<RequestAttachment, $this>
     */
    public function requestAttachments(): HasMany
    {
        return $this->hasMany(RequestAttachment::class, 'request_id');
    }
}
