<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 入居者（LINE UID）。退去後も依頼履歴は trouble_requests 側の property_id で参照する
 */
class Resident extends Model
{
    protected $fillable = [
        'property_id',
        'line_uid',
        'name',
        'age',
        'room_number',
        'phone',
        'registered_at',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Property, $this>
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * @return HasMany<TroubleRequest, $this>
     */
    public function troubleRequests(): HasMany
    {
        return $this->hasMany(TroubleRequest::class);
    }

    /**
     * @return HasMany<OptionContract, $this>
     */
    public function optionContracts(): HasMany
    {
        return $this->hasMany(OptionContract::class);
    }
}
