<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 業者 × 対応地域（properties.region 文字列と照合）
 */
class VendorRegion extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'vendor_id',
        'region',
    ];

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }
}
