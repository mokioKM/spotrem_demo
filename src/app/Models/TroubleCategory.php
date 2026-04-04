<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 困りごとカテゴリマスタ（vendor_genres で業者と多対多）
 */
class TroubleCategory extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'name',
        'display_name',
        'show_phone_number',
        'emergency_phone',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'show_phone_number' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<TroubleRequest, $this>
     */
    public function troubleRequests(): HasMany
    {
        return $this->hasMany(TroubleRequest::class, 'category_id');
    }

    /**
     * @return HasMany<VendorGenre, $this>
     */
    public function vendorGenres(): HasMany
    {
        return $this->hasMany(VendorGenre::class, 'category_id');
    }

    /**
     * @return BelongsToMany<Vendor, $this, VendorGenre>
     */
    public function vendors(): BelongsToMany
    {
        return $this->belongsToMany(Vendor::class, 'vendor_genres', 'category_id', 'vendor_id')
            ->using(VendorGenre::class)
            ->withPivot('id');
    }
}
