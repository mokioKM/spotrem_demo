<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateAdminResidentRequest;
use App\Models\AdminUser;
use App\Models\Property;
use App\Models\Resident;
use App\Services\Admin\ResidentAdminService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

final class ResidentAdminController extends Controller
{
    public function __construct(
        private readonly ResidentAdminService $residentAdminService,
    ) {}

    public function index(Request $request): View
    {
        $propertyId = $request->filled('property_id') ? $request->integer('property_id') : null;
        $residents = $this->residentAdminService->listPaginated($propertyId, 30);
        $properties = Property::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.residents.index', [
            'residents' => $residents,
            'properties' => $properties,
            'filters' => [
                'property_id' => $propertyId,
            ],
        ]);
    }

    public function edit(Request $request, Resident $resident): View
    {
        $resident->load('property');
        $propertyList = Property::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.residents.edit', [
            'resident' => $resident,
            'propertyList' => $propertyList,
            'returnPropertyId' => $request->integer('return_property_id') ?: null,
        ]);
    }

    public function update(UpdateAdminResidentRequest $request, Resident $resident): RedirectResponse
    {
        /** @var AdminUser $actor */
        $actor = Auth::guard('admin')->user();
        $this->residentAdminService->update($actor, $resident, $request->residentPayload());

        $rid = $request->filled('redirect_property_id') ? $request->integer('redirect_property_id') : null;
        $query = $rid !== null && $rid > 0 ? ['property_id' => $rid] : [];

        return redirect()
            ->route('admin.residents.index', $query)
            ->with('status', __('入居者情報を更新しました。'));
    }
}
