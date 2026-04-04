<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TroubleCategory;
use Illuminate\Http\JsonResponse;

final class TroubleCategoryListController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $rows = TroubleCategory::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'display_name', 'show_phone_number', 'emergency_phone']);

        return response()->json($rows);
    }
}
