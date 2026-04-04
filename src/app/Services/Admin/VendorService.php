<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminUser;
use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class VendorService
{
    public function __construct(
        private readonly VendorRepositoryInterface $vendorRepository,
    ) {}

    public function listPaginated(bool $includeInactive, int $perPage)
    {
        return $this->vendorRepository->paginateForAdmin($includeInactive, $perPage);
    }

    public function findOrFailWithRelations(int $id): Vendor
    {
        $vendor = $this->vendorRepository->findByIdWithRelations($id);
        if ($vendor === null) {
            abort(404);
        }

        return $vendor;
    }

    /**
     * vendors と中間テーブルを同一トランザクションで更新する（設計書 06.3.4）
     *
     * @param  list<int>  $categoryIds
     * @param  list<string>  $regions
     */
    public function store(AdminUser $actor, array $vendorAttributes, array $categoryIds, array $regions): Vendor
    {
        $this->assertCanSetActive($actor, (bool) $vendorAttributes['is_active']);

        return DB::transaction(function () use ($vendorAttributes, $categoryIds, $regions): Vendor {
            $vendor = $this->vendorRepository->create($vendorAttributes);
            $this->vendorRepository->replaceGenres((int) $vendor->id, $categoryIds);
            $this->vendorRepository->replaceRegions((int) $vendor->id, $regions);

            return $vendor->fresh(['vendorGenres.troubleCategory', 'vendorRegions']) ?? $vendor;
        });
    }

    /**
     * @param  list<int>  $categoryIds
     * @param  list<string>  $regions
     */
    public function update(AdminUser $actor, Vendor $vendor, array $vendorAttributes, array $categoryIds, array $regions): Vendor
    {
        if ($vendor->is_active === true && (bool) $vendorAttributes['is_active'] === false) {
            $this->assertSuperAdmin($actor);
        }

        if ($vendor->is_active === false && (bool) $vendorAttributes['is_active'] === true) {
            $this->assertSuperAdmin($actor);
        }

        return DB::transaction(function () use ($vendor, $vendorAttributes, $categoryIds, $regions): Vendor {
            $this->vendorRepository->update($vendor, $vendorAttributes);
            $this->vendorRepository->replaceGenres((int) $vendor->id, $categoryIds);
            $this->vendorRepository->replaceRegions((int) $vendor->id, $regions);

            return $vendor->fresh(['vendorGenres.troubleCategory', 'vendorRegions']) ?? $vendor;
        });
    }

    public function destroy(Vendor $vendor): void
    {
        $vendor->delete();
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
            abort(403, __('業者の有効／無効を変更できるのはスーパー管理者のみです。'));
        }
    }
}
