<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface VendorRepositoryInterface
{
    /**
     * トラブル報告: カテゴリ AND 物件地域の両方に対応する有効業者
     *
     * @return Collection<int, Vendor>
     */
    public function findActiveMatchingCategoryAndRegion(int $categoryId, string $propertyRegion): Collection;

    public function paginateForAdmin(bool $includeInactive, int $perPage): LengthAwarePaginator;

    public function findById(int $id): ?Vendor;

    public function findByIdWithRelations(int $id): ?Vendor;

    public function create(array $attributes): Vendor;

    public function update(Vendor $vendor, array $attributes): Vendor;

    /**
     * 設計書どおり全削除→再INSERTでジャンル中間を同期する
     *
     * @param  list<int>  $categoryIds
     */
    public function replaceGenres(int $vendorId, array $categoryIds): void;

    /**
     * @param  list<string>  $regions
     */
    public function replaceRegions(int $vendorId, array $regions): void;
}
