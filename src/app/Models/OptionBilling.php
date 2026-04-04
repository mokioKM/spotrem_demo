<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OptionBilling extends Model
{
    protected $fillable = [
        'option_contract_id',
        'billing_period',
        'due_date',
        'invoice_pdf_url',
        'invoice_pdf_filename',
        'invoice_uploaded_by',
        'invoice_uploaded_at',
        'reminder_sent_at',
        'paid_at',
        'confirmed_by',
        'confirmed_at',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'invoice_uploaded_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'paid_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<OptionContract, $this>
     */
    public function optionContract(): BelongsTo
    {
        return $this->belongsTo(OptionContract::class);
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function invoiceUploader(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'invoice_uploaded_by');
    }

    /**
     * @return BelongsTo<AdminUser, $this>
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'confirmed_by');
    }
}
