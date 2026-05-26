<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminUser;
use App\Models\OptionBilling;
use App\Services\Media\CloudinaryOptionInvoiceUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * 請求書 PDF（Cloudinary）の登録・管理者による入金確認（基本設計 04）
 */
final class OptionBillingAdminService
{
    public function __construct(
        private readonly CloudinaryOptionInvoiceUploadService $invoiceUploadService,
    ) {}

    /**
     * 管理画面からアップロードした PDF を Cloudinary（raw）に保存し、請求行に紐づける
     */
    public function attachInvoicePdf(OptionBilling $billing, AdminUser $admin, UploadedFile $file): void
    {
        $uploaded = $this->invoiceUploadService->uploadPdf($file);
        $originalName = $file->getClientOriginalName();
        $safeName = $originalName !== '' ? mb_substr($originalName, 0, 255) : null;

        DB::transaction(function () use ($billing, $admin, $uploaded, $safeName): void {
            $billing->forceFill([
                'invoice_pdf_url' => $uploaded['secure_url'],
                'invoice_pdf_filename' => $safeName,
                'invoice_uploaded_by' => $admin->id,
                'invoice_uploaded_at' => now(),
            ])->save();
        });
    }

    public function markPaidByAdmin(OptionBilling $billing, AdminUser $admin): void
    {
        if ($billing->status === 'confirmed') {
            throw new HttpException(400, __('既に入金確認済みです。'));
        }

        DB::transaction(function () use ($billing, $admin): void {
            $data = [
                'status' => 'confirmed',
                'confirmed_by' => $admin->id,
                'confirmed_at' => now(),
            ];
            if ($billing->paid_at === null) {
                $data['paid_at'] = now();
            }
            $billing->forceFill($data)->save();
        });
    }
}
