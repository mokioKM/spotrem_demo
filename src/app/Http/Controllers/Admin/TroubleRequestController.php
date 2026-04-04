<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ScheduleTroubleRequestRequest;
use App\Models\Property;
use App\Models\TroubleRequest;
use App\Services\Admin\TroubleRequestWorkflowService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TroubleRequestController extends Controller
{
    public function __construct(
        private readonly TroubleRequestWorkflowService $workflowService,
    ) {}

    public function index(Request $request): View
    {
        $query = TroubleRequest::query()
            ->with(['resident', 'property', 'category', 'vendor'])
            ->orderByDesc('created_at');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->integer('property_id'));
        }

        $requests = $query->paginate(20)->withQueryString();
        $properties = Property::query()->where('is_active', true)->orderBy('name')->get();

        return view('admin.trouble_requests.index', [
            'requests' => $requests,
            'properties' => $properties,
            'filters' => [
                'status' => $request->string('status')->toString(),
                'property_id' => $request->input('property_id'),
            ],
        ]);
    }

    public function edit(TroubleRequest $troubleRequest): View
    {
        $troubleRequest->load([
            'resident.property',
            'property',
            'category',
            'vendor',
            'requestAttachments',
        ]);

        return view('admin.trouble_requests.edit', ['request' => $troubleRequest]);
    }

    public function schedule(ScheduleTroubleRequestRequest $request, TroubleRequest $troubleRequest): RedirectResponse
    {
        $this->workflowService->schedule(
            $troubleRequest,
            Carbon::parse($request->validated('scheduled_at')),
        );

        return redirect()
            ->route('admin.trouble-requests.edit', $troubleRequest)
            ->with('status', __('日程を確定しました。'));
    }

    public function complete(TroubleRequest $troubleRequest): RedirectResponse
    {
        $this->workflowService->markCompleted($troubleRequest);

        return redirect()
            ->route('admin.trouble-requests.edit', $troubleRequest)
            ->with('status', __('対応完了にしました。'));
    }

    public function cancel(TroubleRequest $troubleRequest): RedirectResponse
    {
        $this->workflowService->cancel($troubleRequest);

        return redirect()
            ->route('admin.trouble-requests.edit', $troubleRequest)
            ->with('status', __('キャンセルしました。'));
    }
}
