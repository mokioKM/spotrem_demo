<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreVendorRequest;
use App\Http\Requests\Admin\UpdateVendorRequest;
use App\Models\AdminUser;
use App\Models\TroubleCategory;
use App\Services\Admin\VendorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class VendorController extends Controller
{
    public function __construct(
        private readonly VendorService $vendorService,
    ) {}

    public function index(Request $request): View
    {
        $includeInactive = $request->boolean('include_inactive');
        $vendors = $this->vendorService->listPaginated($includeInactive, 20);

        return view('admin.vendors.index', [
            'vendors' => $vendors,
            'includeInactive' => $includeInactive,
        ]);
    }

    public function create(): View
    {
        $categories = TroubleCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.vendors.create', ['categories' => $categories]);
    }

    public function store(StoreVendorRequest $request): RedirectResponse
    {
        /** @var AdminUser $actor */
        $actor = Auth::guard('admin')->user();

        $data = $request->validated();
        unset($data['category_ids'], $data['regions'], $data['regions_text']);
        $this->vendorService->store(
            $actor,
            $data,
            $request->categoryIds(),
            $request->regionStrings(),
        );

        return redirect()
            ->route('admin.vendors.index')
            ->with('status', __('業者を登録しました。'));
    }

    public function edit(int $vendor): View
    {
        $model = $this->vendorService->findOrFailWithRelations($vendor);
        $categories = TroubleCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.vendors.edit', [
            'vendor' => $model,
            'categories' => $categories,
        ]);
    }

    public function update(UpdateVendorRequest $request, int $vendor): RedirectResponse
    {
        /** @var AdminUser $actor */
        $actor = Auth::guard('admin')->user();
        $model = $this->vendorService->findOrFailWithRelations($vendor);

        $data = $request->validated();
        unset($data['category_ids'], $data['regions'], $data['regions_text']);
        $this->vendorService->update(
            $actor,
            $model,
            $data,
            $request->categoryIds(),
            $request->regionStrings(),
        );

        return redirect()
            ->route('admin.vendors.index')
            ->with('status', __('業者を更新しました。'));
    }

    public function destroy(int $vendor): RedirectResponse
    {
        $model = $this->vendorService->findOrFailWithRelations($vendor);
        $this->vendorService->destroy($model);

        return redirect()
            ->route('admin.vendors.index')
            ->with('status', __('業者を削除しました。'));
    }
}
