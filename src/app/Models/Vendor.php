<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vendor extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'phone',
        'line_uid',
        'line_messaging_group_id',
        'google_calendar_id',
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
     * @return HasMany<TroubleRequest, $this>
     */
    public function troubleRequests(): HasMany
    {
        return $this->hasMany(TroubleRequest::class);
    }

    /**
     * @return HasMany<VendorGenre, $this>
     */
    public function vendorGenres(): HasMany
    {
        return $this->hasMany(VendorGenre::class);
    }

    /**
     * @return HasMany<VendorRegion, $this>
     */
    public function vendorRegions(): HasMany
    {
        return $this->hasMany(VendorRegion::class);
    }

    /**
     * 業者LINE UID登録用に紐づけられた招待トークン
     *
     * @return HasMany<InvitationToken, $this>
     */
    public function targetedInvitationTokens(): HasMany
    {
        return $this->hasMany(InvitationToken::class, 'target_vendor_id');
    }

    /**
     * @return BelongsToMany<TroubleCategory, $this, VendorGenre>
     */
    public function troubleCategories(): BelongsToMany
    {
        return $this->belongsToMany(TroubleCategory::class, 'vendor_genres', 'vendor_id', 'category_id')
            ->using(VendorGenre::class)
            ->withPivot('id');
    }
}
