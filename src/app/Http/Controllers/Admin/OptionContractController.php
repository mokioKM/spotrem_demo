<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreOptionContractRequest;
use App\Http\Requests\Admin\UpdateOptionContractRequest;
use App\Models\OptionContract;
use App\Models\Resident;
use App\Services\Admin\OptionContractAdminService;
use App\Services\Admin\OptionContractResidentNotifyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

final class OptionContractController extends Controller
{
    public function __construct(
        private readonly OptionContractAdminService $optionContractAdminService,
        private readonly OptionContractResidentNotifyService $optionContractResidentNotifyService,
    ) {}

    public function index(): View
    {
        $contracts = OptionContract::query()
            ->with(['resident.property', 'optionBillings'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.option_contracts.index', ['contracts' => $contracts]);
    }

    public function create(): View
    {
        $residents = Resident::query()
            ->where('is_active', true)
            ->with('property')
            ->orderBy('name')
            ->get();

        return view('admin.option_contracts.create', ['residents' => $residents]);
    }

    public function store(StoreOptionContractRequest $request): RedirectResponse
    {
        /** @var \App\Models\AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        $data = $request->validated();
        unset($data['invoice_pdf']);
        $contract = $this->optionContractAdminService->create(
            $data,
            $admin,
            $request->file('invoice_pdf'),
        );

        return redirect()
            ->route('admin.option-contracts.index')
            ->with('status', __('契約を登録しました。'));
    }

    public function edit(OptionContract $optionContract): View
    {
        $optionContract->load([
            'resident.property',
            'optionBillings' => static fn ($q) => $q->orderByDesc('billing_period'),
        ]);
        $residents = Resident::query()
            ->where('is_active', true)
            ->with('property')
            ->orderBy('name')
            ->get();

        return view('admin.option_contracts.edit', [
            'contract' => $optionContract,
            'residents' => $residents,
        ]);
    }

    public function update(UpdateOptionContractRequest $request, OptionContract $optionContract): RedirectResponse
    {
        /** @var \App\Models\AdminUser $admin */
        $admin = Auth::guard('admin')->user();
        $data = $request->validated();
        unset($data['invoice_pdf']);
        $this->optionContractAdminService->update(
            $optionContract,
            $data,
            $admin,
            $request->file('invoice_pdf'),
        );

        return redirect()
            ->route('admin.option-contracts.index')
            ->with('status', __('契約を更新しました。'));
    }

    public function sendDemo(OptionContract $optionContract): RedirectResponse
    {
        try {
            $this->optionContractResidentNotifyService->sendDemoInvoiceAndSummary($optionContract);
        } catch (HttpException $e) {
            return redirect()
                ->route('admin.option-contracts.index')
                ->withErrors(['send' => $e->getMessage()]);
        }

        return redirect()
            ->route('admin.option-contracts.index')
            ->with('status', __('入居者の LINE にオプション内容と請求書リンクを送信しました。'));
    }
}
