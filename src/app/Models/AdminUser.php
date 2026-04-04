<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * 管理画面ログインアカウント（パスワード列は設計上 password_hash。Laravel 認証は getAuthPassword で吸収する）
 */
class AdminUser extends Model implements AuthenticatableContract
{
    use Authenticatable;

    protected $hidden = [
        'password_hash',
    ];

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'password_hash',
        'line_uid',
        'is_active',
        'last_login_at',
    ];

    /**
     * remember_token 列が無いため remember me は使用しない（ログイン時も remember=false 固定）
     */
    public function getRememberTokenName(): ?string
    {
        return null;
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isSuperAdmin(): bool
    {
        if (! $this->relationLoaded('role')) {
            $this->load('role');
        }

        return $this->role?->name === 'super_admin';
    }

    /**
     * @return BelongsTo<Role, $this>
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * この担当者が発行した招待トークン
     *
     * @return HasMany<InvitationToken, $this>
     */
    public function issuedInvitationTokens(): HasMany
    {
        return $this->hasMany(InvitationToken::class, 'issued_by');
    }

    /**
     * 担当者LINE UID登録用に紐づけられた招待トークン
     *
     * @return HasMany<InvitationToken, $this>
     */
    public function targetedInvitationTokens(): HasMany
    {
        return $this->hasMany(InvitationToken::class, 'target_admin_user_id');
    }

    /**
     * @return HasMany<OptionBilling, $this>
     */
    public function invoiceUploadedBillings(): HasMany
    {
        return $this->hasMany(OptionBilling::class, 'invoice_uploaded_by');
    }

    /**
     * @return HasMany<OptionBilling, $this>
     */
    public function confirmedBillings(): HasMany
    {
        return $this->hasMany(OptionBilling::class, 'confirmed_by');
    }
}
