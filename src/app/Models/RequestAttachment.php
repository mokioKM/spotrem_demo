<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 依頼添付（Cloudinary の public_id / URL を保持）
 */
class RequestAttachment extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'request_id',
        'cloudinary_public_id',
        'file_type',
        'url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TroubleRequest, $this>
     */
    public function troubleRequest(): BelongsTo
    {
        return $this->belongsTo(TroubleRequest::class, 'request_id');
    }
}
