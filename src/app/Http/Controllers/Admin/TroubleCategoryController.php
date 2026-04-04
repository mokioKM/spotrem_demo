<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreTroubleCategoryRequest;
use App\Http\Requests\Admin\UpdateTroubleCategoryRequest;
use App\Models\TroubleCategory;
use App\Services\Admin\TroubleCategoryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TroubleCategoryController extends Controller
{
    public function __construct(
        private readonly TroubleCategoryService $troubleCategoryService,
    ) {}

    public function index(): View
    {
        $categories = $this->troubleCategoryService->listPaginated(30);

        return view('admin.trouble_categories.index', ['categories' => $categories]);
    }

    public function create(): View
    {
        return view('admin.trouble_categories.create', ['category' => null]);
    }

    public function store(StoreTroubleCategoryRequest $request): RedirectResponse
    {
        $this->troubleCategoryService->store($request->categoryPayload());

        return redirect()
            ->route('admin.trouble-categories.index')
            ->with('status', __('困りごと種別を登録しました。'));
    }

    public function edit(TroubleCategory $troubleCategory): View
    {
        return view('admin.trouble_categories.edit', ['category' => $troubleCategory]);
    }

    public function update(UpdateTroubleCategoryRequest $request, TroubleCategory $troubleCategory): RedirectResponse
    {
        $this->troubleCategoryService->update($troubleCategory, $request->categoryPayload());

        return redirect()
            ->route('admin.trouble-categories.index')
            ->with('status', __('困りごと種別を更新しました。'));
    }
}
