<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * 業者 × 困りごとカテゴリ（中間。複合ユニークはマイグレーション側）
 */
class VendorGenre extends Pivot
{
    public $incrementing = true;

    public $timestamps = false;

    protected $table = 'vendor_genres';

    protected $fillable = [
        'vendor_id',
        'category_id',
    ];

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * @return BelongsTo<TroubleCategory, $this>
     */
    public function troubleCategory(): BelongsTo
    {
        return $this->belongsTo(TroubleCategory::class, 'category_id');
    }
}
