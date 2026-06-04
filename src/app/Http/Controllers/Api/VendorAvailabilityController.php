<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Services\Calendar\GoogleCalendarAvailabilityService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
            'vendor_id' => ['nullable', 'integer', Rule::exists(Vendor::class, 'id')->where('is_active', true)],
        ]);

        $property = Property::query()->findOrFail($data['property_id']);
        $vendors = $this->vendorRepository->findActiveMatchingCategoryAndRegion(
            (int) $data['category_id'],
            (string) $property->region,
        );

        $from = Carbon::today('Asia/Tokyo');
        $to = $from->copy()->addDays(30);

        if (isset($data['vendor_id'])) {
            $vendor = $vendors->firstWhere('id', (int) $data['vendor_id']);
            if ($vendor === null) {
                throw new HttpException(422, __('選択した業者はこの地域・カテゴリでは利用できません。'));
            }

            $slots = $this->calendarAvailability->fetchAvailableThreeHourSlots(
                $vendor->google_calendar_id,
                $from,
                $to,
            );

            return response()->json([
                'vendor_id' => $vendor->id,
                'vendor_name' => $vendor->name,
                'available_slots' => $slots,
            ]);
        }

        $out = [];
        foreach ($vendors as $v) {
            $slots = $this->calendarAvailability->fetchAvailableThreeHourSlots($v->google_calendar_id, $from, $to);
            if ($slots !== []) {
                $out[] = [
                    'vendor_id' => $v->id,
                    'vendor_name' => $v->name,
                ];
            }
        }

        return response()->json($out);
    }
}
