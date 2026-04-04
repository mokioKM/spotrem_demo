<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminUser;
use App\Models\Property;
use App\Repositories\Contracts\PropertyRepositoryInterface;

final class PropertyService
{
    public function __construct(
        private readonly PropertyRepositoryInterface $propertyRepository,
    ) {}

    public function listPaginated(bool $includeInactive, int $perPage)
    {
        return $this->propertyRepository->paginateForAdmin($includeInactive, $perPage);
    }

    public function findOrFail(int $id): Property
    {
        $property = $this->propertyRepository->findById($id);
        if ($property === null) {
            abort(404);
        }

        return $property;
    }

    /**
     * 無効化（is_active=false）はスーパー管理者のみ（基本設計 06）
     */
    public function store(AdminUser $actor, array $data): Property
    {
        $this->assertCanSetActive($actor, (bool) $data['is_active']);

        return $this->propertyRepository->create($data);
    }

    public function update(AdminUser $actor, Property $property, array $data): Property
    {
        if ($property->is_active === true && (bool) $data['is_active'] === false) {
            $this->assertSuperAdmin($actor);
        }

        if ($property->is_active === false && (bool) $data['is_active'] === true) {
            $this->assertSuperAdmin($actor);
        }

        return $this->propertyRepository->update($property, $data);
    }

    public function destroy(Property $property): void
    {
        $property->delete();
    }

    private function assertCanSetActive(AdminUser $actor, bool $isActive): void
    {
        if ($isActive === false) {
            $this->assertSuperAdmin($actor);
        }
    }

    private function assertSuperAdmin(AdminUser $actor): void
    {
        $actor->loadMissing('role');
        if (! $actor->isSuperAdmin()) {
            abort(403, __('物件の有効／無効を変更できるのはスーパー管理者のみです。'));
        }
    }
}
