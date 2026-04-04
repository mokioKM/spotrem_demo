<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Property;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface PropertyRepositoryInterface
{
    /**
     * LIFF 物件選択用: 有効物件のみ、名前昇順（基本設計 01）
     *
     * @return Collection<int, Property>
     */
    public function listActiveOrderedByName(): Collection;

    /**
     * 管理画面用: 論理削除フィルタ付きページネーション（新着順）
     */
    public function paginateForAdmin(bool $includeInactive, int $perPage): LengthAwarePaginator;

    public function findById(int $id): ?Property;

    public function create(array $attributes): Property;

    public function update(Property $property, array $attributes): Property;
}
