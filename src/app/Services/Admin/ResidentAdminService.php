<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\AdminUser;
use App\Models\Resident;
use App\Repositories\Contracts\ResidentRepositoryInterface;

final class ResidentAdminService
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
    ) {}

    public function listPaginated(?int $propertyId, int $perPage)
    {
        return $this->residentRepository->paginateForAdmin($propertyId, $perPage);
    }

    public function findOrFail(int $id): Resident
    {
        $r = Resident::query()->with(['property'])->find($id);
        if ($r === null) {
            abort(404);
        }

        return $r;
    }

    /**
     * @param  array{property_id: int, name: string, age?: int|null, room_number: string, phone: string, is_active: bool}  $data
     */
    public function update(AdminUser $actor, Resident $resident, array $data): Resident
    {
        if ($resident->is_active === true && $data['is_active'] === false) {
            $this->assertSuperAdmin($actor);
        }

        if ($resident->is_active === false && $data['is_active'] === true) {
            $this->assertSuperAdmin($actor);
        }

        $resident->fill([
            'property_id' => $data['property_id'],
            'name' => $data['name'],
            'age' => $data['age'] ?? null,
            'room_number' => $data['room_number'],
            'phone' => $data['phone'],
            'is_active' => $data['is_active'],
        ]);
        $resident->save();

        return $resident->refresh();
    }

    private function assertSuperAdmin(AdminUser $actor): void
    {
        $actor->loadMissing('role');
        if (! $actor->isSuperAdmin()) {
            abort(403, __('入居者の有効／無効を変更できるのはスーパー管理者のみです。'));
        }
    }
}
