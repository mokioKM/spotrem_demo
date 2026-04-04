<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 電話案内用種別向け: カテゴリ・物件地域に合致する有効業者の連絡先（カレンダー空きは問わない）
 */
final class VendorContactListController extends Controller
{
    public function __construct(
        private readonly VendorRepositoryInterface $vendorRepository,
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

        $out = $vendors->map(static fn (Vendor $v): array => [
            'vendor_id' => $v->id,
            'vendor_name' => $v->name,
            'phone' => $v->phone,
        ])->values()->all();

        return response()->json($out);
    }
}
