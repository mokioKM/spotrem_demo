<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Property;
use App\Repositories\Contracts\PropertyRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final class PropertyRepository implements PropertyRepositoryInterface
{
    public function listActiveOrderedByName(): Collection
    {
        return Property::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function paginateForAdmin(bool $includeInactive, int $perPage): LengthAwarePaginator
    {
        $query = Property::query()->orderByDesc('created_at');

        if (! $includeInactive) {
            $query->where('is_active', true);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findById(int $id): ?Property
    {
        return Property::query()->find($id);
    }

    public function create(array $attributes): Property
    {
        return Property::query()->create($attributes);
    }

    public function update(Property $property, array $attributes): Property
    {
        $property->fill($attributes);
        $property->save();

        return $property;
    }
}
