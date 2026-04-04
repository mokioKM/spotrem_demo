<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use Illuminate\Http\JsonResponse;

/**
 * GET /api/properties — LIFF 入居者登録フォーム用
 */
final class PropertyListController extends Controller
{
    public function __invoke(PropertyRepositoryInterface $propertyRepository): JsonResponse
    {
        $items = $propertyRepository->listActiveOrderedByName()->map(static fn ($p): array => [
            'id' => $p->id,
            'name' => $p->name,
        ]);

        return response()->json($items);
    }
}
