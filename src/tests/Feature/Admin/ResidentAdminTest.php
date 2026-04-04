<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Property;
use App\Models\Resident;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ResidentAdminTest extends TestCase
{
    use RefreshDatabase;

    private function actingSuperAdmin(): AdminUser
    {
        $role = Role::query()->create([
            'name' => 'super_admin',
            'display_name' => 'スーパー管理者',
            'description' => null,
        ]);

        return AdminUser::query()->create([
            'role_id' => $role->id,
            'name' => 'Test Admin',
            'email' => 'admin@test.local',
            'password_hash' => Hash::make('secret'),
            'line_uid' => null,
            'is_active' => true,
        ]);
    }

    public function test_guest_redirected_from_residents_index(): void
    {
        $response = $this->get('/admin/residents');

        $response->assertRedirect(route('admin.login'));
    }

    public function test_admin_can_list_and_filter_residents_by_property(): void
    {
        $admin = $this->actingSuperAdmin();
        $p1 = Property::query()->create([
            'name' => '物件A',
            'address' => 'a',
            'region' => '東京都',
            'room_count' => 10,
            'is_active' => true,
        ]);
        $p2 = Property::query()->create([
            'name' => '物件B',
            'address' => 'b',
            'region' => '大阪府',
            'room_count' => 5,
            'is_active' => true,
        ]);
        Resident::query()->create([
            'property_id' => $p1->id,
            'line_uid' => 'Ua',
            'name' => '山田',
            'age' => null,
            'room_number' => '101',
            'phone' => '090-0000-0001',
            'registered_at' => now(),
            'is_active' => true,
        ]);
        Resident::query()->create([
            'property_id' => $p2->id,
            'line_uid' => 'Ub',
            'name' => '佐藤',
            'age' => null,
            'room_number' => '202',
            'phone' => '090-0000-0002',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->get('/admin/residents?property_id='.$p1->id);

        $response->assertOk();
        $response->assertSee('山田');
        $response->assertDontSee('佐藤');
    }

    public function test_admin_can_update_resident(): void
    {
        $admin = $this->actingSuperAdmin();
        $p = Property::query()->create([
            'name' => '物件',
            'address' => 'x',
            'region' => '東京都',
            'room_count' => 1,
            'is_active' => true,
        ]);
        $r = Resident::query()->create([
            'property_id' => $p->id,
            'line_uid' => 'Utest',
            'name' => '旧名',
            'age' => 20,
            'room_number' => '1',
            'phone' => '090-1111-1111',
            'registered_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin, 'admin')->put('/admin/residents/'.$r->id, [
            'property_id' => (string) $p->id,
            'name' => '新名',
            'age' => '25',
            'room_number' => '2',
            'phone' => '090-2222-2222',
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.residents.index'));
        $this->assertDatabaseHas('residents', [
            'id' => $r->id,
            'name' => '新名',
            'room_number' => '2',
            'phone' => '090-2222-2222',
        ]);
    }
}
