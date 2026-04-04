<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\TroubleCategory;
use App\Repositories\Contracts\TroubleCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class TroubleCategoryRepository implements TroubleCategoryRepositoryInterface
{
    public function paginateOrdered(int $perPage): LengthAwarePaginator
    {
        return TroubleCategory::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findById(int $id): ?TroubleCategory
    {
        return TroubleCategory::query()->find($id);
    }

    public function create(array $attributes): TroubleCategory
    {
        return TroubleCategory::query()->create($attributes);
    }

    public function update(TroubleCategory $category, array $attributes): TroubleCategory
    {
        $category->fill($attributes);
        $category->save();

        return $category->refresh();
    }
}
