<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Vendor;
use App\Models\VendorGenre;
use App\Models\VendorRegion;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class VendorRepository implements VendorRepositoryInterface
{
    public function findActiveMatchingCategoryAndRegion(int $categoryId, string $propertyRegion): Collection
    {
        return Vendor::query()
            ->where('is_active', true)
            ->whereHas('vendorGenres', static function ($q) use ($categoryId): void {
                $q->where('category_id', $categoryId);
            })
            ->whereHas('vendorRegions', static function ($q) use ($propertyRegion): void {
                $q->where('region', $propertyRegion);
            })
            ->orderBy('name')
            ->get();
    }

    public function paginateForAdmin(bool $includeInactive, int $perPage): LengthAwarePaginator
    {
        $query = Vendor::query()
            ->with([
                'vendorGenres' => static function ($q): void {
                    $q->orderBy('category_id');
                },
                'vendorGenres.troubleCategory',
                'vendorRegions' => static function ($q): void {
                    $q->orderBy('region');
                },
            ])
            ->orderByDesc('created_at');

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Vendor
    {
        return Vendor::query()->find($id);
    }

    public function findByIdWithRelations(int $id): ?Vendor
    {
        return Vendor::query()
            ->with([
                'vendorGenres.troubleCategory',
                'vendorRegions',
            ])
            ->find($id);
    }

    public function create(array $attributes): Vendor
    {
        return Vendor::query()->create($attributes);
    }

    public function update(Vendor $vendor, array $attributes): Vendor
    {
        $vendor->fill($attributes);
        $vendor->save();

        return $vendor;
    }

    public function replaceGenres(int $vendorId, array $categoryIds): void
    {
        VendorGenre::query()->where('vendor_id', $vendorId)->delete();

        $rows = [];
        foreach ($categoryIds as $categoryId) {
            $rows[] = [
                'vendor_id' => $vendorId,
                'category_id' => $categoryId,
            ];
        }

        if ($rows !== []) {
            VendorGenre::query()->insert($rows);
        }
    }

    public function replaceRegions(int $vendorId, array $regions): void
    {
        VendorRegion::query()->where('vendor_id', $vendorId)->delete();

        $rows = [];
        foreach ($regions as $region) {
            $rows[] = [
                'vendor_id' => $vendorId,
                'region' => $region,
            ];
        }

        if ($rows !== []) {
            VendorRegion::query()->insert($rows);
        }
    }
}
