<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Resident;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class ResidentRepository implements ResidentRepositoryInterface
{
    public function findByLineUid(string $lineUid): ?Resident
    {
        return Resident::query()->where('line_uid', $lineUid)->first();
    }

    public function findActiveByLineUid(string $lineUid): ?Resident
    {
        return Resident::query()
            ->where('line_uid', $lineUid)
            ->where('is_active', true)
            ->first();
    }

    public function paginateForAdmin(?int $propertyId, int $perPage): LengthAwarePaginator
    {
        $q = Resident::query()
            ->with(['property'])
            ->orderByDesc('created_at');

        if ($propertyId !== null && $propertyId > 0) {
            $q->where('property_id', $propertyId);
        }

        return $q->paginate($perPage)->withQueryString();
    }

    public function create(array $attributes): Resident
    {
        return Resident::query()->create($attributes);
    }

    public function reactivateForReentry(Resident $resident, array $attributes): Resident
    {
        $resident->fill($attributes);
        $resident->is_active = true;
        $resident->registered_at = now();
        $resident->save();

        return $resident->refresh();
    }

    public function updateActiveProfile(Resident $resident, array $attributes): Resident
    {
        $resident->fill($attributes);
        $resident->save();

        return $resident->refresh();
    }
}
