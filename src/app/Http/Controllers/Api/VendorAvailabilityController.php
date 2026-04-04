<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Services\Calendar\GoogleCalendarAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

final class VendorAvailabilityController extends Controller
{
    public function __construct(
        private readonly VendorRepositoryInterface $vendorRepository,
        private readonly GoogleCalendarAvailabilityService $calendarAvailability,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists('trouble_categories', 'id')->where('is_active', true)],
            'property_id' => ['required', 'integer', Rule::exists(Property::class, 'id')->where('is_active', true)],
        ]);

        $property = Property::query()->findOrFail($data['property_id']);
        $vendors = $this->vendorRepository->findActiveMatchingCategoryAndRegion(
            (int) $data['category_id'],
            (string) $property->region,
        );

        $from = Carbon::today();
        $to = $from->copy()->addDays(30);

        $out = [];
        foreach ($vendors as $v) {
            $slots = $this->calendarAvailability->fetchAvailableSlots($v->google_calendar_id, $from, $to);
            if ($slots !== []) {
                $out[] = [
                    'vendor_id' => $v->id,
                    'vendor_name' => $v->name,
                    'available_slots' => $slots,
                ];
            }
        }

        return response()->json($out);
    }
}
