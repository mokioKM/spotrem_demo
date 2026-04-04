<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Models\Resident;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface ResidentRepositoryInterface
{
    public function findByLineUid(string $lineUid): ?Resident;

    public function findActiveByLineUid(string $lineUid): ?Resident;

    public function paginateForAdmin(?int $propertyId, int $perPage): LengthAwarePaginator;

    public function create(array $attributes): Resident;

    /**
     * 退去済み（is_active=false）の同一 line_uid を再入居用に更新する（DB の line_uid UNIQUE のため INSERT ではなく UPDATE）
     */
    public function reactivateForReentry(Resident $resident, array $attributes): Resident;

    /**
     * アクティブ入居者のプロフィール更新（物件・氏名・部屋番号等）
     *
     * @param  array{property_id: int, name: string, age?: int|null, room_number: string, phone: string}  $attributes
     */
    public function updateActiveProfile(Resident $resident, array $attributes): Resident;
}
