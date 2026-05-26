<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AttachOptionBillingInvoiceRequest;
use App\Models\OptionBilling;
use App\Services\Admin\OptionBillingAdminService;
use App\Services\Delivery\OptionInvoicePdfProxyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class OptionBillingController extends Controller
{
    public function __construct(
        private readonly OptionBillingAdminService $optionBillingAdminService,
    ) {}

    public function showInvoicePdf(OptionBilling $optionBilling, OptionInvoicePdfProxyService $proxy): StreamedResponse
    {
        return $proxy->streamedPdfResponse($optionBilling, HeaderUtils::DISPOSITION_ATTACHMENT);
    }

    public function attachInvoice(AttachOptionBillingInvoiceRequest $request, OptionBilling $optionBilling): RedirectResponse
    {
        /** @var \App\Models\AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        $this->optionBillingAdminService->attachInvoicePdf($optionBilling, $admin, $request->file('invoice_pdf'));

        return redirect()
            ->route('admin.option-contracts.index')
            ->with('status', __('請求書PDFをアップロードしました。'));
    }

    public function confirmPaid(OptionBilling $optionBilling): RedirectResponse
    {
        /** @var \App\Models\AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        $this->optionBillingAdminService->markPaidByAdmin($optionBilling, $admin);

        return redirect()
            ->route('admin.option-contracts.index')
            ->with('status', __('入金確認を登録しました。'));
    }
}
