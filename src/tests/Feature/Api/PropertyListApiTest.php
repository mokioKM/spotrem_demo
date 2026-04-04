<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Property;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PropertyListApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_only_active_properties_ordered_by_name(): void
    {
        Property::query()->create([
            'name' => 'Bマンション',
            'address' => '大阪',
            'region' => '大阪府',
            'room_count' => null,
            'is_active' => true,
        ]);
        Property::query()->create([
            'name' => 'Aマンション',
            'address' => '東京',
            'region' => '東京都',
            'room_count' => null,
            'is_active' => true,
        ]);
        Property::query()->create([
            'name' => '廃止物件',
            'address' => '—',
            'region' => '—',
            'room_count' => null,
            'is_active' => false,
        ]);

        $response = $this->getJson('/api/properties');

        $response->assertOk();
        $response->assertJsonCount(2);
        $response->assertJsonPath('0.name', 'Aマンション');
        $response->assertJsonPath('1.name', 'Bマンション');
    }
}
