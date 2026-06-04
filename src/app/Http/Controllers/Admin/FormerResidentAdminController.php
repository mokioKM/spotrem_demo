<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Resident;
use App\Services\Admin\ResidentAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FormerResidentAdminController extends Controller
{
    public function __construct(
        private readonly ResidentAdminService $residentAdminService,
    ) {}

    public function index(Request $request): View
    {
        $propertyId = $request->filled('property_id') ? $request->integer('property_id') : null;
        $residents = $this->residentAdminService->listMovedOutPaginated($propertyId, 30);
        $properties = Property::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('admin.former_residents.index', [
            'residents' => $residents,
            'properties' => $properties,
            'filters' => [
                'property_id' => $propertyId,
            ],
        ]);
    }

    public function show(Request $request, Resident $resident): View
    {
        if ($resident->is_active) {
            abort(404);
        }

        $resident->load('property');

        return view('admin.former_residents.show', [
            'resident' => $resident,
            'returnPropertyId' => $request->integer('return_property_id') ?: null,
        ]);
    }
}
