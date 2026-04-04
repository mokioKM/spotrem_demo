<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\TroubleCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface TroubleCategoryRepositoryInterface
{
    public function paginateOrdered(int $perPage): LengthAwarePaginator;

    public function findById(int $id): ?TroubleCategory;

    public function create(array $attributes): TroubleCategory;

    public function update(TroubleCategory $category, array $attributes): TroubleCategory;
}
