<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePropertyRequest;
use App\Http\Requests\Admin\UpdatePropertyRequest;
use App\Models\AdminUser;
use App\Services\Admin\PropertyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService,
    ) {}

    public function index(Request $request): View
    {
        $includeInactive = $request->boolean('include_inactive');
        $properties = $this->propertyService->listPaginated($includeInactive, 20);

        return view('admin.properties.index', [
            'properties' => $properties,
            'includeInactive' => $includeInactive,
        ]);
    }

    public function create(): View
    {
        return view('admin.properties.create');
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        /** @var AdminUser $actor */
        $actor = Auth::guard('admin')->user();

        $this->propertyService->store($actor, $request->validated());

        return redirect()
            ->route('admin.properties.index')
            ->with('status', __('物件を登録しました。'));
    }

    public function edit(int $property): View
    {
        $model = $this->propertyService->findOrFail($property);

        return view('admin.properties.edit', ['property' => $model]);
    }

    public function update(UpdatePropertyRequest $request, int $property): RedirectResponse
    {
        /** @var AdminUser $actor */
        $actor = Auth::guard('admin')->user();
        $model = $this->propertyService->findOrFail($property);

        $this->propertyService->update($actor, $model, $request->validated());

        return redirect()
            ->route('admin.properties.index')
            ->with('status', __('物件を更新しました。'));
    }

    public function destroy(int $property): RedirectResponse
    {
        $model = $this->propertyService->findOrFail($property);
        $this->propertyService->destroy($model);

        return redirect()
            ->route('admin.properties.index')
            ->with('status', __('物件を削除しました。'));
    }
}
