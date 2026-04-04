<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationToken extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'token',
        'role',
        'issued_by',
        'target_vendor_id',
        'target_admin_user_id',
        'expires_at',
        'used_at',
        'is_used',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'is_used' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function issuer(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'issued_by');
    }

    /**
     * @return BelongsTo<Vendor, $this>
     */
    public function targetVendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class, 'target_vendor_id');
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function targetAdminUser(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'target_admin_user_id');
    }
}
