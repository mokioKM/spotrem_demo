<?php

declare(strict_types=1);

namespace App\Services\Api;

use App\Models\Resident;
use App\Repositories\Contracts\ResidentRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 入居者プロフィールの更新（登録済み LIFF から）
 */
final class ResidentProfileUpdateService
{
    public function __construct(
        private readonly ResidentRepositoryInterface $residentRepository,
    ) {}

    /**
     * @param  array{property_id: int, name: string, age?: int|null, room_number: string, phone: string}  $payload
     */
    public function update(string $lineUid, array $payload): Resident
    {
        $resident = $this->residentRepository->findActiveByLineUid($lineUid);
        if ($resident === null) {
            throw new NotFoundHttpException(__('入居者登録を先に行ってください。'));
        }

        return DB::transaction(function () use ($resident, $payload): Resident {
            return $this->residentRepository->updateActiveProfile($resident, [
                'property_id' => $payload['property_id'],
                'name' => $payload['name'],
                'age' => $payload['age'] ?? null,
                'room_number' => $payload['room_number'],
                'phone' => $payload['phone'],
            ]);
        });
    }
}
