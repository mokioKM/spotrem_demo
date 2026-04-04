<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\TroubleCategory;
use App\Repositories\Contracts\TroubleCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class TroubleCategoryService
{
    public function __construct(
        private readonly TroubleCategoryRepositoryInterface $troubleCategoryRepository,
    ) {}

    public function listPaginated(int $perPage): LengthAwarePaginator
    {
        return $this->troubleCategoryRepository->paginateOrdered($perPage);
    }

    public function findOrFail(int $id): TroubleCategory
    {
        $row = $this->troubleCategoryRepository->findById($id);
        if ($row === null) {
            abort(404);
        }

        return $row;
    }

    /**
     * @param  array{name: string, display_name: string, show_phone_number: bool, emergency_phone: ?string, sort_order: int, is_active: bool}  $data
     */
    public function store(array $data): TroubleCategory
    {
        return $this->troubleCategoryRepository->create($data);
    }

    /**
     * @param  array{name: string, display_name: string, show_phone_number: bool, emergency_phone: ?string, sort_order: int, is_active: bool}  $data
     */
    public function update(TroubleCategory $category, array $data): TroubleCategory
    {
        return $this->troubleCategoryRepository->update($category, $data);
    }
}
