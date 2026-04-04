<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminUser;
use App\Models\OptionBilling;
use App\Models\OptionContract;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * オプション契約の登録・更新と初回請求行（基本設計 04 の管理側）
 */
final class OptionContractAdminService
{
    public function __construct(
        private readonly OptionBillingAdminService $optionBillingAdminService,
    ) {}

    /**
     * @param  array{resident_id: int, name: string, amount: float|string, due_date: string, is_active?: bool}  $data
     */
    public function create(array $data, AdminUser $admin, ?UploadedFile $invoicePdf): OptionContract
    {
        return DB::transaction(function () use ($data, $admin, $invoicePdf): OptionContract {
            $due = Carbon::parse($data['due_date'])->startOfDay();
            $contract = OptionContract::query()->create([
                'resident_id' => (int) $data['resident_id'],
                'name' => $data['name'],
                'amount' => $data['amount'],
                'due_date' => $due->toDateString(),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $billing = OptionBilling::query()->create([
                'option_contract_id' => $contract->id,
                'billing_period' => $due->format('Y-m'),
                'due_date' => $due->toDateString(),
                'status' => 'pending',
            ]);

            if ($invoicePdf !== null) {
                $this->optionBillingAdminService->attachInvoicePdf($billing, $admin, $invoicePdf);
            }

            return $contract->load('resident');
        });
    }

    /**
     * @param  array{resident_id: int, name: string, amount: float|string, due_date: string, is_active?: bool}  $data
     */
    public function update(OptionContract $contract, array $data, AdminUser $admin, ?UploadedFile $invoicePdf): OptionContract
    {
        return DB::transaction(function () use ($contract, $data, $admin, $invoicePdf): OptionContract {
            $due = Carbon::parse($data['due_date'])->startOfDay();
            $contract->forceFill([
                'resident_id' => (int) $data['resident_id'],
                'name' => $data['name'],
                'amount' => $data['amount'],
                'due_date' => $due->toDateString(),
                'is_active' => (bool) ($data['is_active'] ?? true),
            ])->save();

            if ($invoicePdf !== null) {
                $billing = OptionBilling::query()
                    ->where('option_contract_id', $contract->id)
                    ->orderBy('billing_period')
                    ->first();
                if ($billing !== null) {
                    $this->optionBillingAdminService->attachInvoicePdf($billing, $admin, $invoicePdf);
                }
            }

            return $contract->refresh()->load('resident');
        });
    }
}
